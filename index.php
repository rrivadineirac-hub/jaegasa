<?php
/**
 * ============================================================
 * index.php  —  Front Controller / Router
 * Sistema Academic JAEGARS
 * ------------------------------------------------------------
 * Punto de entrada único. Inicia la sesión y enruta la
 * petición hacia el controlador correspondiente.
 *
 * Uso:  index.php?c=<controlador>&a=<accion>
 *   c = controlador  (login | dashboard)   por defecto: login
 *   a = acción       (index | autenticar | salir | ...)
 * ============================================================
 */

date_default_timezone_set('America/La_Paz');
require_once __DIR__ . '/vendor/autoload.php';

// ------------------------------------------------------------
// Configuración de la cookie de sesión (ANTES de iniciarla)
//   lifetime = 0  -> la cookie se borra al cerrar el navegador
//   httponly      -> no accesible desde JavaScript
//   samesite Lax  -> protección básica CSRF
// ------------------------------------------------------------
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Constantes de rutas base
define('BASE_PATH', __DIR__);
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/');

// ------------------------------------------------------------
// Expiración de la sesión por inactividad (comportamiento estándar)
//   Si pasan más de SESSION_TIMEOUT segundos sin actividad, la
//   sesión se cierra y se vuelve a exigir el login.
//   (1800 s = 30 minutos. Cambie el valor a gusto.)
// ------------------------------------------------------------
define('SESSION_TIMEOUT', 1800);

if (isset($_SESSION['idUsuario'])) {
    $ahora = time();
    if (isset($_SESSION['ultimaActividad'])
        && ($ahora - $_SESSION['ultimaActividad']) > SESSION_TIMEOUT) {
        // Sesión vencida: destruirla por completo y avisar en el login
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . 'index.php?c=login&sesion=expirada');
        exit;
    }
    $_SESSION['ultimaActividad'] = $ahora;
}

require_once BASE_PATH . '/Config/Conexion.php';
require_once BASE_PATH . '/Config/Seguridad.php';

// ------------------------------------------------------------
// Parámetros de enrutamiento
// ------------------------------------------------------------
$controlador = $_GET['c'] ?? 'login';
$accion      = $_GET['a'] ?? 'index';

// Lista blanca de controladores permitidos (evita inclusión arbitraria)
$controladoresPermitidos = [
    'login'          => 'Controller/login.controller.php',
    'dashboard'      => 'Controller/dashboard.controller.php',
    'administrativo' => 'Controller/administrativo.controller.php',
    'docente'        => 'Controller/docente.controller.php',
    'categoria'      => 'Controller/categoria.controller.php',
    'estudiante'     => 'Controller/estudiante.controller.php',
    'tutor'          => 'Controller/tutor.controller.php',
    'direccion'      => 'Controller/direccion.controller.php',
    'nivel'          => 'Controller/nivel.controller.php',
    'turno'          => 'Controller/turno.controller.php',
    'titulo'         => 'Controller/titulo.controller.php',
    'paralelo'       => 'Controller/paralelo.controller.php',
    'gestion'        => 'Controller/gestion.controller.php',
    'horario'        => 'Controller/horario.controller.php',
    'aula'           => 'Controller/aula.controller.php',
    'asistencia'     => 'Controller/asistencia.controller.php',
    'asistencia_docente'     => 'Controller/asistencia_docente.controller.php',
    'especialidad'   => 'Controller/especialidad.controller.php',
    'dificultad'     => 'Controller/dificultad.controller.php',
    'discapacidad'   => 'Controller/discapacidad.controller.php',
    'condicion'      => 'Controller/condicion.controller.php',
    'inscripcion'    => 'Controller/inscripcion.controller.php',
    'curso'          => 'Controller/curso.controller.php',
    'qr'             => 'Controller/qr.controller.php',
    'malla_curricular' => 'Controller/malla_curricular.controller.php',
    'materia'          => 'Controller/materia.controller.php',
    'asignacion_docente' => 'Controller/asignacion_docente.controller.php',
    'importar_estudiantes' => 'Controller/importar_estudiantes.controller.php',
    'trimestre'      => 'Controller/trimestre.controller.php',
];

// ------------------------------------------------------------
// Control de acceso: rutas que NO requieren sesión iniciada
// ------------------------------------------------------------
$rutasPublicas = ['login'];

$sesionActiva = isset($_SESSION['idUsuario']);
$esPeticionApi = (
    $accion === 'listar_json' ||
    $accion === 'listar_cursos_json' ||
    $accion === 'listar_cursos' ||
    $accion === 'listar' ||
    $accion === 'guardar_lote' ||
    $accion === 'guardar_json' ||
    $accion === 'listar_hoy' ||
    $accion === 'obtener_estados' ||
    $accion === 'registrarQr' ||
    $accion === 'verificarRude' ||
    isset($_GET['op']) ||
    (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'json')) ||
    (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'json'))
);

if (!in_array($controlador, $rutasPublicas, true) && !$sesionActiva && !$esPeticionApi) {
    // Sin sesión intentando acceder a ruta protegida -> vuelve al login
    header('Location: ' . BASE_URL . 'index.php?c=login');
    exit;
}

// Si ya hay sesión y pide el login (sin acción especial), lo mando al dashboard
if ($controlador === 'login' && $accion === 'index' && $sesionActiva) {
    header('Location: ' . BASE_URL . 'index.php?c=dashboard');
    exit;
}

// ------------------------------------------------------------
// Despacho al controlador
// ------------------------------------------------------------
if (!isset($controladoresPermitidos[$controlador])) {
    http_response_code(404);
    die('Recurso no encontrado.');
}

require_once BASE_PATH . '/' . $controladoresPermitidos[$controlador];
