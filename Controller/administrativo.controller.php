<?php
/**
 * ============================================================
 * administrativo.controller.php  —  Controlador CRUD Administrativo
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Sin AJAX: usa formularios POST tradicionales que llaman a los
 * stored procedures y redirigen a la lista con un mensaje flash.
 *
 * Acciones:
 *   index     -> lista (con búsqueda y modales)
 *   guardar   -> crea o actualiza (POST) y redirige
 *   eliminar  -> baja lógica (POST) y redirige
 *
 * Solo accesible para el rol Administrador (idRoles = 1).
 * ============================================================
 */

require_once BASE_PATH . '/Model/madministrativo.php';

// Control de acceso por rol
if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloAdministrativo();

// URL base del módulo (para redirigir)
$urlModulo = BASE_URL . 'index.php?c=administrativo';

/**
 * Guarda un mensaje flash en sesión y redirige a la lista.
 */
function redirigirConMensaje(string $urlModulo, string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    header('Location: ' . $urlModulo);
    exit;
}

/**
 * Valida y guarda una imagen subida en inagenes/Admi/.
 * @return array ['ok'=>bool, 'nombre'=>string] | ['ok'=>false, 'mensaje'=>string]
 */
function guardarFotoAdministrativo(array $file): array
{
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $maxBytes   = 2 * 1024 * 1024; // 2 MB

    if ($file['size'] > $maxBytes) {
        return ['ok' => false, 'mensaje' => 'La imagen supera el tamaño máximo de 2 MB.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($permitidos[$mime])) {
        return ['ok' => false, 'mensaje' => 'Formato de imagen no permitido. Use JPG, PNG o WEBP.'];
    }

    $ext        = $permitidos[$mime];
    $nombre     = 'admin_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $destinoDir = BASE_PATH . '/inagenes/Admi/';

    if (!is_dir($destinoDir) && !mkdir($destinoDir, 0775, true)) {
        return ['ok' => false, 'mensaje' => 'No se pudo crear el directorio de imágenes.'];
    }

    if (!move_uploaded_file($file['tmp_name'], $destinoDir . $nombre)) {
        return ['ok' => false, 'mensaje' => 'No se pudo guardar la imagen en el servidor.'];
    }

    return ['ok' => true, 'nombre' => $nombre];
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
        $admin = $modelo->obtenerPorCi($ci);
        if ($admin) {
            echo json_encode(['ok' => true, 'encontrado' => true, 'administrativo' => $admin]);
        } else {
            echo json_encode(['ok' => true, 'encontrado' => false, 'mensaje' => 'Administrativo no encontrado.']);
        }
        exit;

    // --------------------------------------------------------
    // GUARDAR (crear o actualizar)
    // --------------------------------------------------------
    case 'guardar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $id = (int) ($_POST['idAdministrativo'] ?? 0);
        $d  = [
            'usuario'      => trim($_POST['usuario']      ?? ''),
            'password'     => (string) ($_POST['password'] ?? ''),
            'apPaterno'    => trim($_POST['apPaterno']    ?? ''),
            'apMaterno'    => trim($_POST['apMaterno']    ?? ''),
            'nombre'       => trim($_POST['nombre']       ?? ''),
            'ci'           => trim($_POST['ci']           ?? ''),
            'nitem'        => trim($_POST['nitem']        ?? ''),
            'rda'          => trim($_POST['rda']          ?? ''),
            'cargo'        => trim($_POST['cargo']        ?? ''),
            'categoria'    => trim($_POST['categoria']    ?? ''),
            'genero'       => trim($_POST['genero']       ?? ''),
            'fechaNac'     => trim($_POST['fechaNac']     ?? ''),
            'correo'       => trim($_POST['correo']       ?? ''),
            'telefono'     => trim($_POST['telefono']     ?? ''),
            'direccion'    => trim($_POST['direccion']    ?? ''),
            'nacionalidad' => trim($_POST['nacionalidad'] ?? ''),
            'departamento' => trim($_POST['departamento'] ?? ''),
            'provincia'    => trim($_POST['provincia']    ?? ''),
        ];
        $d = Seguridad::normalizarMayusculas($d, ['password']);


        $passAutoGenerada = '';
        if ($id === 0) {
            // Usuario de acceso automático = correo electrónico
            if ($d['correo'] !== '') {
                $d['usuario'] = $d['correo'];
            }
            // Generar contraseña aleatoria segura automáticamente que cumple todas las reglas
            $passAutoGenerada = Seguridad::generarPasswordSegura(10);
            $d['password']    = $passAutoGenerada;
        }

        // Validaciones obligatorias
        $errores = [];
        if ($d['apPaterno'] === '') $errores[] = 'El apellido paterno es obligatorio.';
        if ($d['apMaterno'] === '') $errores[] = 'El apellido materno es obligatorio.';
        if ($d['nombre']    === '') $errores[] = 'El nombre es obligatorio.';
        if ($d['ci']        === '') $errores[] = 'El CI es obligatorio.';
        if ($id === 0 && $d['correo'] === '') $errores[] = 'El correo electrónico es obligatorio para generar el usuario de acceso.';

        // Validación de contraseña segura (al crear o si se modifica)
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
                $d['usuario']  = $actual['usuario'];
                $d['password'] = ''; // No se permite modificar contraseña al editar
            }
            $exceptoUsuario = (int) ($actual['idUsuario'] ?? 0);
        }
        if ($modelo->usuarioEnUso($d['usuario'], $exceptoUsuario)) {
            redirigirConMensaje($urlModulo, 'danger', 'El correo / usuario ya está registrado en el sistema.');
        }

        // Foto (opcional)
        $d['foto'] = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $rf = guardarFotoAdministrativo($_FILES['foto']);
            if (!$rf['ok']) {
                redirigirConMensaje($urlModulo, 'danger', $rf['mensaje']);
            }
            $d['foto'] = $rf['nombre'];
        }

        // Crear o actualizar
        if ($id === 0) {
            $resultado = $modelo->crear($d);
            if ($resultado['ok']) {
                $urlWa = Seguridad::generarUrlWhatsApp(
                    $d['telefono'],
                    trim($d['nombre'] . ' ' . $d['apPaterno']),
                    $d['usuario'],
                    $passAutoGenerada,
                    'Administrativo'
                );
                $_SESSION['flash'] = [
                    'tipo'    => 'success',
                    'mensaje' => 'Administrativo registrado. Usuario: ' . $d['usuario'] . ' | Contraseña: ' . $passAutoGenerada,
                    'wa_url'  => $urlWa,
                ];
                header('Location: ' . $urlModulo);
                exit;
            } else {
                redirigirConMensaje($urlModulo, 'danger', $resultado['mensaje']);
            }
        } else {
            $resultado = $modelo->actualizar($id, $d);
            redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        }
        break;

    // --------------------------------------------------------
    // ELIMINAR (baja lógica)
    // --------------------------------------------------------
    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }
        $id = (int) ($_POST['idAdministrativo'] ?? 0);
        $resultado = $modelo->eliminar($id);
        redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    // --------------------------------------------------------
    // VISTA PRINCIPAL (lista)
    // --------------------------------------------------------
    case 'index':
    default:
        $buscar          = trim($_GET['q'] ?? '');
        $administrativos = $modelo->listar($buscar);
        $categorias      = $modelo->listarCategorias();

        // Ordenar categorías según escalafón: Quinta, Cuarta, Tercera, Segunda, Primera, Cero, Mérito
        $ordenCategorias = [
            'quinta'  => 1,
            'cuarta'  => 2,
            'tercera' => 3,
            'segunda' => 4,
            'primera' => 5,
            'cero'    => 6,
            'merito'  => 7,
            'mérito'  => 7
        ];
        usort($categorias, function ($a, $b) use ($ordenCategorias) {
            $nomA = mb_strtolower(trim($a['nombre'] ?? ''));
            $nomB = mb_strtolower(trim($b['nombre'] ?? ''));

            $posA = 99;
            $posB = 99;

            foreach ($ordenCategorias as $clave => $pos) {
                if (strpos($nomA, $clave) !== false) {
                    $posA = $pos;
                    break;
                }
            }

            foreach ($ordenCategorias as $clave => $pos) {
                if (strpos($nomB, $clave) !== false) {
                    $posB = $pos;
                    break;
                }
            }

            if ($posA === $posB) {
                return strcasecmp($nomA, $nomB);
            }
            return $posA <=> $posB;
        });

        // Mensaje flash (si existe)
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vadministrativo.php';
        break;
}
