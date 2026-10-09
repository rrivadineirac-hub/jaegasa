<?php
/**
 * ============================================================
 * malla_curricular.controller.php  —  Controlador CRUD Malla Curricular
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Gestiona la configuración del plan de estudios (malla curricular).
 * Acciones: index, guardar, eliminar
 * Solo accesible para el Administrador (rol 1).
 * ============================================================
 */

require_once BASE_PATH . '/Model/mmalla_curricular.php';

// Control de acceso por rol (1 = Administrador)
if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloMallaCurricular();

$urlModulo = BASE_URL . 'index.php?c=malla_curricular';

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
    // GUARDAR (crear o actualizar)
    // --------------------------------------------------------
    case 'guardar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $idCurso = (int) ($_POST['idCurso'] ?? 0);
        if ($idCurso <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'Debe seleccionar un curso válido.');
        }

        $materiasSeleccionadas = $_POST['materias'] ?? [];
        $cargasHorarias = $_POST['cargaHoraria'] ?? []; // Arreglo asociativo [idMateria => valor]

        if (empty($materiasSeleccionadas)) {
            redirigirConMensaje($urlModulo, 'danger', 'Debe seleccionar al menos una materia para este curso.');
        }

        // Validar carga horaria por materia
        foreach ($materiasSeleccionadas as $idMateria) {
            $idMateria = (int) $idMateria;
            if ($idMateria <= 0) continue;

            $carga = isset($cargasHorarias[$idMateria]) && $cargasHorarias[$idMateria] !== '' ? (int) $cargasHorarias[$idMateria] : null;

            if ($carga === null || $carga < 1 || $carga > 20) {
                redirigirConMensaje($urlModulo, 'danger', 'La carga horaria de las materias seleccionadas debe estar entre 1 y 20 periodos.');
            }
        }

        $resultado = $modelo->sincronizarCurso($idCurso, $materiasSeleccionadas, $cargasHorarias);
        redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    // --------------------------------------------------------
    // ELIMINAR ASIGNACIÓN INDIVIDUAL (baja lógica)
    // --------------------------------------------------------
    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $id = (int) ($_POST['idMallaCurricular'] ?? 0);
        if ($id <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'ID de asignación no válido.');
        }

        $resultado = $modelo->eliminar($id);
        redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    // --------------------------------------------------------
    // ELIMINAR MALLA DE CURSO COMPLETO (baja lógica masiva por idCurso)
    // --------------------------------------------------------
    case 'eliminarCurso':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $idCurso = (int) ($_POST['idCurso'] ?? 0);
        if ($idCurso <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'ID de curso no válido.');
        }

        $resultado = $modelo->eliminarCurso($idCurso);
        redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    // --------------------------------------------------------
    // VISTA PRINCIPAL (Listar)
    // --------------------------------------------------------
    case 'index':
    default:
        $buscar   = trim($_GET['q'] ?? '');
        $grado    = trim($_GET['grado'] ?? '');
        $paralelo = trim($_GET['paralelo'] ?? '');
        $nivel    = trim($_GET['nivel'] ?? '');
        $turno    = trim($_GET['turno'] ?? '');
        $materia  = trim($_GET['materia'] ?? '');
        $docente  = trim($_GET['docente'] ?? '');
        
        // Obtener listados
        $mallas   = $modelo->listar($buscar, $grado, $paralelo, $nivel, $turno, $materia, $docente);
        $cursos   = $modelo->listarCursosActivos();
        $materias = $modelo->listarMateriasActivas();
        $docentes = $modelo->listarDocentesActivos();

        // Generar listados únicos para los combos de filtrado
        $cursosUnicos = [];
        $nivelesUnicos = [];
        $turnosUnicos = [];
        $paralelosUnicos = [];
        
        foreach ($cursos as $c) {
            if (!empty($c['nombre'])) {
                $cursosUnicos[$c['nombre']] = $c['nombre'];
            }
            if (!empty($c['nivel'])) {
                $nivelesUnicos[$c['idNivel']] = $c['nivel'];
            }
            if (!empty($c['turno'])) {
                $turnosUnicos[$c['idTurno']] = $c['turno'];
            }
            if (!empty($c['paralelo'])) {
                $paralelosUnicos[$c['idParalelo']] = $c['paralelo'];
            }
        }
        
        // Ordenar grados
        $ordenGrados = [
            'Primero' => 1, 'Segundo' => 2, 'Tercero' => 3,
            'Cuarto'  => 4, 'Quinto'  => 5, 'Sexto'   => 6
        ];
        uksort($cursosUnicos, function($a, $b) use ($ordenGrados) {
            $va = $ordenGrados[$a] ?? 99;
            $vb = $ordenGrados[$b] ?? 99;
            return $va <=> $vb;
        });

        // La vista espera una variable $cursoSeleccionado para saber qué pintó por defecto, en este caso no la estamos definiendo desde un GET específico en malla, pero por si acaso definimos:
        $cursoSeleccionado = null;

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vmalla_curricular.php';
        break;
}
