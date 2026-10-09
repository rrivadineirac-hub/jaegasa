<?php
/**
 * ============================================================
 * direccion.controller.php  —  Controlador CRUD Dirección
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Sin AJAX: formularios POST tradicionales que llaman a los
 * stored procedures (InsertarDireccion / EditarDireccion /
 * EliminarDireccion) y redirigen con un mensaje flash.
 *
 * Acciones: index, guardar, eliminar
 * Solo accesible para el rol Administrador (idRoles = 1).
 * ============================================================
 */

require_once BASE_PATH . '/Model/mdireccion.php';

// Control de acceso por rol
if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloDireccion();

$urlModulo = BASE_URL . 'index.php?c=direccion';

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

        $id = (int) ($_POST['idDireccion'] ?? 0);
        $d  = [
            'barrio'       => trim($_POST['barrio']       ?? ''),
            'calle'        => trim($_POST['calle']        ?? ''),
            'numero'       => trim($_POST['numero']       ?? ''),
            'ubicacionGps' => trim($_POST['ubicacionGps'] ?? ''),
        ];
        $d = Seguridad::normalizarMayusculas($d);


        // Validación mínima: al menos barrio o calle
        if ($d['calle'] === '' && $d['barrio'] === '') {
            redirigirConMensaje($urlModulo, 'danger', 'Ingrese al menos barrio o calle.');
        }

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
        $id = (int) ($_POST['idDireccion'] ?? 0);
        $resultado = $modelo->eliminar($id);
        redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    // --------------------------------------------------------
    // VISTA PRINCIPAL (lista)
    // --------------------------------------------------------
    case 'index':
    default:
        require_once BASE_PATH . '/Model/mcurso.php';

        $mCurso = new ModeloCurso();
        $cursos = $mCurso->listar();

        $modoBusqueda = trim($_GET['modoBusqueda'] ?? 'estudiante');
        $buscar   = trim($_GET['q'] ?? '');
        $grado    = trim($_GET['grado'] ?? '');
        $paralelo = trim($_GET['paralelo'] ?? '');
        $nivel    = trim($_GET['nivel'] ?? '');
        $turno    = trim($_GET['turno'] ?? '');

        // Limpiar filtros según el modo de búsqueda seleccionado
        if ($modoBusqueda === 'estudiante') {
            $grado = $paralelo = $nivel = $turno = '';
        } else if ($modoBusqueda === 'curso') {
            $buscar = '';
        }

        // Control de estado vacío al ingresar:
        // Cargar lista de estudiantes para permitir búsqueda instantánea JS desde la primera letra tipeada
        $realizoBusqueda = ($buscar !== '' || $grado !== '' || $paralelo !== '' || $nivel !== '' || $turno !== '');
        $estudiantes = $modelo->listarEstudiantesConDireccion($buscar, $grado, $paralelo, $nivel, $turno);

        // Mantener $direcciones para modales si fuera necesario
        $direcciones = $modelo->listar('');

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vdireccion.php';
        break;
}
