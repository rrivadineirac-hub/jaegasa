<?php
/**
 * ============================================================
 * asistencia.controller.php  —  Controlador CRUD Asistencia
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Gestiona el paso de asistencia diaria.
 * Acciones: index, guardar, guardar_lote, listar_hoy, etc.
 * Accesible para el Administrador (rol 1) y el Administrativo (rol 2).
 * ============================================================
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Sistema_Fluter/');
}

require_once BASE_PATH . '/Config/Conexion.php';
require_once BASE_PATH . '/Config/Seguridad.php';
require_once BASE_PATH . '/Model/masistencia.php';

$accion = $_GET['a'] ?? $_GET['op'] ?? 'index';
$accionesApi = ['guardar_lote', 'guardar_json', 'listar_hoy', 'registrarQr', 'verificarRude', 'obtener_estados'];

// Control de acceso por rol (1 = Administrador, 2 = Administrativo/Regente) salvo endpoints API
$rolSesion = (int) ($_SESSION['idRoles'] ?? 0);
if ($rolSesion !== 1 && $rolSesion !== 2 && !in_array($accion, $accionesApi, true)) {
    http_response_code(403);
    die('Acceso denegado.');
}

$modelo = new ModeloAsistencia();
$urlModulo = BASE_URL . 'index.php?c=asistencia';

if (!function_exists('redirigirConMensaje')) {
    function redirigirConMensaje(string $urlModulo, string $tipo, string $mensaje): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: ' . $urlModulo);
        exit;
    }
}

switch ($accion) {


    // --------------------------------------------------------
    // CARGAR ASISTENCIA DEL CURSO DESDE DOCENTES POR PERIODOS
    // --------------------------------------------------------
    case 'cargarDesdeDocentes':
        $idCurso = (int) ($_REQUEST['idCurso'] ?? 0);
        $fecha   = trim($_REQUEST['fecha'] ?? '');

        if ($idCurso <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'Debe seleccionar un curso válido para cargar la asistencia del curso.');
        }
        if ($fecha === '') {
            $fecha = date('Y-m-d');
        }

        $idUsuarioLogueado = (int) $_SESSION['idUsuario'];
        $idAdmin = $modelo->obtenerAdministrativoPorUsuario($idUsuarioLogueado);

        if ($idAdmin === null) {
            try {
                $db = Conexion::obtener();
                $chk = $db->query("SELECT idAdministrativo FROM Administrativo WHERE estado = 1 LIMIT 1")->fetch();
                $idAdmin = $chk ? (int) $chk['idAdministrativo'] : 0;
            } catch (Throwable $e) {
                $idAdmin = 0;
            }
        }

        $resultado = $modelo->cargarAsistenciaDesdeDocentes($idCurso, $fecha, $idAdmin);

        redirigirConMensaje(
            $urlModulo . "&idCurso={$idCurso}&fecha={$fecha}",
            $resultado['tipo'] ?? ($resultado['ok'] ? 'success' : 'warning'),
            $resultado['mensaje']
        );
        break;

    // --------------------------------------------------------
    // GUARDAR ASISTENCIAS
    // --------------------------------------------------------
    case 'guardar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $idCurso = (int) ($_POST['idCurso'] ?? 0);
        $fecha   = trim($_POST['fecha'] ?? '');
        $asist   = $_POST['asistencias'] ?? []; // Formato: [idInscripcion => ['estado' => 'A', 'horaLlegada' => '08:15', 'observacion' => '...']]
        foreach ($asist as $k => $v) {
            if (isset($v['observacion'])) {
                $asist[$k]['observacion'] = Seguridad::mayuscula($v['observacion']);
            }
        }


        // Validaciones básicas
        if ($idCurso <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'Debe seleccionar un curso válido.');
        }
        if ($fecha === '') {
            $fecha = date('Y-m-d');
        }

        // Obtener el administrativo que registra
        $idUsuarioLogueado = (int) $_SESSION['idUsuario'];
        $idAdmin = $modelo->obtenerAdministrativoPorUsuario($idUsuarioLogueado);

        if ($idAdmin === null) {
            // Intentamos buscar un administrativo activo por defecto
            try {
                $db = Conexion::obtener();
                $chk = $db->query("SELECT idAdministrativo FROM Administrativo WHERE estado = 1 LIMIT 1")->fetch();
                $idAdmin = $chk ? (int) $chk['idAdministrativo'] : 0;
            } catch (Throwable $e) {
                $idAdmin = 0;
            }
        }

        if ($idAdmin === 0) {
            redirigirConMensaje(
                $urlModulo . "&idCurso={$idCurso}&fecha={$fecha}",
                'danger',
                'El usuario actual no está vinculado a ningún personal administrativo de registro.'
            );
        }

        // Proceder a guardar
        $resultado = $modelo->guardarAsistenciaMasiva($asist, $fecha, $idAdmin);

        if ($resultado['ok']) {
            redirigirConMensaje(
                $urlModulo . "&fecha={$fecha}",
                'success',
                $resultado['mensaje']
            );
        } else {
            redirigirConMensaje(
                $urlModulo . "&idCurso={$idCurso}&fecha={$fecha}",
                'danger',
                $resultado['mensaje']
            );
        }
        break;

    // --------------------------------------------------------
    // API: GUARDAR LOTE DE ASISTENCIAS (DESDE FLUTTER)
    // --------------------------------------------------------
    case 'guardar_lote':
    case 'guardar_json':
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type');

        $rawInput = file_get_contents('php://input');
        $inputData = json_decode($rawInput, true) ?? $_POST;

        $curso = trim($inputData['curso'] ?? '');
        $fecha = trim($inputData['fecha'] ?? date('Y-m-d'));
        if (strlen($fecha) > 10) {
            $fecha = substr($fecha, 0, 10);
        }
        $asistencias = $inputData['asistencias'] ?? [];

        if (empty($asistencias)) {
            echo json_encode(['ok' => false, 'status' => 'error', 'mensaje' => 'No se recibieron asistencias para guardar.']);
            exit;
        }

        $idUsuarioLogueado = (int) ($_SESSION['idUsuario'] ?? 0);
        $idAdmin = $modelo->obtenerAdministrativoPorUsuario($idUsuarioLogueado);

        if (!$idAdmin) {
            try {
                $db = Conexion::obtener();
                $chk = $db->query("SELECT idAdministrativo FROM Administrativo WHERE estado = 1 LIMIT 1")->fetch();
                $idAdmin = $chk ? (int) $chk['idAdministrativo'] : 1;
            } catch (Throwable $e) {
                $idAdmin = 1;
            }
        }

        $res = $modelo->guardarAsistenciaLoteJson($asistencias, $fecha, $idAdmin, $curso);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    // --------------------------------------------------------
    // API: LISTAR ASISTENCIAS REGISTRADAS HOY (DESDE FLUTTER)
    // --------------------------------------------------------
    case 'listar_hoy':
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');

        $list = $modelo->listarAsistenciasHoyApi();
        echo json_encode($list, JSON_UNESCAPED_UNICODE);
        exit;

    // --------------------------------------------------------
    // API: OBTENER ESTADOS REGISTRADOS DE UN CURSO Y FECHA
    // --------------------------------------------------------
    case 'obtener_estados':
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');

        $curso = trim($_GET['curso'] ?? '');
        $fecha = trim($_GET['fecha'] ?? date('Y-m-d'));
        if (strlen($fecha) > 10) {
            $fecha = substr($fecha, 0, 10);
        }

        $estados = $modelo->obtenerEstadosAsistenciaCursoYFecha($curso, $fecha);
        echo json_encode(['ok' => true, 'estados' => $estados], JSON_UNESCAPED_UNICODE);
        exit;

    // --------------------------------------------------------
    // API: REGISTRAR O VERIFICAR ASISTENCIA POR CÓDIGO QR (AJAX)
    // --------------------------------------------------------
    case 'registrarQr':
        header('Content-Type: application/json; charset=utf-8');

        $inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $rude = trim($inputData['rude'] ?? ($_GET['rude'] ?? ''));
        $fecha = trim($inputData['fecha'] ?? ($_GET['fecha'] ?? date('Y-m-d')));
        $horaMaxEntrada = trim($inputData['horaMaxEntrada'] ?? ($_GET['horaMaxEntrada'] ?? '08:00'));

        if (empty($rude)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'mensaje' => 'Debe ingresar o escanear un código RUDE.']);
            exit;
        }

        // 1. Obtener curso y año activo
        $cursos = $modelo->listarCursos();
        $gestionActualAnio = (function () use ($cursos) {
            $anios = array_column($cursos, 'anio');
            return $anios ? max($anios) : (int) date('Y');
        })();

        // 2. Buscar inscripción activa por RUDE
        $inscripcion = $modelo->obtenerInscripcionActivaPorRude($rude, $gestionActualAnio);
        if (!$inscripcion) {
            http_response_code(404);
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Estudiante con RUDE "' . $rude . '" no encontrado o no está inscrito en la gestión ' . $gestionActualAnio . '.'
            ]);
            exit;
        }

        $idInscripcion = (int) $inscripcion['idInscripcion'];
        $horaActualHora = date('H:i:s');
        $horaFormateada = date('H:i');

        // 3. Verificar si la asistencia YA FUE TOMADA previamente en esta fecha
        $asistenciaPrevia = $modelo->obtenerAsistenciaPorInscripcionYFecha($idInscripcion, $fecha);

        // Obtener ID del administrativo que escanea
        $idUsuarioLogueado = (int) ($_SESSION['idUsuario'] ?? 0);
        $idAdmin = $modelo->obtenerAdministrativoPorUsuario($idUsuarioLogueado) ?? 1;

        $fotoRelativa = !empty($inscripcion['foto']) ? 'inagenes/Estudiante/' . $inscripcion['foto'] : 'inagenes/Estudiante/default.png';

        if ($asistenciaPrevia !== null) {
            // YA FUE TOMADA ANTERIORMENTE
            $estadoPrevio = $asistenciaPrevia['estado'];
            $horaPrevia = $asistenciaPrevia['horaLlegada'] ? substr($asistenciaPrevia['horaLlegada'], 0, 5) : $horaFormateada;
            $txtEstado = ($estadoPrevio === 'A') ? 'Presente' : (($estadoPrevio === 'R') ? 'Retraso' : (($estadoPrevio === 'F') ? 'Falta' : 'Licencia'));

            echo json_encode([
                'ok' => true,
                'yaTomada' => true,
                'mensaje' => 'La asistencia de ' . $inscripcion['estudianteNombre'] . ' YA FUE TOMADA previamente (' . $txtEstado . ' a las ' . $horaPrevia . ').',
                'datos' => [
                    'idInscripcion' => $idInscripcion,
                    'idCurso'       => (int) $inscripcion['idCurso'],
                    'nombre'        => $inscripcion['estudianteNombre'],
                    'rude'          => $inscripcion['rude'],
                    'curso'         => $inscripcion['cursoNombre'] . ' ' . $inscripcion['paraleloNombre'] . ' (' . $inscripcion['nivelNombre'] . ')',
                    'foto'          => $fotoRelativa,
                    'estado'        => $estadoPrevio,
                    'horaLlegada'   => $horaPrevia,
                    'yaTomada'      => true
                ]
            ]);
            exit;
        }

        // 4. SI NO HA SIDO TOMADA AÚN: Registrar ahora (Presente 'A' o Retraso 'R' según la hora límite)
        $estadoNuevo = 'A';
        if ($horaFormateada > $horaMaxEntrada) {
            $estadoNuevo = 'R';
        }

        $exito = $modelo->registrarAsistenciaIndividual($idInscripcion, $fecha, $estadoNuevo, $horaActualHora, $idAdmin);

        if (!$exito) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error al registrar la asistencia en la base de datos.']);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'yaTomada' => false,
            'mensaje' => 'Asistencia de ' . $inscripcion['estudianteNombre'] . ' registrada exitosamente.',
            'datos' => [
                'idInscripcion' => $idInscripcion,
                'idCurso'       => (int) $inscripcion['idCurso'],
                'nombre'        => $inscripcion['estudianteNombre'],
                'rude'          => $inscripcion['rude'],
                'curso'         => $inscripcion['cursoNombre'] . ' ' . $inscripcion['paraleloNombre'] . ' (' . $inscripcion['nivelNombre'] . ')',
                'foto'          => $fotoRelativa,
                'estado'        => $estadoNuevo,
                'horaLlegada'   => $horaFormateada,
                'yaTomada'      => false
            ]
        ]);
        exit;

    // --------------------------------------------------------
    // API: VERIFICAR CÓDIGO RUDE (AJAX)
    // --------------------------------------------------------
    case 'verificarRude':
        header('Content-Type: application/json; charset=utf-8');

        $rude = trim($_GET['rude'] ?? '');
        if (empty($rude)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'mensaje' => 'El código RUDE es obligatorio.']);
            exit;
        }

        // Resolver año de gestión actual
        $cursos = $modelo->listarCursos();
        $gestionActualAnio = (function () use ($cursos) {
            $anios = array_column($cursos, 'anio');
            return $anios ? max($anios) : (int) date('Y');
        })();

        // Buscar estudiante de manera general en el colegio
        $estudiante = $modelo->buscarEstudiantePorRudeGeneral($rude, $gestionActualAnio);
        if (!$estudiante) {
            echo json_encode([
                'ok' => false,
                'codigoValido' => false,
                'mensaje' => 'Código QR no reconocido en el sistema.'
            ]);
            exit;
        }

        $nombreCompleto = $estudiante['estudianteNombre'];
        
        if (empty($estudiante['cursoNombre'])) {
            echo json_encode([
                'ok' => true,
                'codigoValido' => true,
                'inscrito' => false,
                'nombre' => $nombreCompleto,
                'mensaje' => "El estudiante {$nombreCompleto} no está inscrito en la gestión {$gestionActualAnio}."
            ]);
            exit;
        }

        $cursoDesc = trim($estudiante['cursoNombre'] . ' ' . $estudiante['paraleloNombre'] . ' (' . $estudiante['nivelNombre'] . ' - ' . $estudiante['turnoNombre'] . ')');
        echo json_encode([
            'ok' => true,
            'codigoValido' => true,
            'inscrito' => true,
            'nombre' => $nombreCompleto,
            'curso' => $cursoDesc,
            'mensaje' => "El estudiante {$nombreCompleto} pertenece al curso {$cursoDesc}."
        ]);
        exit;

    // --------------------------------------------------------
    // VISTA PRINCIPAL (Filtro + Paso de lista)
    // --------------------------------------------------------
    case 'index':
    default:
        $idCurso     = (int) ($_GET['idCurso'] ?? 0);
        $fecha       = trim($_GET['fecha'] ?? date('Y-m-d'));
        $cursos      = $modelo->listarCursos();
        $estudiantes = [];

        if ($idCurso > 0) {
            $estudiantes = $modelo->listarEstudiantesPorCursoYFecha($idCurso, $fecha);
        }

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vasistencia.php';
        break;
}
