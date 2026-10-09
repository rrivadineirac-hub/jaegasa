<?php
/**
 * ============================================================
 * asignacion_docente.controller.php  —  Controlador CRUD Asignación Docente
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Gestiona la asignación de docentes a materias y cursos por gestión.
 * Acciones: index, guardar, eliminar
 * Solo accesible para el Administrador (rol 1).
 * ============================================================
 */

require_once BASE_PATH . '/Model/masignacion_docente.php';

// Control de acceso por rol (1 = Administrador)
if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloAsignacionDocente();

$urlModulo = BASE_URL . 'index.php?c=asignacion_docente';

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

        // Si es edición agrupada desde el modal del docente
        if (isset($_POST['edicion_agrupada'])) {
            $idDocente = (int) ($_POST['idDocente'] ?? 0);
            if ($idDocente <= 0) {
                redirigirConMensaje($urlModulo, 'danger', 'Debe seleccionar un docente válido.');
            }

            $aEliminar = $_POST['asignaciones_a_eliminar'] ?? [];
            $aEliminar = array_map('intval', $aEliminar);

            $eliminadasCount = 0;
            foreach ($aEliminar as $idAs) {
                if ($idAs > 0) {
                    $modelo->eliminar($idAs);
                    $eliminadasCount++;
                }
            }

            if ($eliminadasCount > 0) {
                redirigirConMensaje($urlModulo, 'success', "Se eliminaron {$eliminadasCount} asignación(es) seleccionada(s) correctamente.");
            } else {
                redirigirConMensaje($urlModulo, 'warning', "No se seleccionó ninguna asignación para eliminar.");
            }
        }

        // Si es edición completa (cambios de cursos y materias en grilla)
        if (isset($_POST['edicion_completa'])) {
            $idDocente = (int) ($_POST['idDocente'] ?? 0);
            $idGestion = (int) ($_POST['idGestion'] ?? 0);
            $cursosSeleccionados = $_POST['cursos'] ?? [];
            $materiasSeleccionadas = $_POST['materias'] ?? [];

            if ($idDocente <= 0) {
                redirigirConMensaje($urlModulo, 'danger', 'Debe seleccionar un docente válido.');
            }
            if ($idGestion <= 0) {
                redirigirConMensaje($urlModulo, 'danger', 'Debe seleccionar una gestión válida.');
            }

            // 1. Eliminar asignaciones actuales
            $modelo->eliminarPorDocente($idDocente);

            // 2. Crear las nuevas asignaciones seleccionadas
            $exitos = 0;
            $errores = 0;
            $duplicados = 0;

            foreach ($cursosSeleccionados as $idCurso) {
                $idCurso = (int) $idCurso;
                if ($idCurso <= 0) continue;

                $mats = $materiasSeleccionadas[$idCurso] ?? [];
                $mats = array_unique(array_filter(array_map('intval', $mats)));

                foreach ($mats as $idMateria) {
                    if ($idMateria <= 0) continue;

                    if ($modelo->existeAsignacion($idGestion, $idCurso, $idMateria)) {
                        $duplicados++;
                        continue;
                    }

                    $datos = [
                        'idGestion' => $idGestion,
                        'idCurso'   => $idCurso,
                        'idMateria' => $idMateria,
                        'idDocente' => $idDocente
                    ];

                    $res = $modelo->crear($datos);
                    if ($res['ok']) {
                        $exitos++;
                    } else {
                        $errores++;
                    }
                }
            }

            redirigirConMensaje($urlModulo, 'success', 'Asignaciones del docente actualizadas correctamente.');
        }

        $id        = (int) ($_POST['idAsignacion'] ?? 0);
        $idGestion = (int) ($_POST['idGestion'] ?? 0);
        $idDocente = (int) ($_POST['idDocente'] ?? 0);
        $cursosSeleccionados = $_POST['cursos'] ?? [];
        $materiasSeleccionadas = $_POST['materias'] ?? [];

        // Validaciones básicas
        if ($idGestion <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'Debe seleccionar una gestión válida.');
        }
        if ($idDocente <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'Debe seleccionar un docente válido.');
        }
        if (empty($cursosSeleccionados)) {
            redirigirConMensaje($urlModulo, 'danger', 'Debe seleccionar al menos un curso.');
        }

        // Si estamos editando, damos de baja lógica la asignación original primero
        // para que no cause conflicto con las nuevas asignaciones del docente.
        if ($id > 0) {
            $modelo->eliminar($id);
        }

        $exitos = 0;
        $errores = 0;
        $duplicados = 0;

        foreach ($cursosSeleccionados as $idCurso) {
            $idCurso = (int) $idCurso;
            if ($idCurso <= 0) continue;

            $mats = $materiasSeleccionadas[$idCurso] ?? [];
            // Filtrar duplicados locales e IDs vacíos
            $mats = array_unique(array_filter(array_map('intval', $mats)));

            foreach ($mats as $idMateria) {
                if ($idMateria <= 0) continue;

                // Validar duplicado en la base de datos
                if ($modelo->existeAsignacion($idGestion, $idCurso, $idMateria)) {
                    $duplicados++;
                    continue;
                }

                $datos = [
                    'idGestion' => $idGestion,
                    'idCurso'   => $idCurso,
                    'idMateria' => $idMateria,
                    'idDocente' => $idDocente
                ];

                $res = $modelo->crear($datos);
                if ($res['ok']) {
                    $exitos++;
                } else {
                    $errores++;
                }
            }
        }

        if ($exitos > 0) {
            $msg = "Se registraron {$exitos} asignación(es) correctamente.";
            if ($duplicados > 0) {
                $msg .= " (Se omitieron {$duplicados} materia(s) que ya cuentan con docente asignado).";
            }
            redirigirConMensaje($urlModulo, 'success', $msg);
        } else {
            if ($duplicados > 0) {
                redirigirConMensaje($urlModulo, 'danger', 'Las materias seleccionadas para los cursos ya cuentan con docente asignado.');
            } else {
                redirigirConMensaje($urlModulo, 'danger', 'Ocurrió un error al procesar las asignaciones.');
            }
        }
        break;

    // --------------------------------------------------------
    // ELIMINAR (baja lógica)
    // --------------------------------------------------------
    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $id = (int) ($_POST['idAsignacion'] ?? 0);
        if ($id <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'ID de asignación no válido.');
        }

        $resultado = $modelo->eliminar($id);
        redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    // --------------------------------------------------------
    // VISTA PRINCIPAL (Listar)
    // --------------------------------------------------------
    case 'index':
    default:
        $buscar = trim($_GET['q'] ?? '');
        
        // Obtener listados
        $asignaciones = $modelo->listar($buscar);
        $gestiones    = $modelo->listarGestionesActivas();
        $cursos       = $modelo->listarCursosActivos();
        $materias     = $modelo->listarMateriasActivas();
        $docentes     = $modelo->listarDocentesActivos();

        // Determinar gestión actual (la primera en orden descendente)
        $idGestionActual = 1;
        $gestionActualInfo = null;
        if (!empty($gestiones)) {
            $idGestionActual = (int) $gestiones[0]['idGestion'];
            $gestionActualInfo = $gestiones[0];
        }

        // Obtener materias no asignadas por curso para la gestión actual
        $materiasDisponibles = $modelo->obtenerMateriasDisponiblesPorCurso($idGestionActual);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vasignacion_docente.php';
        break;
}
