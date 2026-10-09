<?php
/**
 * vmalla_curricular.php — Vista del CRUD Malla Curricular
 * Recibe: $mallas, $cursos, $materias, $docentes, $flash, $buscar
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$idGestionActual = (function () use ($cursos) {
    $gestiones = array_column($cursos, 'idGestion');
    return $gestiones ? (int) max($gestiones) : 1;
})();

$gestionActualAnio = (function () use ($cursos) {
    $anios = array_column($cursos, 'anio');
    return $anios ? (int) max($anios) : (int) date('Y');
})();

$gestionActualInfo = [
    'idGestion' => $idGestionActual,
    'anio'      => $gestionActualAnio
];

// Filtrar cursos de la gestión actual
$cursosGestionActual = array_values(array_filter(
    $cursos,
    fn($c) => (string) ($c['anio'] ?? '') === (string) $gestionActualAnio
));

// Catálogos únicos
$cursosUnicos = [];
$nivelesUnicos = [];
$turnosUnicos = [];
$paralelosUnicos = [];
foreach ($cursosGestionActual as $c) {
    if (!empty($c['cursoNombre'])) {
        $cursosUnicos[$c['cursoNombre']] = $c['cursoNombre'];
    }
    $nivelesUnicos[$c['idNivel']] = $c['nivelNombre'];
    $turnosUnicos[$c['idTurno']] = $c['turnoNombre'];
    $paralelosUnicos[$c['idParalelo']] = $c['paraleloNombre'];
}

// Ordenar grados
$ordenGrados = [
    'Primero' => 1,
    'Segundo' => 2,
    'Tercero' => 3,
    'Cuarto'  => 4,
    'Quinto'  => 5,
    'Sexto'   => 6
];
uksort($cursosUnicos, function($a, $b) use ($ordenGrados) {
    $posA = $ordenGrados[$a] ?? 999;
    $posB = $ordenGrados[$b] ?? 999;
    if ($posA === $posB) {
        return strcasecmp($a, $b);
    }
    return $posA <=> $posB;
});

// Definir cursoUnicos por compatibilidad (con y sin 's')
$cursoUnicos = &$cursosUnicos;

$accionGuardar       = BASE_URL . 'index.php?c=malla_curricular&a=guardar';
$accionEliminar      = BASE_URL . 'index.php?c=malla_curricular&a=eliminar';
$accionEliminarCurso = BASE_URL . 'index.php?c=malla_curricular&a=eliminarCurso';

// Agrupar las materias asignadas por curso
$mallasPorCurso = [];
$materiasPorCurso = [];
foreach ($mallas as $item) {
    $cId = (int) $item['idCurso'];
    if (!isset($mallasPorCurso[$cId])) {
        $mallasPorCurso[$cId] = [
            'idCurso'        => $cId,
            'cursoNombre'    => $item['cursoNombre'],
            'paraleloNombre' => $item['paraleloNombre'],
            'nivelNombre'    => $item['nivelNombre'],
            'turnoNombre'    => $item['turnoNombre'],
            'gestionAnio'    => $item['gestionAnio'],
            'materias'       => [],
            'totalCarga'     => 0
        ];
    }
    $mallasPorCurso[$cId]['materias'][] = $item;
    $mallasPorCurso[$cId]['totalCarga'] += (int) $item['cargaHoraria'];

    if (!isset($materiasPorCurso[$cId])) {
        $materiasPorCurso[$cId] = [];
    }
    $materiasPorCurso[$cId][(int) $item['idMateria']] = (int) $item['cargaHoraria'];
}
?>

<!-- Datos de cursos de la gestión activa, para resolver idCurso en el navegador -->
<script>
    const CURSOS_DATA = <?= json_encode(array_map(fn($c) => [
        'idCurso'     => (int) $c['idCurso'],
        'cursoNombre' => $c['cursoNombre'] ?? '',
        'idNivel'     => (int) $c['idNivel'],
        'idTurno'     => (int) $c['idTurno'],
        'idParalelo'  => (int) $c['idParalelo'],
    ], $cursosGestionActual)) ?>;
</script>

<!-- Carga del archivo CSS específico para Malla Curricular -->
<link rel="stylesheet" href="<?= BASE_URL ?>Css/malla_curricular.css?v=<?= @filemtime(BASE_PATH . '/Css/malla_curricular.css') ?>">

<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-journal-bookmark-fill"></i> Malla Curricular</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Plan de estudios e inscripciones de asignaturas y docentes por curso</p>
    </div>
    <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="bi bi-plus-lg"></i> Nueva Asignación
    </button>
</div>



<div class="card-panel">
    <div class="tabla-toolbar d-flex justify-content-between align-items-center mb-3">
        <span class="tabla-total m-0"><?= count($mallasPorCurso) ?> CURSO(S)</span>
        <div class="text-end">
            <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
            <div class="text-primary fw-semibold fs-6">
                <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualInfo['anio'] ?? date('Y')) ?>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaMalla">
            <thead>
                <tr>
                    <th style="width: 5%">N°</th>
                    <th style="width: 45%">Curso / Paralelo</th>
                    <th style="width: 20%">Materias Asignadas</th>
                    <th style="width: 15%">Carga Horaria Total</th>
                    <th style="width: 15%" class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mallasPorCurso)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i> No se encontraron mallas curriculares registradas.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = 0; foreach ($mallasPorCurso as $cId => $mCurso): $i++; ?>
                        <?php 
                        $cursoDesc = trim($mCurso['cursoNombre'] . ' "' . $mCurso['paraleloNombre'] . '" (' . $mCurso['nivelNombre'] . ' - ' . $mCurso['turnoNombre'] . ')');
                        $numMaterias = count($mCurso['materias']);
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td class="fw-semibold">
                                <span class="badge-curso"><i class="bi bi-mortarboard-fill"></i> <?= $e($cursoDesc) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-semibold">
                                    <i class="bi bi-book-fill text-primary me-1"></i> <?= $numMaterias ?> materia(s)
                                </span>
                            </td>
                            <td>
                                <span class="badge-carga">
                                    <i class="bi bi-clock-fill"></i> <?= (int) $mCurso['totalCarga'] ?> periodos
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="acciones-tabla">
                                    <button class="btn-accion text-white" title="Ver Malla Completa" data-bs-toggle="modal" data-bs-target="#modalVerCurso<?= $cId ?>" style="background-color: #0dcaf0; border-color: #0dcaf0;"><i class="bi bi-eye-fill"></i></button>
                                    <button class="btn-accion btn-editar" title="Editar Malla del Curso" data-bs-toggle="modal" data-bs-target="#modalEditarCurso<?= $cId ?>"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="btn-accion btn-eliminar" title="Eliminar Malla Completa del Curso" data-bs-toggle="modal" data-bs-target="#modalEliminarCurso<?= $cId ?>"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modales por Curso (Ver, Editar, Eliminar) -->
<?php foreach ($mallasPorCurso as $cId => $mCurso): ?>

    <!-- Modal Ver Malla Curricular Completa -->
    <div class="modal fade" id="modalVerCurso<?= $cId ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
                <div class="modal-header bg-primary text-white p-3">
                    <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-journal-check me-2"></i> Malla Curricular Completa — <?= $e($mCurso['cursoNombre'] . ' "' . $mCurso['paraleloNombre'] . '" (' . $mCurso['nivelNombre'] . ')') ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-1 fw-bold text-dark"><i class="bi bi-mortarboard-fill me-1 text-primary"></i> <?= $e($mCurso['cursoNombre'] . ' "' . $mCurso['paraleloNombre'] . '"') ?></h6>
                            <span class="text-muted small"><?= $e($mCurso['nivelNombre']) ?> | Turno: <?= $e($mCurso['turnoNombre']) ?></span>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-primary border px-3 py-2 rounded-pill fw-semibold"><i class="bi bi-calendar2-check me-1"></i> Gestión <?= $e($mCurso['gestionAnio']) ?></span>
                        </div>
                    </div>

                    <div class="table-responsive rounded border">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 8%">N°</th>
                                    <th style="width: 15%">Sigla</th>
                                    <th style="width: 37%">Asignatura / Materia</th>
                                    <th style="width: 25%">Docente Asignado</th>
                                    <th style="width: 15%" class="text-end">Carga Horaria</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mCurso['materias'] as $idx => $matRow): ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td><span class="badge bg-secondary text-white fw-bold"><?= $e($matRow['materiaSigla']) ?></span></td>
                                        <td class="fw-semibold text-dark"><?= $e($matRow['materiaNombre']) ?></td>
                                        <td>
                                            <?php if (!empty(trim($matRow['docenteCompleto'] ?? ''))): ?>
                                                <span class="text-dark fw-medium" style="font-size: 0.9em;"><i class="bi bi-person-badge text-muted me-1"></i> <?= $e(trim($matRow['docenteCompleto'])) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic small">Sin asignar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold text-primary"><?= (int) $matRow['cargaHoraria'] ?> periodos</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end fw-bold text-dark">Carga Horaria Total:</th>
                                    <th class="text-end fw-bold text-success fs-6"><?= (int) $mCurso['totalCarga'] ?> periodos</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Malla del Curso -->
    <div class="modal fade" id="modalEditarCurso<?= $cId ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" autocomplete="off" class="form-malla-validador">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i> Editar Asignación Curricular</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idCurso" value="<?= $cId ?>">
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold mb-1">Curso al que se inscribe la asignatura</label>
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">Curso</label>
                                        <select class="form-select" disabled>
                                            <option selected><?= $e($mCurso['cursoNombre']) ?></option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">Paralelo</label>
                                        <select class="form-select" disabled>
                                            <option selected><?= $e($mCurso['paraleloNombre']) ?></option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">Nivel</label>
                                        <select class="form-select" disabled>
                                            <option selected><?= $e($mCurso['nivelNombre']) ?></option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">Turno</label>
                                        <select class="form-select" disabled>
                                            <option selected><?= $e($mCurso['turnoNombre']) ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-bold mb-2">Materias a asignar (Seleccione e indique carga horaria) *</label>
                                <div class="invalid-feedback feedback-grid-malla text-danger fw-bold mb-2" style="display: none; font-size: 0.85rem;">
                                    <i class="bi bi-exclamation-octagon-fill"></i> Debe seleccionar al menos una materia e indicar su carga horaria.
                                </div>
                                
                                <div class="card bg-light border-0 shadow-sm p-3 materias-selector-wrapper">
                                    <div class="row">
                                        <?php 
                                        $materiasAsignadas = $materiasPorCurso[$cId] ?? [];
                                        
                                        $totalMaterias = count($materias);
                                        $mitad = ceil($totalMaterias / 2);
                                        $col1 = array_slice($materias, 0, $mitad);
                                        $col2 = array_slice($materias, $mitad);
                                        
                                        if (!function_exists('renderColumnaMateriasEditar')) {
                                            function renderColumnaMateriasEditar($mats, $materiasAsignadas, $idM, $e) {
                                                foreach ($mats as $mat): 
                                                    $idMat = (int)$mat['idMateria'];
                                                    $estaAsignada = isset($materiasAsignadas[$idMat]);
                                                    $carga = $estaAsignada ? (int) $materiasAsignadas[$idMat] : '';
                                                    $rowClass = $estaAsignada ? 'materia-item-row d-flex align-items-center justify-content-between mb-2 p-2 bg-white rounded shadow-xs border active-item' : 'materia-item-row d-flex align-items-center justify-content-between mb-2 p-2 bg-white rounded shadow-xs border';
                                                    ?>
                                                    <div class="<?= $rowClass ?>">
                                                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                                                            <input class="form-check-input check-materia-select" type="checkbox" 
                                                                   name="materias[]" 
                                                                   id="checkMateria_<?= $idM ?>_<?= $idMat ?>" 
                                                                   value="<?= $idMat ?>"
                                                                   <?= $estaAsignada ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold text-dark cursor-pointer mb-0 text-truncate" for="checkMateria_<?= $idM ?>_<?= $idMat ?>" style="max-width: 220px;">
                                                                [<?= $e($mat['sigla']) ?>] - <?= $e($mat['nombre']) ?>
                                                            </label>
                                                        </div>
                                                        <div style="width: 80px;">
                                                            <input type="number" class="form-control form-control-sm input-carga-materia text-center" 
                                                                   name="cargaHoraria[<?= $idMat ?>]" 
                                                                   min="1" max="20" placeholder="Horas" 
                                                                   value="<?= $carga ?>"
                                                                   <?= $estaAsignada ? 'required' : 'disabled' ?>>
                                                        </div>
                                                    </div>
                                                    <?php 
                                                endforeach;
                                            }
                                        }
                                        ?>
                                        
                                        <div class="col-md-6">
                                            <?php renderColumnaMateriasEditar($col1, $materiasAsignadas, $cId, $e); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <?php renderColumnaMateriasEditar($col2, $materiasAsignadas, $cId, $e); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between align-items-center">
                        <div class="text-start">
                            <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                            <div class="text-primary fw-semibold fs-6">
                                <i class="bi bi-calendar2-check"></i> <?= $e($mCurso['gestionAnio'] ?? $gestionActualAnio) ?>
                            </div>
                        </div>
                        <div class="text-center mx-auto px-2">
                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-bold contador-periodos-disponibles" style="font-size: 0.88rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                <i class="bi bi-clock-history text-primary me-1"></i> <span class="texto-contador-periodos">Tiene <strong class="periodos-restantes text-primary fs-6">35</strong> Periodos para asignar al curso</span>
                            </span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-jaegasa">Guardar Cambios</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Malla Completa del Curso -->
    <div class="modal fade" id="modalEliminarCurso<?= $cId ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionEliminarCurso ?>" method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i> Eliminar Malla Curricular</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <div class="text-danger mb-3">
                            <i class="bi bi-trash-fill display-4"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">¿Está seguro de eliminar?</h5>
                        <p class="fs-6 text-secondary mb-3">Se va a eliminar la malla curricular de todo el curso <strong><?= $e($mCurso['cursoNombre'] . ' "' . $mCurso['paraleloNombre'] . '" (' . $mCurso['nivelNombre'] . ')') ?></strong>.</p>
                        <div class="alert alert-warning mb-0 text-start small">
                            <i class="bi bi-exclamation-circle-fill me-1"></i> <strong>Atención:</strong> Al confirmar, se desvincularán todas las materias asignadas a este curso para la gestión activa.
                        </div>
                        <input type="hidden" name="idCurso" value="<?= $cId ?>">
                    </div>
                    <div class="modal-footer justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger px-4 py-2 fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Aceptar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php endforeach; ?>

<!-- Modal Nuevo -->
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="<?= $accionGuardar ?>" method="post" autocomplete="off" class="form-malla-validador">
                <div class="modal-header">
                    <h5 class="modal-title">Asignación Curricular por Curso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idMallaCurricular" value="0">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold mb-1">Curso al que se inscribe la asignatura *</label>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Curso *</label>
                                    <select class="form-select curso-selector" data-tipo="grado" required <?= empty($cursoUnicos) ? 'disabled' : '' ?>>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($cursosUnicos as $id => $nomN): ?>
                                            <option value="<?= $e($id) ?>"><?= $e($nomN) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Paralelo *</label>
                                    <select class="form-select curso-selector" data-tipo="paralelo" required <?= empty($paralelosUnicos) ? 'disabled' : '' ?>>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($paralelosUnicos as $idP => $nomP): ?>
                                            <option value="<?= (int) $idP ?>"><?= $e($nomP) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Nivel *</label>
                                    <select class="form-select curso-selector" data-tipo="nivel" required <?= empty($nivelesUnicos) ? 'disabled' : '' ?>>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($nivelesUnicos as $idN => $nomN): ?>
                                            <option value="<?= (int) $idN ?>"><?= $e($nomN) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Turno *</label>
                                    <select class="form-select curso-selector" data-tipo="turno" required <?= empty($turnosUnicos) ? 'disabled' : '' ?>>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($turnosUnicos as $idT => $nomT): ?>
                                            <option value="<?= (int) $idT ?>"><?= $e($nomT) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" name="idCurso" value="" class="idCursoResuelto" required>
                            <div class="alert alert-warning mt-2 cursoNoEncontrado" hidden>
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                No existe un curso con esa combinación de nivel, turno y paralelo en la gestión <?= $e($gestionActualAnio) ?>.
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold mb-2">Materias a asignar (Seleccione e indique carga horaria) *</label>
                            <div class="invalid-feedback feedback-grid-malla text-danger fw-bold mb-2" style="display: none; font-size: 0.85rem;">
                                <i class="bi bi-exclamation-octagon-fill"></i> Debe seleccionar al menos una materia e indicar su carga horaria.
                            </div>
                            
                            <div class="card bg-light border-0 shadow-sm p-3 materias-selector-wrapper disabled-wrapper">
                                <div class="row" id="containerMateriasMalla">
                                    <?php 
                                    $totalMaterias = count($materias);
                                    $mitad = ceil($totalMaterias / 2);
                                    $col1 = array_slice($materias, 0, $mitad);
                                    $col2 = array_slice($materias, $mitad);
                                    
                                    if (!function_exists('renderColumnaMaterias')) {
                                        function renderColumnaMaterias($mats, $e) {
                                            foreach ($mats as $m): 
                                                $idMat = (int)$m['idMateria'];
                                                ?>
                                                <div class="materia-item-row d-flex align-items-center justify-content-between mb-2 p-2 bg-white rounded shadow-xs border">
                                                    <div class="d-flex align-items-center gap-2 flex-grow-1">
                                                        <input class="form-check-input check-materia-select" type="checkbox" 
                                                               name="materias[]" 
                                                               id="checkMateria_<?= $idMat ?>" 
                                                               value="<?= $idMat ?>" disabled>
                                                        <label class="form-check-label fw-semibold text-dark cursor-pointer mb-0 text-truncate" for="checkMateria_<?= $idMat ?>" style="max-width: 220px;">
                                                            [<?= $e($m['sigla']) ?>] - <?= $e($m['nombre']) ?>
                                                        </label>
                                                    </div>
                                                    <div style="width: 80px;">
                                                        <input type="number" class="form-control form-control-sm input-carga-materia text-center" 
                                                               name="cargaHoraria[<?= $idMat ?>]" 
                                                               min="1" max="20" placeholder="Horas" disabled required>
                                                    </div>
                                                </div>
                                                <?php 
                                            endforeach;
                                        }
                                    }
                                    ?>
                                    
                                    <div class="col-md-6">
                                        <?php renderColumnaMaterias($col1, $e); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php renderColumnaMaterias($col2, $e); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer justify-content-between align-items-center">
                    <div class="text-start">
                        <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                        <div class="text-primary fw-semibold fs-6">
                            <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualInfo['anio'] ?? '') ?>
                        </div>
                        <input type="hidden" name="idGestion" value="<?= $idGestionActual ?>">
                    </div>
                    <div class="text-center mx-auto px-2">
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-bold contador-periodos-disponibles" style="font-size: 0.88rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <i class="bi bi-clock-history text-primary me-1"></i> <span class="texto-contador-periodos">Tiene <strong class="periodos-restantes text-primary fs-6">35</strong> Periodos para asignar al curso</span>
                        </span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-jaegasa">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Carga del archivo JavaScript específico para Malla Curricular -->
<script src="<?= BASE_URL ?>Js/malla_curricular.js?v=<?= @filemtime(BASE_PATH . '/Js/malla_curricular.js') ?>"></script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>
