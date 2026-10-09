<?php
/**
 * ============================================================
 * importar_estudiantes.controller.php  —  Controlador de Importación Excel por Curso
 * Sistema Académico JAEGASA
 * ============================================================
 */

require_once BASE_PATH . '/Model/mimportar_estudiantes.php';

// Control de acceso por rol: Solo Administrador (idRoles = 1)
if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloImportarEstudiantes();
$urlModulo = BASE_URL . 'index.php?c=importar_estudiantes';

switch ($accion) {

    // --------------------------------------------------------
    // Descargar plantilla CSV/Excel estructurada
    // --------------------------------------------------------
    case 'descargar_plantilla':
        $idCurso = (int) ($_GET['idCurso'] ?? 0);
        $cursoInfo = null;
        $nombreArchivo = 'Plantilla_Estudiantes_JAEGASA.csv';
        $estudiantesDB = [];

        if ($idCurso > 0) {
            $cursoInfo = $modelo->obtenerCursoInfo($idCurso);
            if ($cursoInfo) {
                // Formatear nombre dinámico del archivo según el curso seleccionado
                $gradoClean    = preg_replace('/[^A-Za-z0-9]/', '', $cursoInfo['cursoNombre'] ?? 'Curso');
                $paraleloClean = preg_replace('/[^A-Za-z0-9]/', '', $cursoInfo['paraleloNombre'] ?? 'A');
                $nivelClean    = preg_replace('/[^A-Za-z0-9]/', '', $cursoInfo['nivelNombre'] ?? 'Nivel');
                $gestionClean  = preg_replace('/[^A-Za-z0-9]/', '', (string) ($cursoInfo['gestionAnio'] ?? '2026'));
                
                $nombreArchivo = "Plantilla_Estudiantes_{$gradoClean}_{$paraleloClean}_{$nivelClean}_{$gestionClean}.csv";
                $estudiantesDB = $modelo->listarEstudiantesCurso($idCurso);
            }
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        
        // Output UTF-8 BOM as the absolute first bytes for Microsoft Excel special characters (ñ, acentos, N°)
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');

        
        // Encabezados en el orden exacto de la planilla del colegio
        fputcsv($output, [
            'N°',
            'RUDE',
            'CI',
            'APELLIDO PATERNO',
            'APELLIDO MATERNO',
            'NOMBRE',
            'GENERO(M/F)',
            'FECHANACIMIENTO'
        ], ';');

        $numFila = 1;
        if (!empty($estudiantesDB)) {
            // Pre-llenar con los estudiantes actualmente inscritos en el curso seleccionado
            foreach ($estudiantesDB as $est) {
                fputcsv($output, [
                    (string) $numFila,
                    $est['rude'] ?? '',
                    $est['ci'] ?? '',
                    $est['apPaterno'] ?? '',
                    $est['apMaterno'] ?? '',
                    $est['nombre'] ?? '',
                    $est['genero'] ?? '',
                    $est['fechaNacimiento'] ?? ''
                ], ';');
                $numFila++;
            }
        } else {
            // Filas de ejemplo si el curso está vacío
            fputcsv($output, ['1', '80560001202301A', '12345678', 'AGUILERA', 'AUZA', 'JOSE CARLOS', 'M', '2010-05-14'], ';');
            fputcsv($output, ['2', '80560001202302B', '12345679', 'ANDRADE', 'CALLIZAYA', 'MARIA VICTORIA', 'F', '2010-08-22'], ';');
            fputcsv($output, ['3', '80560001202303C', '12345680', 'ANTEZANA', 'SAAVEDRA', 'ADRIAN MOISES', 'M', '2010-02-10'], ';');
            fputcsv($output, ['4', '80560001202304D', '12345681', 'BANEGAS', 'ROCHA', 'LUIS AQUILES', 'M', '2010-11-05'], ';');
            fputcsv($output, ['5', '80560001202305E', '12345682', 'BONILLA', 'TEODOVICH', 'AARON', 'M', '2010-04-18'], ';');
            $numFila = 6;
        }

        // Rellenar filas numeradas vacías hasta completar al menos 36 filas
        $limiteFilas = max(36, $numFila - 1);
        for ($i = $numFila; $i <= $limiteFilas; $i++) {
            fputcsv($output, [(string)$i, '', '', '', '', '', '', ''], ';');
        }

        fclose($output);
        exit;






    // --------------------------------------------------------
    // Validar datos recibidos por AJAX antes de guardar
    // --------------------------------------------------------
    case 'validar_datos':
        header('Content-Type: application/json; charset=utf-8');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        $filas = $data['estudiantes'] ?? [];
        if (empty($filas)) {
            echo json_encode(['ok' => false, 'mensaje' => 'No se recibieron filas para validar.']);
            exit;
        }

        $cis = [];
        $rudes = [];
        foreach ($filas as $f) {
            if (!empty($f['ci'])) $cis[] = trim($f['ci']);
            if (!empty($f['rude'])) $rudes[] = trim($f['rude']);
        }

        $duplicadosBD = $modelo->verificarDuplicados($cis, $rudes);

        // Map validation status to each row
        $resultado = [];
        $conteo = ['total' => count($filas), 'validos' => 0, 'duplicados' => 0, 'incompletos' => 0];

        foreach ($filas as $idx => $f) {
            $f['index'] = $idx + 1;
            $nombre = trim($f['nombre'] ?? '');
            $apPat  = trim($f['apPaterno'] ?? '');
            $apMat  = trim($f['apMaterno'] ?? '');
            $ci     = trim($f['ci'] ?? '');
            $rude   = trim($f['rude'] ?? '');

            $f['estado'] = 'VALIDO';
            $f['observacion'] = 'Listo para importar';

            if ($nombre === '' || ($apPat === '' && $apMat === '')) {
                $f['estado'] = 'INCOMPLETO';
                $f['observacion'] = 'Falta nombre o apellidos';
                $conteo['incompletos']++;
            } elseif (($ci !== '' && isset($duplicadosBD['cis'][$ci])) || ($rude !== '' && isset($duplicadosBD['rudes'][$rude]))) {
                $f['estado'] = 'DUPLICADO';
                $f['observacion'] = 'Ya existe en BD (se asignará al curso)';
                $conteo['duplicados']++;
            } else {
                $conteo['validos']++;
            }

            $resultado[] = $f;
        }

        echo json_encode(['ok' => true, 'conteo' => $conteo, 'filas' => $resultado]);
        exit;

    // --------------------------------------------------------
    // Procesar importación masiva por AJAX
    // --------------------------------------------------------
    case 'guardar_importacion':
        header('Content-Type: application/json; charset=utf-8');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        $idCurso = (int) ($data['idCurso'] ?? 0);
        $estudiantes = $data['estudiantes'] ?? [];

        if ($idCurso <= 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'Debe seleccionar un curso de destino.']);
            exit;
        }

        if (empty($estudiantes)) {
            echo json_encode(['ok' => false, 'mensaje' => 'No hay estudiantes para importar.']);
            exit;
        }

        $idUsuario = (int) ($_SESSION['idUsuario'] ?? 0);
        $idAdministrativo = $modelo->obtenerIdAdministrativo($idUsuario);

        $res = $modelo->procesarImportacionLote($idCurso, $idAdministrativo, $estudiantes);

        echo json_encode([
            'ok'         => true,
            'mensaje'    => "Proceso completado: {$res['insertados']} estudiantes creados/actualizados, {$res['inscritos']} inscritos en el curso.",
            'resumen'    => $res,
            'redirect'   => $urlModulo . '&idCurso=' . $idCurso
        ]);
        exit;

    // --------------------------------------------------------
    // Listar estudiantes de un curso por AJAX (para refresco en cliente)
    // --------------------------------------------------------
    case 'obtener_estudiantes_curso':
        header('Content-Type: application/json; charset=utf-8');
        $idCurso = (int) ($_GET['idCurso'] ?? 0);
        $buscar  = trim($_GET['buscar'] ?? '');

        if ($idCurso <= 0) {
            echo json_encode(['ok' => true, 'estudiantes' => []]);
            exit;
        }

        $lista = $modelo->listarEstudiantesCurso($idCurso, $buscar);
        echo json_encode(['ok' => true, 'estudiantes' => $lista]);
        exit;

    // --------------------------------------------------------
    // Eliminar inscripción de un estudiante en un curso
    // --------------------------------------------------------
    case 'eliminar_estudiante_curso':
        $idInscripcion = (int) ($_GET['idInscripcion'] ?? 0);
        $idCurso       = (int) ($_GET['idCurso'] ?? 0);

        if ($idInscripcion > 0) {
            $ok = $modelo->eliminarInscripcion($idInscripcion);
            if ($ok) {
                $_SESSION['flash'] = ['tipo' => 'exito', 'mensaje' => 'Estudiante quitado del curso correctamente.'];
            } else {
                $_SESSION['flash'] = ['tipo' => 'error', 'mensaje' => 'No se pudo quitar al estudiante del curso.'];
            }
        }
        header('Location: ' . $urlModulo . ($idCurso > 0 ? '&idCurso=' . $idCurso : ''));
        exit;

    // --------------------------------------------------------
    // Vista Principal
    // --------------------------------------------------------
    case 'index':
    default:
        $cursos = $modelo->listarCursos();
        $idCursoSeleccionado = (int) ($_GET['idCurso'] ?? 0);
        
        $cursoInfo = null;
        $estudiantesCurso = [];
        if ($idCursoSeleccionado > 0) {
            $cursoInfo = $modelo->obtenerCursoInfo($idCursoSeleccionado);
            $estudiantesCurso = $modelo->listarEstudiantesCurso($idCursoSeleccionado);
        }

        require_once BASE_PATH . '/View/vimportar_estudiantes.php';
        break;
}
