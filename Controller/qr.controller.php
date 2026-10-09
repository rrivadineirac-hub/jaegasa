<?php
/**
 * qr.controller.php — Controlador CRUD QR
 * Sistema Académico JAEGASA
 */

require_once BASE_PATH . '/Model/mqr.php';

if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloQr();
$urlModulo = BASE_URL . 'index.php?c=qr';

$filtrosParams = [];
foreach (['modoBusqueda', 'grado', 'paralelo', 'nivel', 'turno', 'q'] as $f) {
    if (!empty($_GET[$f])) {
        $filtrosParams[$f] = $_GET[$f];
    }
}
$urlFiltros = http_build_query($filtrosParams);
$urlFiltros = $urlFiltros ? '&' . $urlFiltros : '';
$urlRedirect = $urlModulo . $urlFiltros;

// Carpeta física donde se guardan las imágenes QR
const QR_DIR_RELATIVO = '/imagenes/qr/';

/**
 * Genera un nombre de archivo seguro para el QR de un estudiante.
 */
function nombreArchivoQr(int $idEstudiante, string $rude): string
{
    $rudeSeguro = preg_replace('/[^A-Za-z0-9\-]/', '', $rude);
    return 'qr_' . $idEstudiante . '_' . $rudeSeguro . '.png';
}

/**
 * Renderizador de salida PNG en PHP puro compatible con la interfaz de chillerlan/php-qrcode.
 */
class QRPurePhpPng extends \chillerlan\QRCode\Output\QROutputAbstract {

    public static function moduleValueIsValid(mixed $value): bool {
        return true;
    }

    protected function prepareModuleValue(mixed $value): mixed {
        return $value;
    }

    protected function getDefaultModuleValue(bool $isDark): mixed {
        return $isDark ? "\x00" : "\xFF";
    }

    public function dump(string|null $file = null): string {
        $matrix = $this->matrix->getMatrix();
        $moduleCount = count($matrix);
        $scale = $this->scale;
        $imgSize = $moduleCount * $scale;

        $raw = '';
        for ($y = 0; $y < $imgSize; $y++) {
            $raw .= "\x00"; // filter type 0
            $my = (int)($y / $scale);
            for ($x = 0; $x < $imgSize; $x++) {
                $mx = (int)($x / $scale);
                $val = $matrix[$my][$mx];
                $raw .= $this->matrix->isDark($val) ? "\x00" : "\xFF";
            }
        }

        $idat = gzcompress($raw);

        $packChunk = function($type, $data) {
            return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        };

        $png = "\x89PNG\r\n\x1a\n";
        $ihdr = pack('NNCCCCC', $imgSize, $imgSize, 8, 0, 0, 0, 0);
        $png .= $packChunk('IHDR', $ihdr);
        $png .= $packChunk('IDAT', $idat);
        $png .= $packChunk('IEND', '');

        $this->saveToFile($png, $file);

        return $png;
    }
}

/**
 * Crea físicamente el PNG del QR en imagenes/qr y devuelve el nombre de archivo.
 */
function generarArchivoQr(int $idEstudiante, string $rude): string
{
    $dir = BASE_PATH . QR_DIR_RELATIVO;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new \RuntimeException('No se pudo crear la carpeta de QR: ' . $dir);
    }
    if (!is_writable($dir)) {
        throw new \RuntimeException('La carpeta de QR no tiene permisos de escritura: ' . $dir);
    }

    $nombreArchivo = nombreArchivoQr($idEstudiante, $rude);
    $rutaCompleta  = $dir . $nombreArchivo;

    $options = new \chillerlan\QRCode\QROptions([
        'version'         => \chillerlan\QRCode\Common\Version::AUTO,
        'outputInterface' => (extension_loaded('gd') && class_exists(\chillerlan\QRCode\Output\QRGdImagePNG::class))
                            ? \chillerlan\QRCode\Output\QRGdImagePNG::class 
                            : QRPurePhpPng::class,
        'quality'         => 90,
        'scale'           => 10,
        'addQuietzone'    => true,
        'quietzoneSize'   => 4,
    ]);

    (new \chillerlan\QRCode\QRCode($options))->render($rude, $rutaCompleta);

    if (!is_file($rutaCompleta)) {
        throw new \RuntimeException('El archivo QR no se generó correctamente en: ' . $rutaCompleta);
    }

    return $nombreArchivo;
}

/**
 * Elimina el archivo físico del QR (si existe).
 */
function borrarArchivoQr(?string $nombreArchivo): void
{
    if (empty($nombreArchivo)) return;
    $ruta = BASE_PATH . QR_DIR_RELATIVO . $nombreArchivo;
    if (is_file($ruta)) {
        @unlink($ruta);
    }
}

switch ($accion) {

    // ---------------------------------------------------------------
    // CREAR: genera el QR solo si el estudiante todavía no tiene uno
    // ---------------------------------------------------------------
    case 'crear':
        $idEstudiante = (int) ($_GET['id'] ?? 0);
        $estudiante   = $modelo->obtenerEstudiante($idEstudiante);

        if (!$estudiante) {
            $_SESSION['flash'] = ['tipo' => 'danger', 'mensaje' => 'Estudiante no encontrado.'];
            header('Location: ' . $urlRedirect);
            exit;
        }
        if (empty($estudiante['rude'])) {
            $_SESSION['flash'] = ['tipo' => 'warning', 'mensaje' => 'El estudiante no tiene RUDE registrado.'];
            header('Location: ' . $urlRedirect);
            exit;
        }
        if (!empty($estudiante['qr']) && is_file(BASE_PATH . QR_DIR_RELATIVO . $estudiante['qr'])) {
            $_SESSION['flash'] = ['tipo' => 'info', 'mensaje' => 'Este estudiante ya tiene un QR generado.'];
            header('Location: ' . $urlRedirect);
            exit;
        }

        try {
            $nombreArchivo = generarArchivoQr($idEstudiante, $estudiante['rude']);
            $modelo->guardarQr($idEstudiante, $nombreArchivo);
            $_SESSION['flash'] = ['tipo' => 'success', 'mensaje' => 'QR generado correctamente.'];
        } catch (\Throwable $ex) {
            error_log('[QR CONTROLLER] crear: ' . $ex->getMessage());
            $_SESSION['flash'] = ['tipo' => 'danger', 'mensaje' => 'Error al generar el QR: ' . $ex->getMessage()];
        }
        header('Location: ' . $urlRedirect);
        exit;

    // ---------------------------------------------------------------
    // MODIFICAR (opcional, regenera)
    // ---------------------------------------------------------------
    case 'modificar':
        $idEstudiante = (int) ($_GET['id'] ?? 0);
        $estudiante   = $modelo->obtenerEstudiante($idEstudiante);

        if (!$estudiante) {
            $_SESSION['flash'] = ['tipo' => 'danger', 'mensaje' => 'Estudiante no encontrado.'];
            header('Location: ' . $urlRedirect);
            exit;
        }
        if (empty($estudiante['rude'])) {
            $_SESSION['flash'] = ['tipo' => 'warning', 'mensaje' => 'El estudiante no tiene RUDE registrado.'];
            header('Location: ' . $urlRedirect);
            exit;
        }

        try {
            borrarArchivoQr($estudiante['qr']);
            $nombreArchivo = generarArchivoQr($idEstudiante, $estudiante['rude']);
            $modelo->guardarQr($idEstudiante, $nombreArchivo);
            $_SESSION['flash'] = ['tipo' => 'success', 'mensaje' => 'QR regenerado correctamente.'];
        } catch (\Throwable $ex) {
            error_log('[QR CONTROLLER] modificar: ' . $ex->getMessage());
            $_SESSION['flash'] = ['tipo' => 'danger', 'mensaje' => 'Error al modificar el QR: ' . $ex->getMessage()];
        }
        header('Location: ' . $urlRedirect);
        exit;

    // ---------------------------------------------------------------
    // CREAR_CURSO: genera el QR masivo para todos los estudiantes del curso
    // ---------------------------------------------------------------
    case 'crear_curso':
        $grado    = trim($_GET['grado'] ?? '');
        $paralelo = trim($_GET['paralelo'] ?? '');
        $nivel    = trim($_GET['nivel'] ?? '');
        $turno    = trim($_GET['turno'] ?? '');

        if ($grado === '' && $paralelo === '' && $nivel === '' && $turno === '') {
            $_SESSION['flash'] = ['tipo' => 'warning', 'mensaje' => 'Debe seleccionar los filtros de un curso primero.'];
            header('Location: ' . $urlRedirect);
            exit;
        }

        $estudiantesCurso = $modelo->listarEstudiantes('', $grado, $paralelo, $nivel, $turno);
        $generados = 0;
        $errores   = 0;

        foreach ($estudiantesCurso as $est) {
            $idEst    = (int) $est['idEstudiante'];
            $rude     = $est['rude'] ?? '';
            $qrActual = $est['qr'] ?? '';

            if (empty($rude)) {
                continue; // omitir estudiantes sin RUDE registrado
            }

            $rutaQr = BASE_PATH . QR_DIR_RELATIVO . $qrActual;
            if (empty($qrActual) || !is_file($rutaQr)) {
                try {
                    $nombreArchivo = generarArchivoQr($idEst, $rude);
                    $modelo->guardarQr($idEst, $nombreArchivo);
                    $generados++;
                } catch (\Throwable $ex) {
                    error_log('[QR CONTROLLER] crear_curso: ' . $ex->getMessage());
                    $errores++;
                }
            }
        }

        if ($generados > 0) {
            $_SESSION['flash'] = ['tipo' => 'success', 'mensaje' => "Se generaron $generados códigos QR para los estudiantes del curso correctamente."];
        } else {
            $_SESSION['flash'] = ['tipo' => 'info', 'mensaje' => "Todos los estudiantes con RUDE de este curso ya tienen sus códigos QR generados."];
        }
        header('Location: ' . $urlRedirect);
        exit;

    // ---------------------------------------------------------------
    // PDF_CURSO: genera un PDF con credenciales/QR de todo el curso
    //            formateado a 9 credenciales por hoja tamaño Oficio
    // ---------------------------------------------------------------
    case 'pdf_curso':
        $grado    = trim($_GET['grado'] ?? '');
        $paralelo = trim($_GET['paralelo'] ?? '');
        $nivel    = trim($_GET['nivel'] ?? '');
        $turno    = trim($_GET['turno'] ?? '');

        if ($grado === '' && $paralelo === '' && $nivel === '' && $turno === '') {
            die('Debe seleccionar un curso para exportar las credenciales.');
        }

        $estudiantesCurso = $modelo->listarEstudiantes('', $grado, $paralelo, $nivel, $turno);

        // Filtrar estudiantes con RUDE
        $estudiantesValidos = array_values(array_filter($estudiantesCurso, fn($e) => !empty($e['rude'])));

        if (empty($estudiantesValidos)) {
            die('No hay estudiantes con RUDE registrado en el curso seleccionado.');
        }

        require_once BASE_PATH . '/vendor/setasign/fpdf/fpdf.php';

        // Dimensiones hoja Oficio (Legal): 215.9 mm ancho x 355.6 mm alto
        $pdf = new \FPDF('P', 'mm', [215.9, 355.6]);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);

        $fotoDefault = BASE_PATH . '/inagenes/Estudiante/default.png';

        // Parámetros de cuadrícula: 3 columnas x 3 filas = 9 por hoja
        $cardW  = 62;    // Ancho credencial (mm)
        $cardH  = 106;   // Alto credencial (mm)
        $gapX   = 4;     // Separación horizontal entre credenciales (mm)
        $gapY   = 7;     // Separación vertical entre credenciales (mm)
        $startX = 10.95; // Margen izquierdo inicial (centrado)
        $startY = 11.8;  // Margen superior inicial (centrado)

        foreach ($estudiantesValidos as $k => $est) {
            $idEst = (int) $est['idEstudiante'];
            $rude  = $est['rude'];

            // Verificar / generar el archivo de QR si hiciera falta
            $rutaQr = BASE_PATH . QR_DIR_RELATIVO . ($est['qr'] ?? '');
            if (empty($est['qr']) || !is_file($rutaQr)) {
                try {
                    $nombreArchivo = generarArchivoQr($idEst, $rude);
                    $modelo->guardarQr($idEst, $nombreArchivo);
                    $rutaQr = BASE_PATH . QR_DIR_RELATIVO . $nombreArchivo;
                } catch (\Throwable $ex) {
                    error_log('[QR CONTROLLER] pdf_curso error QR: ' . $ex->getMessage());
                    continue;
                }
            }

            // Calcular posición en la página (9 por hoja)
            $posEnPagina = $k % 9;
            if ($posEnPagina === 0) {
                $pdf->AddPage();
            }

            $col = $posEnPagina % 3;
            $row = (int) floor($posEnPagina / 3);

            $x = $startX + $col * ($cardW + $gapX);
            $y = $startY + $row * ($cardH + $gapY);

            // Borde exterior y fondo suave de la tarjeta credencial
            $pdf->SetDrawColor(29, 138, 181);
            $pdf->SetLineWidth(0.8);
            $pdf->Rect($x, $y, $cardW, $cardH, 'D');

            $pdf->SetFillColor(248, 250, 252);
            $pdf->Rect($x + 0.5, $y + 0.5, $cardW - 1, $cardH - 1, 'F');

            // Encabezado institucional azul
            $pdf->SetFillColor(29, 138, 181);
            $pdf->Rect($x + 0.5, $y + 0.5, $cardW - 1, 17.5, 'F');

            $pdf->SetXY($x, $y + 1.2);
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell($cardW, 4, utf8_decode('CREDENCIAL ACADÉMICA'), 0, 1, 'C');

            $pdf->SetXY($x + 0.5, $y + 6);
            $pdf->SetFont('Arial', 'B', 6.8);
            $pdf->MultiCell($cardW - 1, 3.6, utf8_decode('U.E. PROF. JAIME ALFONSO ESCALANTE GUTIÉRREZ "A"'), 0, 'C');

            // Foto del estudiante
            $fotoPath = BASE_PATH . '/inagenes/Estudiante/' . ($est['foto'] ?? '');
            if (empty($est['foto']) || !is_file($fotoPath)) {
                $fotoPath = $fotoDefault;
            }
            $photoW = 16;
            $photoH = 16;
            $photoX = $x + ($cardW - $photoW) / 2;
            $photoY = $y + 17.5;
            $pdf->Image($fotoPath, $photoX, $photoY, $photoW, $photoH);

            // Apellidos (Fila 1) y Nombres (Fila 2)
            $apellidos = trim(($est['apPaterno'] ?? '') . ' ' . ($est['apMaterno'] ?? ''));
            $nombres   = trim($est['nombre'] ?? '');

            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($x + 0.5, $y + 34.5);
            $pdf->Cell($cardW - 1, 3.5, utf8_decode(strtoupper($apellidos)), 0, 1, 'C');

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($x + 0.5, $y + 38.2);
            $pdf->Cell($cardW - 1, 3.5, utf8_decode(strtoupper($nombres)), 0, 1, 'C');

            // Información del Curso
            $cursoDesc = trim(($est['cursoNombre'] ?? '') . ' ' . ($est['cursoParalelo'] ?? '') . ' - ' . ($est['cursoNivel'] ?? ''));
            if (empty($cursoDesc)) {
                $cursoDesc = 'Sin inscribir';
            }
            $pdf->SetXY($x + 0.5, $y + 42.0);
            $pdf->SetFont('Arial', 'B', 7.5);
            $pdf->SetTextColor(29, 138, 181);
            $pdf->Cell($cardW - 1, 3.5, utf8_decode($cursoDesc), 0, 1, 'C');

            // Código QR del estudiante (MÁS GRANDE: 40x40 mm)
            $qrW = 40;
            $qrH = 40;
            $qrX = $x + ($cardW - $qrW) / 2;
            $qrY = $y + 46.0;
            $pdf->Image($rutaQr, $qrX, $qrY, $qrW, $qrH);

            // RUDE y C.I.
            $pdf->SetXY($x + 0.5, $y + 87.0);
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->Cell($cardW - 1, 4, utf8_decode('RUDE: ' . $rude), 0, 1, 'C');

            $pdf->SetXY($x + 0.5, $y + 91.5);
            $pdf->SetFont('Arial', '', 7);
            $pdf->SetTextColor(107, 114, 128);
            $gestionAnio = $est['cursoAnio'] ?? date('Y');
            $pdf->Cell($cardW - 1, 3.5, utf8_decode('C.I.: ' . ($est['ci'] ?: '—') . '  |  Gestión: ' . $gestionAnio), 0, 1, 'C');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Credenciales_QRs_Curso.pdf"');
        $pdf->Output('I', 'Credenciales_QRs_Curso.pdf');
        exit;

    // ---------------------------------------------------------------
    // PDF_SELECCION: genera PDF con credenciales/QR de los estudiantes seleccionados (3, 6 o 9 por hoja Oficio)
    // ---------------------------------------------------------------
    case 'pdf_seleccion':
        $idsStr = trim($_GET['ids'] ?? '');
        if (empty($idsStr)) {
            die('Debe seleccionar al menos 3, 6 o 9 estudiantes.');
        }

        $idsArray = array_values(array_filter(array_map('intval', explode(',', $idsStr)), fn($id) => $id > 0));
        if (empty($idsArray)) {
            die('No se enviaron IDs de estudiantes válidos.');
        }

        $estudiantesSel = $modelo->obtenerEstudiantesPorIds($idsArray);
        $estudiantesValidos = array_values(array_filter($estudiantesSel, fn($e) => !empty($e['rude'])));

        if (empty($estudiantesValidos)) {
            die('Los estudiantes seleccionados no tienen RUDE registrado.');
        }

        require_once BASE_PATH . '/vendor/setasign/fpdf/fpdf.php';

        // Dimensiones hoja Oficio (Legal): 215.9 mm ancho x 355.6 mm alto
        $pdf = new \FPDF('P', 'mm', [215.9, 355.6]);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);

        $fotoDefault = BASE_PATH . '/inagenes/Estudiante/default.png';

        // Parámetros de cuadrícula: 3 columnas x 3 filas = 9 por hoja
        $cardW  = 62;    // Ancho credencial (mm)
        $cardH  = 106;   // Alto credencial (mm)
        $gapX   = 4;     // Separación horizontal entre credenciales (mm)
        $gapY   = 7;     // Separación vertical entre credenciales (mm)
        $startX = 10.95; // Margen izquierdo inicial (centrado)
        $startY = 11.8;  // Margen superior inicial (centrado)

        foreach ($estudiantesValidos as $k => $est) {
            $idEst = (int) $est['idEstudiante'];
            $rude  = $est['rude'];

            // Verificar / generar el archivo de QR si hiciera falta
            $rutaQr = BASE_PATH . QR_DIR_RELATIVO . ($est['qr'] ?? '');
            if (empty($est['qr']) || !is_file($rutaQr)) {
                try {
                    $nombreArchivo = generarArchivoQr($idEst, $rude);
                    $modelo->guardarQr($idEst, $nombreArchivo);
                    $rutaQr = BASE_PATH . QR_DIR_RELATIVO . $nombreArchivo;
                } catch (\Throwable $ex) {
                    error_log('[QR CONTROLLER] pdf_seleccion error QR: ' . $ex->getMessage());
                    continue;
                }
            }

            // Calcular posición en la página (9 por hoja)
            $posEnPagina = $k % 9;
            if ($posEnPagina === 0) {
                $pdf->AddPage();
            }

            $col = $posEnPagina % 3;
            $row = (int) floor($posEnPagina / 3);

            $x = $startX + $col * ($cardW + $gapX);
            $y = $startY + $row * ($cardH + $gapY);

            // Borde exterior y fondo suave de la tarjeta credencial
            $pdf->SetDrawColor(29, 138, 181);
            $pdf->SetLineWidth(0.8);
            $pdf->Rect($x, $y, $cardW, $cardH, 'D');

            $pdf->SetFillColor(248, 250, 252);
            $pdf->Rect($x + 0.5, $y + 0.5, $cardW - 1, $cardH - 1, 'F');

            // Encabezado institucional azul
            $pdf->SetFillColor(29, 138, 181);
            $pdf->Rect($x + 0.5, $y + 0.5, $cardW - 1, 17.5, 'F');

            $pdf->SetXY($x, $y + 1.2);
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell($cardW, 4, utf8_decode('CREDENCIAL ACADÉMICA'), 0, 1, 'C');

            $pdf->SetXY($x + 0.5, $y + 6);
            $pdf->SetFont('Arial', 'B', 6.8);
            $pdf->MultiCell($cardW - 1, 3.6, utf8_decode('U.E. PROF. JAIME ALFONSO ESCALANTE GUTIÉRREZ "A"'), 0, 'C');

            // Foto del estudiante
            $fotoPath = BASE_PATH . '/inagenes/Estudiante/' . ($est['foto'] ?? '');
            if (empty($est['foto']) || !is_file($fotoPath)) {
                $fotoPath = $fotoDefault;
            }
            $photoW = 16;
            $photoH = 16;
            $photoX = $x + ($cardW - $photoW) / 2;
            $photoY = $y + 17.5;
            $pdf->Image($fotoPath, $photoX, $photoY, $photoW, $photoH);

            // Apellidos (Fila 1) y Nombres (Fila 2)
            $apellidos = trim(($est['apPaterno'] ?? '') . ' ' . ($est['apMaterno'] ?? ''));
            $nombres   = trim($est['nombre'] ?? '');

            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($x + 0.5, $y + 34.5);
            $pdf->Cell($cardW - 1, 3.5, utf8_decode(strtoupper($apellidos)), 0, 1, 'C');

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($x + 0.5, $y + 38.2);
            $pdf->Cell($cardW - 1, 3.5, utf8_decode(strtoupper($nombres)), 0, 1, 'C');

            // Información del Curso
            $cursoDesc = trim(($est['cursoNombre'] ?? '') . ' ' . ($est['cursoParalelo'] ?? '') . ' - ' . ($est['cursoNivel'] ?? ''));
            if (empty($cursoDesc)) {
                $cursoDesc = 'Sin inscribir';
            }
            $pdf->SetXY($x + 0.5, $y + 42.0);
            $pdf->SetFont('Arial', 'B', 7.5);
            $pdf->SetTextColor(29, 138, 181);
            $pdf->Cell($cardW - 1, 3.5, utf8_decode($cursoDesc), 0, 1, 'C');

            // Código QR del estudiante (MÁS GRANDE: 40x40 mm)
            $qrW = 40;
            $qrH = 40;
            $qrX = $x + ($cardW - $qrW) / 2;
            $qrY = $y + 46.0;
            $pdf->Image($rutaQr, $qrX, $qrY, $qrW, $qrH);

            // RUDE y C.I.
            $pdf->SetXY($x + 0.5, $y + 87.0);
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->Cell($cardW - 1, 4, utf8_decode('RUDE: ' . $rude), 0, 1, 'C');

            $pdf->SetXY($x + 0.5, $y + 91.5);
            $pdf->SetFont('Arial', '', 7);
            $pdf->SetTextColor(107, 114, 128);
            $gestionAnio = $est['cursoAnio'] ?? date('Y');
            $pdf->Cell($cardW - 1, 3.5, utf8_decode('C.I.: ' . ($est['ci'] ?: '—') . '  |  Gestión: ' . $gestionAnio), 0, 1, 'C');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Credenciales_Seleccionadas.pdf"');
        $pdf->Output('I', 'Credenciales_Seleccionadas.pdf');
        exit;

    // ---------------------------------------------------------------
    // ELIMINAR: borra el archivo físico y limpia el campo qr
    // ---------------------------------------------------------------
    case 'eliminar':
        $idEstudiante = (int) ($_GET['id'] ?? 0);
        $estudiante   = $modelo->obtenerEstudiante($idEstudiante);

        if (!$estudiante) {
            $_SESSION['flash'] = ['tipo' => 'danger', 'mensaje' => 'Estudiante no encontrado.'];
            header('Location: ' . $urlRedirect);
            exit;
        }

        borrarArchivoQr($estudiante['qr']);
        $modelo->limpiarQr($idEstudiante);
        $_SESSION['flash'] = ['tipo' => 'success', 'mensaje' => 'QR eliminado correctamente.'];
        header('Location: ' . $urlRedirect);
        exit;

    // ---------------------------------------------------------------
    // PDF: usa el QR ya guardado; si no existe, lo genera primero
    // ---------------------------------------------------------------
    case 'pdf':
        $idEstudiante = (int) ($_GET['id'] ?? 0);
        $estudiante   = $modelo->obtenerEstudiante($idEstudiante);
        if (!$estudiante) {
            die('Estudiante no encontrado o inactivo.');
        }
        if (empty($estudiante['rude'])) {
            die('El estudiante no tiene RUDE registrado.');
        }

        $rutaQr = BASE_PATH . QR_DIR_RELATIVO . ($estudiante['qr'] ?? '');
        if (empty($estudiante['qr']) || !is_file($rutaQr)) {
            try {
                $nombreArchivo = generarArchivoQr($idEstudiante, $estudiante['rude']);
                $modelo->guardarQr($idEstudiante, $nombreArchivo);
                $rutaQr = BASE_PATH . QR_DIR_RELATIVO . $nombreArchivo;
            } catch (\Throwable $ex) {
                error_log('[QR CONTROLLER] pdf: ' . $ex->getMessage());
                die('Error al generar el código QR: ' . htmlspecialchars($ex->getMessage()));
            }
        }

        require_once BASE_PATH . '/vendor/setasign/fpdf/fpdf.php';

        $pdf = new \FPDF('P', 'mm', [85, 125]);
        $pdf->AddPage();
        $pdf->SetMargins(5, 5, 5);
        $pdf->SetAutoPageBreak(false);

        $pdf->SetDrawColor(29, 138, 181);
        $pdf->SetLineWidth(1.2);
        $pdf->Rect(2, 2, 81, 121, 'D');

        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect(3, 3, 79, 119, 'F');

        $pdf->SetFillColor(29, 138, 181);
        $pdf->Rect(3, 3, 79, 21, 'F');

        $pdf->SetXY(5, 4.5);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(75, 6, utf8_decode('CREDENCIAL ACADÉMICA'), 0, 1, 'C');

        $pdf->SetXY(4, 11);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->MultiCell(77, 4, utf8_decode('U.E. PROF. JAIME ALFONSO ESCALANTE GUTIÉRREZ "A"'), 0, 'C');

        $fotoDefault = BASE_PATH . '/inagenes/Estudiante/default.png';
        $fotoPath = BASE_PATH . '/inagenes/Estudiante/' . $estudiante['foto'];
        if (empty($estudiante['foto']) || !is_file($fotoPath)) {
            $fotoPath = $fotoDefault;
        }

        $pdf->Image($fotoPath, 30.5, 24, 24, 24);

        // Apellidos (Fila 1) y Nombres (Fila 2)
        $apellidos = trim(($estudiante['apPaterno'] ?? '') . ' ' . ($estudiante['apMaterno'] ?? ''));
        $nombres   = trim($estudiante['nombre'] ?? '');

        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(4, 49.5);
        $pdf->Cell(77, 4.5, utf8_decode(strtoupper($apellidos)), 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(4, 54);
        $pdf->Cell(77, 4.5, utf8_decode(strtoupper($nombres)), 0, 1, 'C');

        $cursoDesc = trim(($estudiante['cursoNombre'] ?? '') . ' ' . ($estudiante['cursoParalelo'] ?? '') . ' - ' . ($estudiante['cursoNivel'] ?? ''));
        if (empty($cursoDesc)) {
            $cursoDesc = 'Sin inscribir';
        }
        $pdf->SetXY(4, 58.5);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(29, 138, 181);
        $pdf->Cell(77, 4, utf8_decode($cursoDesc), 0, 1, 'C');

        // QR MÁS GRANDE (48x48 mm)
        $pdf->Image($rutaQr, 18.5, 63.0, 48, 48);

        $pdf->SetXY(4, 112.0);
        $pdf->SetFont('Arial', 'B', 9.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->Cell(77, 4.5, utf8_decode('RUDE: ' . $estudiante['rude']), 0, 1, 'C');

        $pdf->SetXY(4, 117.0);
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(107, 114, 128);
        $gestionAnio = $estudiante['cursoAnio'] ?? date('Y');
        $pdf->Cell(77, 4, utf8_decode('C.I.: ' . ($estudiante['ci'] ?: '—') . '  |  Gestión Escolar: ' . $gestionAnio), 0, 1, 'C');

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Credencial_' . $estudiante['rude'] . '.pdf"');
        $pdf->Output('I', 'Credencial_' . $estudiante['rude'] . '.pdf');
        exit;

    case 'index':
    default:
        $buscar       = trim($_GET['q'] ?? '');
        $grado        = trim($_GET['grado'] ?? '');
        $paralelo     = trim($_GET['paralelo'] ?? '');
        $nivel        = trim($_GET['nivel'] ?? '');
        $turno        = trim($_GET['turno'] ?? '');
        $modoBusqueda = trim($_GET['modoBusqueda'] ?? 'estudiante');

        if ($modoBusqueda === 'estudiante') {
            $grado = '';
            $paralelo = '';
            $nivel = '';
            $turno = '';
        } else {
            $buscar = '';
        }

        $realizoBusqueda = ($buscar !== '' || $grado !== '' || $paralelo !== '' || $nivel !== '' || $turno !== '');
        $estudiantes = $modelo->listarEstudiantes($buscar, $grado, $paralelo, $nivel, $turno);
        $cursos = $modelo->listarCursosActivos();

        // Generar listados únicos para los combos
        $cursosUnicos    = [];
        $paralelosUnicos = [];
        $nivelesUnicos   = [];
        $turnosUnicos    = [];

        foreach ($cursos as $c) {
            $cursosUnicos[$c['cursoNombre']]   = $c['cursoNombre'];
            $paralelosUnicos[$c['idParalelo']] = $c['paraleloNombre'];
            $nivelesUnicos[$c['idNivel']]      = $c['nivelNombre'];
            $turnosUnicos[$c['idTurno']]       = $c['turnoNombre'];
        }

        // Orden alfabético (excepto curso que ya viene ordenado lógicamente)
        asort($paralelosUnicos);
        asort($nivelesUnicos);
        asort($turnosUnicos);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vqr.php';
        break;
}