<?php
/**
 * dificultad.controller.php — Controlador CRUD Dificultad de Aprendizaje
 */

require_once BASE_PATH . '/Model/mdificultad.php';

if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloDificultad();
$urlModulo = BASE_URL . 'index.php?c=dificultad';

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
        $id = (int) ($_POST['idDificultad'] ?? 0);
        $d = [
            'tipoDificultad' => trim($_POST['tipoDificultad'] ?? ''),
            'grado'          => trim($_POST['grado']          ?? ''),
        ];
        $d = Seguridad::normalizarMayusculas($d);

        if ($d['tipoDificultad'] === '') redirigirConMensaje($urlModulo, 'danger', 'El tipo de dificultad es obligatorio.');
        $r = $id > 0 ? $modelo->actualizar($id, $d) : $modelo->crear($d);
        redirigirConMensaje($urlModulo, $r['ok'] ? 'success' : 'danger', $r['mensaje']);
        break;

    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        $r = $modelo->eliminar((int) ($_POST['idDificultad'] ?? 0));
        redirigirConMensaje($urlModulo, $r['ok'] ? 'success' : 'danger', $r['mensaje']);
        break;

    case 'index':
    default:
        $buscar       = trim($_GET['q'] ?? '');
        $dificultades = $modelo->listar($buscar);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require_once BASE_PATH . '/View/vdificultad.php';
        break;
}
