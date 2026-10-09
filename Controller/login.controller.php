<?php
/**
 * ============================================================
 * login.controller.php  —  Controlador de Login
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Acciones:
 *   index       -> muestra el formulario de acceso
 *   autenticar  -> valida credenciales (respuesta JSON, vía AJAX)
 *   salir       -> cierra la sesión
 * ============================================================
 */

require_once BASE_PATH . '/Model/mlogin.php';

$accion = $_GET['a'] ?? 'index';

switch ($accion) {

    // --------------------------------------------------------
    // Procesar autenticación (AJAX -> JSON)
    // --------------------------------------------------------
    case 'autenticar':

        ini_set('display_errors', '0');
        error_reporting(E_ALL);
        header('Content-Type: application/json; charset=utf-8');
        ob_start();

        // Recorta texto de forma segura, exista o no la extensión mbstring.
        $recortar = function (string $txt, int $n): string {
            return function_exists('mb_substr')
                ? mb_substr($txt, 0, $n)
                : substr($txt, 0, $n);
        };

        // Envía JSON limpio, adjuntando cualquier "ruido" capturado como debug.
        $responder = function (array $payload) use ($recortar) {
            $ruido = ob_get_length() !== false ? ob_get_clean() : '';
            if (is_string($ruido) && trim($ruido) !== '') {
                error_log('[LOGIN] Salida inesperada antes del JSON: ' . $ruido);
                $payload['debug'] = $recortar(trim($ruido), 600);
            }
            echo json_encode($payload);
            exit;
        };

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $responder(['ok' => false, 'mensaje' => 'Método no permitido.']);
            }

            $ahora = time();
            $bloqueadoHasta = (int) ($_SESSION['login_bloqueado_hasta'] ?? 0);

            // 1. Verificar si el usuario está bloqueado por 10 minutos (600 s)
            if ($bloqueadoHasta > $ahora) {
                $segundosRestantes = $bloqueadoHasta - $ahora;
                $minutosRestantes  = (int) ceil($segundosRestantes / 60);
                $responder([
                    'ok'      => false,
                    'mensaje' => "Acceso bloqueado por 6 intentos fallidos. Por favor espere {$minutosRestantes} minuto(s) para reintentar.",
                ]);
            }

            // Si el tiempo de bloqueo ya expiró, limpiar estado
            if ($bloqueadoHasta > 0 && $bloqueadoHasta <= $ahora) {
                unset($_SESSION['login_bloqueado_hasta'], $_SESSION['login_intentos']);
            }

            $nombre   = trim($_POST['usuario']  ?? '');
            $password = $_POST['password'] ?? '';

            if ($nombre === '' || $password === '') {
                $responder(['ok' => false, 'mensaje' => 'Complete usuario y contraseña.']);
            }

            $modelo  = new ModeloLogin();
            $usuario = $modelo->validar($nombre, $password);

            // 2. Si falla la autenticación, incrementar contador de fallos
            if ($usuario === null) {
                $intentos = (int) ($_SESSION['login_intentos'] ?? 0) + 1;
                $_SESSION['login_intentos'] = $intentos;

                if ($intentos >= 6) {
                    $_SESSION['login_bloqueado_hasta'] = $ahora + 600; // 10 minutos
                    unset($_SESSION['login_intentos']);
                    $responder([
                        'ok'      => false,
                        'mensaje' => 'Ha superado el límite de 6 intentos fallidos. Su acceso ha sido bloqueado por 10 minutos.',
                    ]);
                } else {
                    $restantes = 6 - $intentos;
                    $responder([
                        'ok'      => false,
                        'mensaje' => "Usuario o contraseña incorrectos. Le quedan {$restantes} intento(s) antes del bloqueo.",
                    ]);
                }
            }

            // 3. Credenciales correctas: limpiar contador de fallos y bloqueo
            unset($_SESSION['login_intentos'], $_SESSION['login_bloqueado_hasta']);

            // Regenerar el id de sesión para prevenir fijación de sesión
            session_regenerate_id(true);

            $_SESSION['idUsuario']       = (int) $usuario['idUsuario'];
            $_SESSION['nombre']          = $usuario['nombre'];
            $_SESSION['idRoles']         = (int) $usuario['idRoles'];
            $_SESSION['rol']             = $usuario['rol'];
            $_SESSION['ultimaActividad'] = time();

            // Datos de perfil (nombre completo, foto y cargo), si existen
            $perfil = $modelo->perfil((int) $usuario['idUsuario']);
            $_SESSION['nombreCompleto'] = $perfil['nombreCompleto'] ?? $usuario['nombre'];
            $_SESSION['foto']           = $perfil['foto'] ?? null;
            $_SESSION['cargo']          = !empty($perfil['cargo']) ? $perfil['cargo'] : $usuario['rol'];

            $responder([
                'ok'       => true,
                'mensaje'  => 'Acceso correcto. Redirigiendo...',
                'redirect' => BASE_URL . 'index.php?c=dashboard',
            ]);

        } catch (Throwable $e) {
            // Cualquier error fatal se convierte en un JSON legible (no un 500)
            error_log('[LOGIN] Error: ' . $e->getMessage() . ' @ '
                     . $e->getFile() . ':' . $e->getLine());
            $ruido = (ob_get_length() !== false) ? ob_get_clean() : '';
            echo json_encode([
                'ok'      => false,
                'mensaje' => 'Error interno del servidor.',
                'debug'   => $recortar($e->getMessage() . ' @ '
                           . basename($e->getFile()) . ':' . $e->getLine()
                           . (trim((string) $ruido) !== '' ? ' | ' . trim((string) $ruido) : ''), 600),
            ]);
            exit;
        }

    // --------------------------------------------------------
    // Cerrar sesión
    // --------------------------------------------------------
    case 'salir':
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Location: ' . BASE_URL . 'index.php?c=login');
        exit;

    // --------------------------------------------------------
    // Mostrar formulario de login (por defecto)
    // --------------------------------------------------------
    case 'index':
    default:
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        require_once BASE_PATH . '/View/vlogin.php';
        break;
}
