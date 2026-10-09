<?php
/**
 * ============================================================
 * trimestre.controller.php — Controlador del CRUD Trimestre
 * Sistema Académico JAEGASA
 * ============================================================
 */

require_once BASE_PATH . '/Model/mtrimestre.php';

if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion  = $_GET['a'] ?? 'index';
$modelo  = new ModeloTrimestre();
$urlModulo = BASE_URL . 'index.php?c=trimestre';

if (!function_exists('redirigirConMensaje')) {
    function redirigirConMensaje(string $urlModulo, string $tipo, string $mensaje): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: ' . $urlModulo);
        exit;
    }
}

switch ($accion) {
    case 'guardar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $idTrimestre     = (int) ($_POST['idTrimestre'] ?? 0);
        $idGestion       = (int) ($_POST['idGestion'] ?? 0);
        $numero          = (int) ($_POST['numero'] ?? 1);
        $nombre          = trim($_POST['nombre'] ?? '');
        $fechaInicio     = trim($_POST['fechaInicio'] ?? '');
        $fechaFin        = trim($_POST['fechaFin'] ?? '');
        $estadoTrimestre = trim($_POST['estadoTrimestre'] ?? 'Pendiente');
        $observacion     = trim($_POST['observacion'] ?? '');

        if ($fechaInicio !== '' && $fechaFin !== '' && $fechaInicio > $fechaFin) {
            redirigirConMensaje($urlModulo . "&idGestion={$idGestion}", 'danger', 'La fecha de inicio no puede ser posterior a la fecha de fin.');
        }

        $datos = [
            'idGestion'       => $idGestion,
            'numero'          => $numero,
            'nombre'          => $nombre,
            'fechaInicio'     => $fechaInicio,
            'fechaFin'        => $fechaFin,
            'estadoTrimestre' => $estadoTrimestre,
            'observacion'     => $observacion,
        ];

        if ($idTrimestre > 0) {
            $resultado = $modelo->actualizar($idTrimestre, $datos);
        } else {
            $resultado = $modelo->crear($datos);
        }

        $targetUrl = $urlModulo . ($idGestion > 0 ? "&idGestion={$idGestion}" : '');
        redirigirConMensaje($targetUrl, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    case 'inicializar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }
        $idGestion = (int) ($_POST['idGestion'] ?? 0);
        if ($idGestion <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'Gestión no válida.');
        }

        $resultado = $modelo->inicializarGestion($idGestion);
        redirigirConMensaje($urlModulo . "&idGestion={$idGestion}", $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }
        $idTrimestre = (int) ($_POST['idTrimestre'] ?? 0);
        $idGestion   = (int) ($_POST['idGestion'] ?? 0);

        $resultado = $modelo->eliminar($idTrimestre);
        $targetUrl = $urlModulo . ($idGestion > 0 ? "&idGestion={$idGestion}" : '');
        redirigirConMensaje($targetUrl, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    case 'index':
    default:
        $buscar     = trim($_GET['q'] ?? '');
        $gestiones  = $modelo->listarGestionesActivas();
        $gestionAct = $modelo->obtenerGestionActual();

        $idGestionSel = (int) ($_GET['idGestion'] ?? ($gestionAct['idGestion'] ?? 0));

        if ($idGestionSel > 0) {
            // Auto-inicializar 3 trimestres si la gestión no tiene trimestres registrados aún
            $existentes = $modelo->listarPorGestion($idGestionSel);
            if (empty($existentes) && empty($buscar)) {
                $modelo->inicializarGestion($idGestionSel);
            }
        }

        $trimestres = $modelo->listar($buscar, $idGestionSel);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vtrimestre.php';
        break;
}
