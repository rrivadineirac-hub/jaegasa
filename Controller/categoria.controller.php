<?php
/**
 * ============================================================
 * categoria.controller.php  —  Controlador CRUD Categoría
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Sin AJAX: formularios POST tradicionales que llaman a los
 * stored procedures y redirigen a la lista con un mensaje flash.
 *
 * Acciones: index, guardar, eliminar
 * Solo accesible para el rol Administrador (idRoles = 1).
 * ============================================================
 */

require_once BASE_PATH . '/Model/mcategoria.php';

// Control de acceso por rol
if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloCategoria();

$urlModulo = BASE_URL . 'index.php?c=categoria';

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

        $id = (int) ($_POST['idCategoria'] ?? 0);
        $d  = ['nombre' => Seguridad::mayuscula($_POST['nombre'] ?? '')];


        // Validación
        if ($d['nombre'] === '') {
            redirigirConMensaje($urlModulo, 'danger', 'El nombre de la categoría es obligatorio.');
        }

        // Duplicado
        if ($modelo->nombreEnUso($d['nombre'], $id)) {
            redirigirConMensaje($urlModulo, 'danger', 'Ya existe una categoría con ese nombre.');
        }

        // Crear o actualizar
        $resultado = $id > 0 ? $modelo->actualizar($id, $d) : $modelo->crear($d);
        redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    // --------------------------------------------------------
    // ELIMINAR (baja lógica)
    // --------------------------------------------------------
    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }
        $id = (int) ($_POST['idCategoria'] ?? 0);
        $resultado = $modelo->eliminar($id);
        redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    // --------------------------------------------------------
    // VISTA PRINCIPAL (lista)
    // --------------------------------------------------------
    case 'index':
    default:
        $buscar     = trim($_GET['q'] ?? '');
        $categorias = $modelo->listar($buscar);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vcategoria.php';
        break;
}
