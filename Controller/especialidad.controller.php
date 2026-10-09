<?php
/**
 * especialidad.controller.php — Controlador CRUD Especialidad
 */

require_once BASE_PATH . '/Model/mespecialidad.php';

if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloEspecialidad();
$urlModulo = BASE_URL . 'index.php?c=especialidad';

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
        $id = (int) ($_POST['idEspecialidad'] ?? 0);
        $d = [
            'nombre'    => trim($_POST['nombre']    ?? ''),
            'categoria' => trim($_POST['categoria'] ?? ''),
        ];
        $d = Seguridad::normalizarMayusculas($d);

        if ($d['nombre'] === '') redirigirConMensaje($urlModulo, 'danger', 'El nombre de la especialidad es obligatorio.');
        $r = $id > 0 ? $modelo->actualizar($id, $d) : $modelo->crear($d);
        redirigirConMensaje($urlModulo, $r['ok'] ? 'success' : 'danger', $r['mensaje']);
        break;

    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        $r = $modelo->eliminar((int) ($_POST['idEspecialidad'] ?? 0));
        redirigirConMensaje($urlModulo, $r['ok'] ? 'success' : 'danger', $r['mensaje']);
        break;

    case 'index':
    default:
        $buscar        = trim($_GET['q'] ?? '');
        $especialidades = $modelo->listar($buscar);
        $categorias    = $modelo->listarCategorias();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require_once BASE_PATH . '/View/vespecialidad.php';
        break;
}
