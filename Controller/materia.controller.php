<?php
/**
 * ============================================================
 * materia.controller.php  —  Controlador CRUD Materia
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Gestiona el catálogo de materias de la institución.
 * Acciones: index, guardar, eliminar
 * Solo accesible para el Administrador (rol 1).
 * ============================================================
 */

require_once BASE_PATH . '/Model/mmateria.php';

// Control de acceso por rol (1 = Administrador)
if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloMateria();

$urlModulo = BASE_URL . 'index.php?c=materia';

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

        $id     = (int) ($_POST['idMateria'] ?? 0);
        $nombre = Seguridad::mayuscula($_POST['nombre'] ?? '');
        $sigla  = Seguridad::mayuscula($_POST['sigla'] ?? '');


        // Validaciones básicas
        if ($nombre === '') {
            redirigirConMensaje($urlModulo, 'danger', 'El nombre de la materia es obligatorio.');
        }

        // Validación de duplicado
        if ($modelo->nombreEnUso($nombre, $id)) {
            redirigirConMensaje($urlModulo, 'danger', 'Ya existe una materia registrada con el nombre "' . $nombre . '".');
        }

        $datos = [
            'nombre' => $nombre,
            'sigla'  => $sigla !== '' ? $sigla : null
        ];

        // Crear o actualizar
        $resultado = $id > 0 ? $modelo->actualizar($id, $datos) : $modelo->crear($datos);
        redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    // --------------------------------------------------------
    // ELIMINAR (baja lógica)
    // --------------------------------------------------------
    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $id = (int) ($_POST['idMateria'] ?? 0);
        if ($id <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'ID de materia no válido.');
        }

        $resultado = $modelo->eliminar($id);
        redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    // --------------------------------------------------------
    // VISTA PRINCIPAL (Listar)
    // --------------------------------------------------------
    case 'index':
    default:
        $buscar   = trim($_GET['q'] ?? '');
        $materias = $modelo->listar($buscar);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vmateria.php';
        break;
}
