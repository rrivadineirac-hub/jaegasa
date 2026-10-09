<?php
/**
 * condicion.controller.php — Controlador CRUD Condición Médica
 */

require_once BASE_PATH . '/Model/mcondicion.php';

if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloCondicion();
$urlModulo = BASE_URL . 'index.php?c=condicion';

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
        $id = (int) ($_POST['idCondicion'] ?? 0);
        $d = ['condicion' => Seguridad::mayuscula($_POST['condicion'] ?? '')];

        if ($d['condicion'] === '') redirigirConMensaje($urlModulo, 'danger', 'La condición médica es obligatoria.');
        $r = $id > 0 ? $modelo->actualizar($id, $d) : $modelo->crear($d);
        redirigirConMensaje($urlModulo, $r['ok'] ? 'success' : 'danger', $r['mensaje']);
        break;

    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        $r = $modelo->eliminar((int) ($_POST['idCondicion'] ?? 0));
        redirigirConMensaje($urlModulo, $r['ok'] ? 'success' : 'danger', $r['mensaje']);
        break;

    case 'index':
    default:
        $buscar     = trim($_GET['q'] ?? '');
        $condiciones = $modelo->listar($buscar);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require_once BASE_PATH . '/View/vcondicion.php';
        break;
}
