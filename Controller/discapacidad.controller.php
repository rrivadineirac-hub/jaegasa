<?php
/**
 * discapacidad.controller.php — Controlador CRUD Discapacidad
 */

require_once BASE_PATH . '/Model/mdiscapacidad.php';

if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloDiscapacidad();
$urlModulo = BASE_URL . 'index.php?c=discapacidad';

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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        $id = (int) ($_POST['idDiscapacidad'] ?? 0);
        $d = ['tipoDiscapacidad' => Seguridad::mayuscula($_POST['tipoDiscapacidad'] ?? '')];

        if ($d['tipoDiscapacidad'] === '') redirigirConMensaje($urlModulo, 'danger', 'El tipo de discapacidad es obligatorio.');
        $r = $id > 0 ? $modelo->actualizar($id, $d) : $modelo->crear($d);
        redirigirConMensaje($urlModulo, $r['ok'] ? 'success' : 'danger', $r['mensaje']);
        break;

    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        $r = $modelo->eliminar((int) ($_POST['idDiscapacidad'] ?? 0));
        redirigirConMensaje($urlModulo, $r['ok'] ? 'success' : 'danger', $r['mensaje']);
        break;

    case 'index':
    default:
        $buscar         = trim($_GET['q'] ?? '');
        $discapacidades = $modelo->listar($buscar);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require_once BASE_PATH . '/View/vdiscapacidad.php';
        break;
}
