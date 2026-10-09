<?php
/**
 * ============================================================
 * vimportar_estudiantes.php  —  Vista de Importación Excel por Curso
 * Sistema Académico JAEGASA
 * ============================================================
 */

require_once BASE_PATH . '/View/header.php';
?>

<!-- SheetJS (XLSX) para lectura en cliente de archivos Excel .xlsx / .xls / .csv -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<link rel="stylesheet" href="<?= BASE_URL ?>Css/importar_estudiantes.css?v=<?= @filemtime(BASE_PATH . '/Css/importar_estudiantes.css') ?>">

<?php
// Preparar catálogos únicos para los selectores separados
$nivelesUnicos   = [];
$cursosUnicos    = [];
$paralelosUnicos = [];
$turnosUnicos    = [];
$gestionesUnicas = [];

foreach ($cursos as $c) {
    if (!empty($c['idNivel']))     $nivelesUnicos[$c['idNivel']]       = $c['nivelNombre'];
    if (!empty($c['cursoNombre'])) $cursosUnicos[$c['cursoNombre']]     = $c['cursoNombre'];
    if (!empty($c['idParalelo']))  $paralelosUnicos[$c['idParalelo']] = $c['paraleloNombre'];
    if (!empty($c['idTurno']))     $turnosUnicos[$c['idTurno']]       = $c['turnoNombre'];
    if (!empty($c['gestionAnio'])) $gestionesUnicas[$c['gestionAnio']] = $c['gestionAnio'];
}

$ordenGrados = [
    'Primero' => 1, 'Segundo' => 2, 'Tercero' => 3,
    'Cuarto'  => 4, 'Quinto'  => 5, 'Sexto'   => 6
];
uksort($cursosUnicos, function($a, $b) use ($ordenGrados) {
    $posA = $ordenGrados[$a] ?? 999;
    $posB = $ordenGrados[$b] ?? 999;
    return $posA === $posB ? strcasecmp($a, $b) : $posA <=> $posB;
});
?>

<!-- Catálogo de Cursos en JS para resolución dinámica -->
<script>
    const CURSOS_CATALOGO = <?= json_encode(array_map(fn($c) => [
        'idCurso'        => (int) $c['idCurso'],
        'cursoNombre'    => $c['cursoNombre'] ?? '',
        'idNivel'        => (int) $c['idNivel'],
        'nivelNombre'    => $c['nivelNombre'] ?? '',
        'idParalelo'     => (int) $c['idParalelo'],
        'paraleloNombre' => $c['paraleloNombre'] ?? '',
        'idTurno'        => (int) $c['idTurno'],
        'turnoNombre'    => $c['turnoNombre'] ?? '',
        'gestionAnio'    => (int) $c['gestionAnio'],
        'descripcion'    => $c['descripcion'] ?? ''
    ], $cursos)) ?>;
</script>

<div class="import-container">

    <!-- Mensaje Flash -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['tipo'] === 'exito' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <i class="bi <?= $_SESSION['flash']['tipo'] === 'exito' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
            <?= htmlspecialchars($_SESSION['flash']['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Encabezado -->
    <div class="import-header-card">
        <div>
            <h2><i class="bi bi-file-earmark-excel-fill text-success"></i> Importación Masiva de Estudiantes por Curso</h2>
            <p>Cargue listas completas de alumnos desde un archivo Excel (.xlsx, .csv) e inscríbalos directamente en su curso.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button id="btnSeleccionarArchivoExcel" type="button" class="btn btn-primary fw-bold shadow-sm px-3 py-2">
                <i class="bi bi-file-earmark-arrow-up-fill me-1"></i> Seleccionar / Cargar Archivo Excel
            </button>
            <a id="btnDescargarPlantilla" href="<?= BASE_URL ?>index.php?c=importar_estudiantes&a=descargar_plantilla&idCurso=<?= $idCursoSeleccionado ?>" class="btn btn-success fw-bold shadow-sm px-3 py-2">
                <i class="bi bi-download me-1"></i> Descargar Plantilla Excel (.csv)
            </a>

        </div>
    </div>






    <!-- Selector de Curso de Destino (En cadena: 1. Curso -> 2. Paralelo -> 3. Nivel -> 4. Turno) -->
    <div class="course-selector-card">
        <label class="mb-2 fw-bold text-dark fs-6">
            <i class="bi bi-diagram-3-fill text-primary me-1"></i> Seleccione el Curso de Destino:
        </label>

        <div class="row g-2 align-items-center">
            <!-- 1. Curso / Grado -->
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">1. Curso / Grado</label>
                <select id="selGrado" class="form-select filter-curso-part">
                    <option value="">Seleccione...</option>
                    <?php foreach ($cursosUnicos as $nomG): ?>
                        <option value="<?= htmlspecialchars($nomG) ?>"><?= htmlspecialchars($nomG) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 2. Paralelo -->
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">2. Paralelo</label>
                <select id="selParalelo" class="form-select filter-curso-part">
                    <option value="">Seleccione...</option>
                </select>
            </div>

            <!-- 3. Nivel -->
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">3. Nivel</label>
                <select id="selNivel" class="form-select filter-curso-part">
                    <option value="">Seleccione...</option>
                </select>
            </div>

            <!-- 4. Turno -->
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">4. Turno</label>
                <select id="selTurno" class="form-select filter-curso-part">
                    <option value="">Seleccione...</option>
                </select>
            </div>
        </div>


        <!-- Input oculto que guarda el idCurso resuelto -->
        <input type="hidden" id="selectCurso" value="<?= $idCursoSeleccionado ?>">

        <div id="cursoSeleccionadoBadge" class="mt-3 p-2 px-3 rounded bg-light border text-primary fw-bold small d-flex align-items-center justify-content-between">
            <span><i class="bi bi-check-circle-fill me-1"></i> Curso Resuelto: <span id="nombreCursoResuelto" class="text-dark">Cargando...</span></span>
            <span id="badgeEstadoResuelto" class="badge bg-success">Listo</span>
        </div>
    </div>

    <!-- Zona de Arrastrar y Soltar (Dropzone) -->
    <div class="dropzone-container" id="dropzone">
        <input type="file" id="inputExcel" accept=".xlsx, .xls, .csv" class="d-none">
        <i class="bi bi-cloud-arrow-up-fill dropzone-icon"></i>
        <div class="dropzone-title">Arrastre aquí su archivo Excel (.xlsx, .csv) o haga clic para seleccionar</div>
        <p class="dropzone-desc">Soporta formatos Microsoft Excel y archivos separados por comas. Mapeo automático de columnas.</p>
    </div>

    <!-- Sección de Pre-Validación y Vista Previa (Inicialmente Oculta) -->
    <div class="preview-card" id="sectionPreview" style="display: none;">
        
        <div class="preview-card-header">
            <h4><i class="bi bi-search me-1"></i> Pre-validación de Registros del Archivo</h4>
            <button id="btnEjecutarImportacion" class="btn-import-execute" disabled>
                <i class="bi bi-check-circle-fill"></i> Confirmar e Importar al Curso
            </button>
        </div>

        <div class="p-4">
            <!-- Tarjetas de Métricas -->
            <div class="metrics-row">
                <div class="metric-box metric-total">
                    <div class="metric-info">
                        <span>Total Filas</span>
                        <h3 id="metricTotal">0</h3>
                    </div>
                    <div class="metric-icon"><i class="bi bi-list-ol"></i></div>
                </div>

                <div class="metric-box metric-validos">
                    <div class="metric-info">
                        <span>Nuevos Válidos</span>
                        <h3 id="metricValidos">0</h3>
                    </div>
                    <div class="metric-icon"><i class="bi bi-person-check-fill"></i></div>
                </div>

                <div class="metric-box metric-duplicados">
                    <div class="metric-info">
                        <span>Duplicados BD</span>
                        <h3 id="metricDuplicados">0</h3>
                    </div>
                    <div class="metric-icon"><i class="bi bi-exclamation-square-fill"></i></div>
                </div>

                <div class="metric-box metric-incompletos">
                    <div class="metric-info">
                        <span>Incompletos</span>
                        <h3 id="metricIncompletos">0</h3>
                    </div>
                    <div class="metric-icon"><i class="bi bi-x-octagon-fill"></i></div>
                </div>
            </div>

            <!-- Tabla de Pre-vista -->
            <div class="table-preview-wrapper">
                <table class="table-preview">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Estado</th>
                            <th>RUDE</th>
                            <th>CI</th>
                            <th>Apellidos</th>
                            <th>Nombres</th>
                            <th>Género</th>
                            <th>Fecha Nac.</th>
                            <th>Tutor</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyPreview">
                        <!-- Filas parseadas JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Gestión / Estudiantes Inscritos en el Curso Seleccionado -->
    <div class="course-students-card">
        <div class="course-students-header">
            <div>
                <h3>
                    <i class="bi bi-people-fill text-primary"></i> Estudiantes Inscritos en el Curso
                </h3>
                <span id="labelCursoNombre" class="text-muted fw-bold small">
                    <?= htmlspecialchars($cursoInfo['descripcion'] ?? 'Seleccione un curso') ?>
                </span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary fs-6 px-3 py-2" id="badgeCountCurso">
                    <?= count($estudiantesCurso) ?> estudiante(s)
                </span>
                <div class="search-input-wrapper">
                    <i class="bi bi-search"></i>
                    <input type="text" id="inputBuscarCurso" class="form-control" placeholder="Buscar alumno...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>RUDE</th>
                        <th>CI</th>
                        <th>Estudiante</th>
                        <th>Género</th>
                        <th>Tutor / Contacto</th>
                        <th class="text-end" style="width: 100px;">Acción</th>
                    </tr>
                </thead>
                <tbody id="tbodyInscritos">
                    <?php if (empty($estudiantesCurso)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i> No hay estudiantes inscritos en este curso aún.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($estudiantesCurso as $idx => $e): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><code><?= htmlspecialchars($e['rude'] ?: 'S/R') ?></code></td>
                                <td><?= htmlspecialchars($e['ci'] ?: '-') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($e['apPaterno'] . ' ' . $e['apMaterno'] . ' ' . $e['nombre']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?= $e['genero'] === 'F' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary' ?>">
                                        <?= $e['genero'] === 'F' ? 'Femenino' : 'Masculino' ?>
                                    </span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($e['tutorNombre'] ?: 'Sin registrar') ?>
                                    <?php if (!empty($e['tutorTelefono'])): ?>
                                        <small class="text-muted">(<?= htmlspecialchars($e['tutorTelefono']) ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>index.php?c=importar_estudiantes&a=eliminar_estudiante_curso&idInscripcion=<?= $e['idInscripcion'] ?>&idCurso=<?= $idCursoSeleccionado ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('¿Quitar a este estudiante de este curso?');" title="Quitar del curso">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ========================================================= -->
<!-- MODAL 1: Confirmación de Importación Masiva (Reemplaza confirm()) -->
<!-- ========================================================= -->
<div class="modal fade" id="modalConfirmarImportacion" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #1D8AB5, #146585);">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2 text-white m-0">
                    <i class="bi bi-file-earmark-excel-fill text-warning fs-4"></i> Confirmar Importación Masiva
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle p-3 mb-2" style="width: 74px; height: 74px;">
                        <i class="bi bi-cloud-arrow-up-fill fs-1 text-primary"></i>
                    </span>
                </div>
                <h5 class="fw-bold text-dark mb-2">¿Está seguro de proceder con la importación?</h5>
                <p class="text-muted small mb-3">Se procesará el archivo Excel e inscribirá automáticamente a los estudiantes en el curso seleccionado.</p>
                
                <div class="p-3 bg-light rounded-3 border text-start mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold"><i class="bi bi-mortarboard-fill me-1 text-secondary"></i> Curso Destino:</span>
                        <span id="modalConfirmCurso" class="fw-bold text-primary small text-end ms-2">Cargando...</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small fw-semibold"><i class="bi bi-people-fill me-1 text-secondary"></i> Estudiantes a Importar:</span>
                        <span id="modalConfirmCantidad" class="badge bg-success fs-6">0 estudiantes</span>
                    </div>
                </div>

                <div class="alert alert-info d-flex align-items-center gap-2 py-2 px-3 small text-start m-0">
                    <i class="bi bi-info-circle-fill fs-5 flex-shrink-0 text-info"></i>
                    <span>Los registros existentes serán actualizados y los nuevos alumnos quedarán inscritos.</span>
                </div>
            </div>
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i> Cancelar
                </button>
                <button type="button" id="btnEjecutarConfirmado" class="btn btn-success px-4 fw-bold shadow-sm">
                    <i class="bi bi-check-circle-fill me-1"></i> Sí, Importar Ahora
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- MODAL 2: Resultado / Éxito de Importación (Reemplaza alert()) -->
<!-- ========================================================= -->
<div class="modal fade" id="modalResultadoImportacion" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #51C743, #3a962e);">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2 text-white m-0">
                    <i class="bi bi-check-circle-fill fs-4"></i> ¡Proceso Completado!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle p-3 mb-2" style="width: 76px; height: 76px;">
                        <i class="bi bi-patch-check-fill fs-1 text-success"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-dark mb-2">¡Importación Exitosa!</h4>
                <p id="modalResultadoMensaje" class="text-muted fs-6 mb-3">Los estudiantes han sido registrados e inscritos correctamente en el curso.</p>
                
                <div class="p-3 bg-light rounded-3 border text-start">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold"><i class="bi bi-person-check-fill text-success me-1"></i> Creados / Actualizados:</span>
                        <span id="modalResultadoInsertados" class="badge bg-success fs-6">0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small fw-semibold"><i class="bi bi-journal-check text-primary me-1"></i> Inscritos en el Curso:</span>
                        <span id="modalResultadoInscritos" class="badge bg-primary fs-6">0</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-center py-3">
                <button type="button" class="btn btn-success px-5 fw-bold shadow-sm" data-bs-dismiss="modal">
                    <i class="bi bi-check-lg me-1"></i> Aceptar / Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- MODAL 3: Alerta de Error / Advertencia -->
<!-- ========================================================= -->
<div class="modal fade" id="modalAlertaImportacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="modal-header bg-warning text-dark py-3 px-4" id="modalAlertaHeader">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2 m-0" id="modalAlertaTitulo">
                    <i class="bi bi-exclamation-triangle-fill fs-4"></i> Atención
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3" id="modalAlertaIconContainer">
                    <span class="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-circle p-3 mb-2" style="width: 70px; height: 70px;">
                        <i class="bi bi-exclamation-circle-fill fs-1"></i>
                    </span>
                </div>
                <p id="modalAlertaMensaje" class="fs-6 text-dark mb-0 fw-semibold">Mensaje de notificación.</p>
            </div>
            <div class="modal-footer bg-light justify-content-center py-3">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>Js/importar_estudiantes.js?v=<?= @filemtime(BASE_PATH . '/Js/importar_estudiantes.js') ?>"></script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>
