<?php
/**
 * ============================================================
 * horario.controller.php  —  Controlador del Planificador de Horarios
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Permite gestionar la planificación horaria de los diferentes
 * cursos con validaciones y mapeo para vista de cuadrícula.
 * ============================================================
 */

require_once BASE_PATH . '/Model/mhorario.php';

$accion = $_GET['a'] ?? $_GET['op'] ?? 'index';

// Control de acceso por rol (solo Administrador para acciones de edición)
if ((int) ($_SESSION['idRoles'] ?? 0) !== 1 && !in_array($accion, ['listar_json', 'listar'], true)) {
    http_response_code(403);
    die('Acceso denegado.');
}

$modelo = new ModeloHorario();

$urlModulo = BASE_URL . 'index.php?c=horario';

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
    // API JSON HORARIOS POR CURSO
    // --------------------------------------------------------
    case 'listar_json':
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        $cursoParam = $_GET['curso'] ?? '';
        $idCurso = (int) ($_GET['idCurso'] ?? 0);

        $cursos = $modelo->listarCursos();

        if ($idCurso === 0 && !empty($cursoParam)) {
            $normParam = preg_replace('/[^a-z0-9]/', '', str_replace(
                ['1ro', '2do', '3ro', '4to', '5to', '6to', 'á', 'é', 'í', 'ó', 'ú'],
                ['primero', 'segundo', 'tercero', 'cuarto', 'quinto', 'sexto', 'a', 'e', 'i', 'o', 'u'],
                mb_strtolower($cursoParam, 'UTF-8')
            ));

            foreach ($cursos as $c) {
                if ((string)$c['idCurso'] === $cursoParam) {
                    $idCurso = (int)$c['idCurso'];
                    break;
                }

                $noms = [
                    $c['nombreCurso'] ?? '',
                    $c['descripcionCompleta'] ?? '',
                    ($c['nombreCurso'] ?? '') . ' ' . ($c['nivel'] ?? '') . ' ' . ($c['paralelo'] ?? ''),
                    ($c['nivel'] ?? '') . ' ' . ($c['paralelo'] ?? ''),
                    ($c['nombreCurso'] ?? '') . ' ' . ($c['paralelo'] ?? ''),
                ];

                foreach ($noms as $nom) {
                    $normNom = preg_replace('/[^a-z0-9]/', '', str_replace(
                        ['1ro', '2do', '3ro', '4to', '5to', '6to', 'á', 'é', 'í', 'ó', 'ú'],
                        ['primero', 'segundo', 'tercero', 'cuarto', 'quinto', 'sexto', 'a', 'e', 'i', 'o', 'u'],
                        mb_strtolower($nom, 'UTF-8')
                    ));

                    if ($normNom === $normParam && !empty($normNom)) {
                        $idCurso = (int)$c['idCurso'];
                        break 2;
                    }
                }
            }
        }

        if ($idCurso === 0) {
            $idCurso = 1; // Default to Primero Secundaria A
        }

        $rawHorarios = $idCurso > 0 ? $modelo->obtenerHorarioCurso($idCurso) : [];
        $periodos = $modelo->listarPeriodos();

        $periodosMap = [];
        foreach ($periodos as $per) {
            $periodosMap[(int) $per['idPeriodo']] = $per;
        }

        $diasColors = [
            'Lunes'     => '#b5c71a',
            'Martes'    => '#8b5cf6',
            'Miercoles' => '#eab308',
            'Miércoles' => '#eab308',
            'Jueves'    => '#06b6d4',
            'Viernes'   => '#f43f5e',
        ];

        $resultado = [];
        foreach ($rawHorarios as $h) {
            $perId = (int) $h['idPeriodo'];
            $per = $periodosMap[$perId] ?? null;
            $dia = $h['diaSemana'];

            $siglaStr = !empty($h['materiaSigla']) ? ' (' . $h['materiaSigla'] . ')' : '';

            $resultado[] = [
                'id'          => (string) ($h['idHorarioCurso'] ?? ''),
                'curso'       => $cursoParam ?: ($c['nombreCurso'] ?? ''),
                'dia'         => $dia,
                'hora_inicio' => $per ? date('H:i', strtotime($per['horaInicio'])) : '07:30',
                'hora_fin'    => $per ? date('H:i', strtotime($per['horaFin'])) : '08:10',
                'materia'     => trim(($h['materiaNombre'] ?? '') . $siglaStr),
                'docente'     => $h['docenteNombre'] ?: 'Sin Docente',
                'aula'        => $h['aula'] ?: 'Sin Aula',
                'color_hex'   => $diasColors[$dia] ?? '#0F4C81',
            ];
        }

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        exit;

    // --------------------------------------------------------
    // GUARDAR O ACTUALIZAR CELDA HORARIA
    // --------------------------------------------------------
    case 'guardar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $idCurso   = (int) ($_POST['idCurso'] ?? 0);
        $diaSemana = Seguridad::mayuscula($_POST['diaSemana'] ?? '');
        $idPeriodo = (int) ($_POST['idPeriodo'] ?? 0);
        $idMateria = (int) ($_POST['idMateria'] ?? 0);
        $idDocente = (int) ($_POST['idDocente'] ?? 0);
        $aula      = Seguridad::mayuscula($_POST['aula'] ?? '');


        if ($idCurso === 0 || $diaSemana === '' || $idPeriodo === 0 || $idMateria === 0) {
            redirigirConMensaje($urlModulo . '&idCurso=' . $idCurso, 'danger', 'Los campos Curso, Día, Período y Materia son obligatorios.');
        }

        // Validar límite de carga horaria semanal
        $validacionCarga = $modelo->validarCargaHorariaMateria($idCurso, $idMateria, $diaSemana, $idPeriodo);
        if (!$validacionCarga['ok']) {
            redirigirConMensaje($urlModulo . '&idCurso=' . $idCurso, 'danger', $validacionCarga['mensaje']);
        }

        $d = [
            'idCurso'   => $idCurso,
            'diaSemana' => $diaSemana,
            'idPeriodo' => $idPeriodo,
            'idMateria' => $idMateria,
            'idDocente' => $idDocente,
            'aula'      => $aula
        ];

        $ok = $modelo->guardarCelda($d);
        
        redirigirConMensaje(
            $urlModulo . '&idCurso=' . $idCurso, 
            $ok ? 'success' : 'danger', 
            $ok ? 'Asignación horaria guardada correctamente.' : 'No se pudo guardar la asignación horaria.'
        );
        break;

    // --------------------------------------------------------
    // ELIMINAR CELDA HORARIA
    // --------------------------------------------------------
    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $idCurso   = (int) ($_POST['idCurso'] ?? 0);
        $diaSemana = trim($_POST['diaSemana'] ?? '');
        $idPeriodo = (int) ($_POST['idPeriodo'] ?? 0);

        if ($idCurso === 0 || $diaSemana === '' || $idPeriodo === 0) {
            redirigirConMensaje($urlModulo . '&idCurso=' . $idCurso, 'danger', 'Datos insuficientes para eliminar.');
        }

        $ok = $modelo->eliminarCelda($idCurso, $diaSemana, $idPeriodo);

        redirigirConMensaje(
            $urlModulo . '&idCurso=' . $idCurso, 
            $ok ? 'success' : 'danger', 
            $ok ? 'Asignación horaria eliminada de la celda.' : 'No se pudo eliminar la asignación horaria.'
        );
        break;

    // --------------------------------------------------------
    // MOSTRAR VISTA PLANIFICADORA (INDEX)
    // --------------------------------------------------------
    case 'index':
    default:
        // 0. Obtener gestión activa
        $gestionActual = $modelo->obtenerGestionActual();
        $idGestionActual = $gestionActual['idGestion'] ?? 0;
        $gestionActualInfo = $gestionActual;

        // 1. Cargar lista de cursos para el selector
        $cursos = $modelo->listarCursos();

        // 2. Determinar el curso seleccionado (por defecto 0 si no se especifica)
        $idCurso = isset($_GET['idCurso']) ? (int) $_GET['idCurso'] : 0;

        // 3. Si hay un curso seleccionado, cargar catálogos y horarios asignados
        $periodos            = [];
        $materias            = [];
        $docentes            = [];
        $horariosCurso       = [];
        $asignacionesDocente = [];
        $aulasDocente        = [];
        $conteoMaterias      = [];

        if ($idCurso > 0) {
            $periodos = $modelo->listarPeriodos();
            $materias = $modelo->listarMateriasPorCurso($idCurso);
            $docentes = $modelo->listarDocentes();
            $asignacionesDocente = $modelo->obtenerAsignacionesDocente($idCurso);
            $aulasDocente        = $modelo->obtenerAulasDocente();
            $conteoMaterias      = $modelo->obtenerConteoMateriasHorario($idCurso);
            
            // Obtener asignaciones horarias
            $rawHorarios = $modelo->obtenerHorarioCurso($idCurso);
            
            // Estructurar las celdas asignadas en una matriz: [diaSemana][idPeriodo]
            foreach ($rawHorarios as $h) {
                $dia = $h['diaSemana'];
                $per = (int) $h['idPeriodo'];
                $horariosCurso[$dia][$per] = $h;
            }
        }

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vhorario.php';
        break;
}
