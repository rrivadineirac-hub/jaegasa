<?php
/**
 * ============================================================
 * curso.controller.php — Controlador CRUD Curso
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Acciones: index, guardar, eliminar
 * Solo accesible para el rol Administrador (idRoles = 1).
 * ============================================================
 */

require_once BASE_PATH . '/Model/mcurso.php';

$accion = $_GET['a'] ?? $_GET['op'] ?? 'index';

if ((int) ($_SESSION['idRoles'] ?? 0) !== 1 && !in_array($accion, ['listar_json', 'listar_cursos', 'listar'], true)) {
    http_response_code(403);
    die('Acceso denegado.');
}

$modelo = new ModeloCurso();
$urlModulo = BASE_URL . 'index.php?c=curso';

if (!function_exists('redirigirConMensaje')) {
    function redirigirConMensaje(string $urlModulo, string $tipo, string $mensaje): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: ' . $urlModulo);
        exit;
    }
}

switch ($accion) {

    case 'listar_json':
    case 'listar_cursos':
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');

        $cursosDb = $modelo->listar();
        $salida = [];
        foreach ($cursosDb as $row) {
            $nombreBase = trim($row['nombre'] ?? '');
            $paralelo   = trim($row['paralelo'] ?? '');
            $nivel      = trim($row['nivel'] ?? '');
            $turno      = trim($row['turno'] ?? '');
            $nombreCurso = trim("{$nombreBase} {$nivel} {$paralelo}");
            if (empty($nombreBase)) {
                $nombreCurso = trim("{$nivel} {$paralelo}");
            }
            $salida[] = [
                'idCurso'     => (int) ($row['idCurso'] ?? 0),
                'nombre'      => $nombreBase,
                'paralelo'    => $paralelo,
                'nivel'       => $nivel,
                'turno'       => $turno,
                'anio'        => (int) ($row['anio'] ?? 0),
                'inscritos'   => (int) ($row['inscritos'] ?? 0),
                'nombreCurso' => $nombreCurso,
            ];
        }
        echo json_encode($salida, JSON_UNESCAPED_UNICODE);
        exit;

    case 'guardar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $id = (int) ($_POST['idCurso'] ?? 0);
        $d = [
            'idGestion'  => (int) ($_POST['idGestion']  ?? 0),
            'idNivel'    => (int) ($_POST['idNivel']    ?? 0),
            'idTurno'    => (int) ($_POST['idTurno']    ?? 0),
            'idParalelo' => (int) ($_POST['idParalelo'] ?? 0),
            'idHorario'  => (int) ($_POST['idHorario']  ?? 0),
            'nombre'     => Seguridad::mayuscula($_POST['nombre'] ?? ''),
            'cupo'       => (int) ($_POST['cupo'] ?? 36),
        ];


        if ($d['idGestion'] <= 0 || $d['idNivel'] <= 0 || $d['idTurno'] <= 0 || $d['idParalelo'] <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'Gestión, nivel, turno y paralelo son obligatorios.');
        }

        if ($modelo->duplicado($d, $id)) {
            redirigirConMensaje($urlModulo, 'danger', 'Ya existe un curso con esa gestión, nivel, turno y paralelo.');
        }

        $r = $id > 0 ? $modelo->actualizar($id, $d) : $modelo->crear($d);
        redirigirConMensaje($urlModulo, $r['ok'] ? 'success' : 'danger', $r['mensaje']);
        break;

    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }
        $r = $modelo->eliminar((int) ($_POST['idCurso'] ?? 0));
        redirigirConMensaje($urlModulo, $r['ok'] ? 'success' : 'danger', $r['mensaje']);
        break;

    case 'index':
    default:
        $buscar    = trim($_GET['q'] ?? '');
        $cursos    = $modelo->listar($buscar);
        $gestiones = $modelo->listarGestiones();
        $niveles   = $modelo->listarNiveles();
        $turnos    = $modelo->listarTurnos();
        $paralelos = $modelo->listarParalelos();
        $horarios  = $modelo->listarHorarios();

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vcurso.php';
        break;
}
