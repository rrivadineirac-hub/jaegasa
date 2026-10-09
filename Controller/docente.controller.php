<?php
/**
 * ============================================================
 * docente.controller.php  —  Controlador CRUD Docente
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

require_once BASE_PATH . '/Model/mdocente.php';

// Control de acceso por rol
if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloDocente();

// URL base del módulo (para redirigir)
$urlModulo = BASE_URL . 'index.php?c=docente';

/**
 * Guarda un mensaje flash en sesión y redirige a la lista.
 */
if (!function_exists('redirigirConMensaje')) {
    function redirigirConMensaje(string $urlModulo, string $tipo, string $mensaje): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: ' . $urlModulo);
        exit;
    }
}

/**
 * Valida y guarda una imagen subida en inagenes/Docente/.
 * @return array ['ok'=>bool, 'nombre'=>string] | ['ok'=>false, 'mensaje'=>string]
 */
function guardarFotoDocente(array $file): array
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
    $nombre     = 'docente_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $destinoDir = BASE_PATH . '/inagenes/Docente/';

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
        $docente = $modelo->obtenerPorCi($ci);
        if ($docente) {
            echo json_encode(['ok' => true, 'encontrado' => true, 'docente' => $docente]);
        } else {
            echo json_encode(['ok' => true, 'encontrado' => false, 'mensaje' => 'Docente no encontrado.']);
        }
        exit;

    // --------------------------------------------------------
    // EXPORTAR LISTADO DE DOCENTES EN PDF (FPDF)
    // --------------------------------------------------------
    case 'exportar_pdf':
        $docentes = $modelo->listar('');

        if (!class_exists('FPDF')) {
            require_once BASE_PATH . '/vendor/autoload.php';
        }

        class PDF_Docentes extends FPDF {
            public string $gestionActual = '';

            function Header() {
                $this->SetFont('Arial', 'B', 14);
                $this->SetTextColor(29, 138, 181);
                $this->Cell(0, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'UNIDAD EDUCATIVA JAIME ESCALANTE GUTIÉRREZ'), 0, 1, 'C');

                $this->SetFont('Arial', 'B', 11);
                $this->SetTextColor(80, 80, 80);
                $this->Cell(0, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'LISTADO OFICIAL DE DOCENTES REGISTRADOS'), 0, 1, 'C');

                $this->SetFont('Arial', '', 9);
                $this->SetTextColor(120, 120, 120);
                $gestion = $this->gestionActual ?: date('Y');
                $fecha   = date('d/m/Y H:i');
                $this->Cell(0, 5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', "Gestión Escolar: {$gestion}   |   Fecha de emisión: {$fecha}"), 0, 1, 'C');
                $this->Ln(4);

                // Encabezados de la tabla (Ancho total: 190 mm)
                $this->SetFillColor(71, 85, 105);
                $this->SetTextColor(255, 255, 255);
                $this->SetDrawColor(148, 163, 184);
                $this->SetFont('Arial', 'B', 9);

                $this->Cell(10, 7, '#', 1, 0, 'C', true);
                $this->Cell(80, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Nombre Completo'), 1, 0, 'L', true);
                $this->Cell(30, 7, 'C.I.', 1, 0, 'C', true);
                $this->Cell(40, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Categoría'), 1, 0, 'C', true);
                $this->Cell(30, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'N.º de Ítem'), 1, 1, 'C', true);
            }

            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial', 'I', 8);
                $this->SetTextColor(150, 150, 150);
                $this->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Sistema Académico JAEGASA — Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
            }
        }

        $pdf = new PDF_Docentes('P', 'mm', 'A4');
        $pdf->gestionActual = (string) ($_SESSION['gestion'] ?? date('Y'));
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetDrawColor(220, 225, 230);

        if (empty($docentes)) {
            $pdf->Cell(190, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'No hay docentes registrados.'), 1, 1, 'C');
        } else {
            $fill = false;
            foreach ($docentes as $i => $d) {
                $nombreComp = trim(($d['apPaterno'] ?? '') . ' ' . ($d['apMaterno'] ?? '') . ' ' . ($d['nombre'] ?? ''));

                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 247 : 255, $fill ? 250 : 255);
                $pdf->SetTextColor(30, 41, 59);
                $pdf->Cell(10, 6.5, $i + 1, 1, 0, 'C', true);
                $pdf->Cell(80, 6.5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $nombreComp), 1, 0, 'L', true);
                $pdf->Cell(30, 6.5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $d['ci'] ?? ''), 1, 0, 'C', true);
                $pdf->Cell(40, 6.5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $d['categoria'] ?: '—'), 1, 0, 'C', true);
                $pdf->Cell(30, 6.5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $d['numeroItem'] ?: '—'), 1, 1, 'C', true);
                $fill = !$fill;
            }
        }

        $pdf->Output('I', 'Listado_Docentes_JAEGASA.pdf');
        exit;

    // --------------------------------------------------------
    // GUARDAR (crear o actualizar)
    // --------------------------------------------------------
    case 'guardar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $id = (int) ($_POST['idDocente'] ?? 0);
        $d  = [
            'usuario'      => trim($_POST['usuario']      ?? ''),
            'password'     => (string) ($_POST['password'] ?? ''),
            'apPaterno'    => trim($_POST['apPaterno']    ?? ''),
            'apMaterno'    => trim($_POST['apMaterno']    ?? ''),
            'nombre'       => trim($_POST['nombre']       ?? ''),
            'ci'           => trim($_POST['ci']           ?? ''),
            'numeroItem'   => trim($_POST['numeroItem']   ?? ''),
            'rda'          => trim($_POST['rda']          ?? ''),
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
            $rf = guardarFotoDocente($_FILES['foto']);
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
                    'Docente'
                );
                $_SESSION['flash'] = [
                    'tipo'    => 'success',
                    'mensaje' => 'Docente registrado. Usuario: ' . $d['usuario'] . ' | Contraseña: ' . $passAutoGenerada,
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
        $id = (int) ($_POST['idDocente'] ?? 0);
        $resultado = $modelo->eliminar($id);
        redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    // --------------------------------------------------------
    // VISTA PRINCIPAL (lista)
    // --------------------------------------------------------
    case 'index':
    default:
        $buscar     = trim($_GET['q'] ?? '');
        $docentes   = $modelo->listar($buscar);
        $categorias = $modelo->listarCategorias();

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

        require_once BASE_PATH . '/View/vdocente.php';
        break;
}
