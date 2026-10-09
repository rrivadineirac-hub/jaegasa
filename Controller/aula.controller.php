<?php
/**
 * ============================================================
 * aula.controller.php  —  Controlador CRUD Aula
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Sin AJAX: Formularios POST tradicionales que interactúan
 * con el modelo ModeloAula y redirigen con mensajes flash.
 *
 * Acciones: index, guardar, eliminar
 * Solo accesible para el rol Administrador (idRoles = 1).
 * ============================================================
 */

require_once BASE_PATH . '/Model/maula.php';

// Control de acceso por rol
if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloAula();

$urlModulo = BASE_URL . 'index.php?c=aula';

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

        $id        = (int) ($_POST['idAula'] ?? 0);
        $idDocente = isset($_POST['idDocente']) && $_POST['idDocente'] !== '' ? (int) $_POST['idDocente'] : null;
        $idTurno   = isset($_POST['idTurno']) && $_POST['idTurno'] !== '' ? (int) $_POST['idTurno'] : null;
        
        $d  = [
            'nombre'      => Seguridad::mayuscula($_POST['nombre'] ?? ''),
            'idDocente'   => $idDocente,
            'idTurno'     => $idTurno,
            'observacion' => Seguridad::mayuscula($_POST['observacion'] ?? '')
        ];


        // Validación
        if ($d['nombre'] === '') {
            redirigirConMensaje($urlModulo, 'danger', 'El nombre del aula es obligatorio.');
        }

        // Duplicado
        if ($modelo->nombreEnUso($d['nombre'], $id)) {
            redirigirConMensaje($urlModulo, 'danger', 'Ya existe un aula con ese nombre.');
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
        $id = (int) ($_POST['idAula'] ?? 0);
        $resultado = $modelo->eliminar($id);
        redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    // --------------------------------------------------------
    // VISTA PRINCIPAL (lista)
    // --------------------------------------------------------
    case 'index':
    default:
        $buscar = trim($_GET['q'] ?? '');
        $aulas  = $modelo->listar($buscar);
        
        $docentes = $modelo->listarDocentesActivos();
        $turnos   = $modelo->listarTurnosActivos();

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vaula.php';
        break;
}
