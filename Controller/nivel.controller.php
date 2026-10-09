<?php
/**
 * nivel.controller.php — Controlador CRUD Nivel
 */

require_once BASE_PATH . '/Model/mnivel.php';

if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloNivel();
$urlModulo = BASE_URL . 'index.php?c=nivel';

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
        $id = (int) ($_POST['idNivel'] ?? 0);
        $d = [
            'nombre'     => Seguridad::mayuscula($_POST['nombre'] ?? ''),
            'horaInicio' => trim($_POST['horaInicio'] ?? ''),
            'horaFin'    => trim($_POST['horaFin']    ?? ''),
        ];

        if ($d['nombre'] === '') redirigirConMensaje($urlModulo, 'danger', 'El nombre del nivel es obligatorio.');
        $r = $id > 0 ? $modelo->actualizar($id, $d) : $modelo->crear($d);
        redirigirConMensaje($urlModulo, $r['ok'] ? 'success' : 'danger', $r['mensaje']);
        break;

    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        $r = $modelo->eliminar((int) ($_POST['idNivel'] ?? 0));
        redirigirConMensaje($urlModulo, $r['ok'] ? 'success' : 'danger', $r['mensaje']);
        break;

    case 'index':
    default:
        $buscar  = trim($_GET['q'] ?? '');
        $niveles = $modelo->listar($buscar);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require_once BASE_PATH . '/View/vnivel.php';
        break;
}
