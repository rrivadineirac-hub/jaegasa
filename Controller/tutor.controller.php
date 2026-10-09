<?php
/**
 * ============================================================
 * tutor.controller.php  —  Controlador CRUD Tutor
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Sin AJAX: formularios POST tradicionales que llaman a los
 * stored procedures (InsertarTutor / EditarTutor / EliminarTutor)
 * y redirigen a la lista con un mensaje flash.
 *
 * Acciones: index, guardar, eliminar
 * Solo accesible para el rol Administrador (idRoles = 1).
 * ============================================================
 */

require_once BASE_PATH . '/Model/mtutor.php';

// Control de acceso por rol
if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloTutor();

$urlModulo = BASE_URL . 'index.php?c=tutor';

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
    // BUSCAR POR CI (AJAX)
    // --------------------------------------------------------
    case 'buscar_ci':
        header('Content-Type: application/json; charset=utf-8');
        $ci = trim($_GET['ci'] ?? '');
        if ($ci === '') {
            echo json_encode(['ok' => false, 'mensaje' => 'CI no proporcionado.']);
            exit;
        }
        $tutor = $modelo->obtenerPorCi($ci);
        if ($tutor) {
            echo json_encode(['ok' => true, 'encontrado' => true, 'tutor' => $tutor]);
        } else {
            echo json_encode(['ok' => true, 'encontrado' => false, 'mensaje' => 'Tutor no encontrado']);
        }
        exit;

    // --------------------------------------------------------
    // GUARDAR (crear o actualizar)
    // --------------------------------------------------------
    case 'guardar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $id = (int) ($_POST['idTutor'] ?? 0);
        $d  = [
            'apPaterno'   => trim($_POST['apPaterno']   ?? ''),
            'apMaterno'   => trim($_POST['apMaterno']   ?? ''),
            'nombre'      => trim($_POST['nombre']      ?? ''),
            'ci'          => trim($_POST['ci']          ?? ''),
            'telefono'    => trim($_POST['telefono']    ?? ''),
            'correo'      => trim($_POST['correo']      ?? ''),
            'parentesco'  => trim($_POST['parentesco']  ?? ''),
            'tipo'        => trim($_POST['tipo']        ?? ''),
            'direccion'   => trim($_POST['direccion']   ?? ''),
            'profesion'   => trim($_POST['profesion']   ?? ''),
            'ocupacion'   => trim($_POST['ocupacion']   ?? ''),
            'usuario'     => trim($_POST['usuario']     ?? ''),
            'password'    => (string) ($_POST['password'] ?? ''),
        ];
        $d = Seguridad::normalizarMayusculas($d, ['password']);


        // Validaciones obligatorias
        $errores = [];
        if ($d['apPaterno'] === '') $errores[] = 'El apellido paterno es obligatorio.';
        if ($d['apMaterno'] === '') $errores[] = 'El apellido materno es obligatorio.';
        if ($d['nombre']    === '') $errores[] = 'El nombre es obligatorio.';
        if ($d['ci']        === '') $errores[] = 'El CI es obligatorio.';
        // Si se indica usuario en un registro nuevo, exige contraseña
        if ($id === 0 && $d['usuario'] !== '' && $d['password'] === '') {
            $errores[] = 'Si define un usuario debe indicar una contraseña.';
        }

        // Validación de contraseña segura (si se asigna o modifica contraseña)
        if ($d['password'] !== '') {
            $resPass = Seguridad::validarPasswordSegura($d['password']);
            if (!$resPass['ok']) {
                $errores = array_merge($errores, $resPass['errores']);
            }
        }

        if ($errores) {
            redirigirConMensaje($urlModulo, 'danger', implode(' ', $errores));
        }

        // Duplicados
        if ($modelo->ciEnUso($d['ci'], $id)) {
            redirigirConMensaje($urlModulo, 'danger', 'El CI ingresado ya está registrado.');
        }

        $exceptoUsuario = 0;
        if ($id > 0) {
            $actual = $modelo->obtener($id);
            if ($actual) {
                $d['usuario']  = $actual['usuario'] ?? '';
                $d['password'] = ''; // No se permite modificar contraseña al editar
            }
            $exceptoUsuario = (int) ($actual['idUsuario'] ?? 0);
        }
        if ($d['usuario'] !== '' && $modelo->usuarioEnUso($d['usuario'], $exceptoUsuario)) {
            redirigirConMensaje($urlModulo, 'danger', 'El nombre de usuario ya está en uso.');
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
        $id = (int) ($_POST['idTutor'] ?? 0);
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

        $buscar   = trim($_GET['q'] ?? '');
        $grado    = trim($_GET['grado'] ?? '');
        $paralelo = trim($_GET['paralelo'] ?? '');
        $nivel    = trim($_GET['nivel'] ?? '');
        $turno    = trim($_GET['turno'] ?? '');

        // Control de estado vacio al ingresar:
        // Solo realizar la consulta si se envió al menos un filtro de búsqueda
        $realizoBusqueda = ($buscar !== '' || $grado !== '' || $paralelo !== '' || $nivel !== '' || $turno !== '');
        $estudiantes = $realizoBusqueda ? $modelo->listarEstudiantesConTutores($buscar, $grado, $paralelo, $nivel, $turno) : [];

        // Mantener $tutores para modales
        $tutores = $modelo->listar('');

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vtutor.php';
        break;
}
