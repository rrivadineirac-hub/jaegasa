<?php
/**
 * vhorario.php — Vista del Planificador de Horarios por Curso (MVC)
 * Recibe: $cursos, $idCurso, $periodos, $materias, $docentes, $horariosCurso, $flash
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$accionGuardar = BASE_URL . 'index.php?c=horario&a=guardar';

$diasGrid = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
$diasMap = [
    'Lunes'     => 'Lunes',
    'Martes'    => 'Martes',
    'Miercoles' => 'Miércoles',
    'Jueves'    => 'Jueves',
    'Viernes'   => 'Viernes'
];

// ------------------------------------------------------------
// Gestión activa e información de cursos para el selector
// ------------------------------------------------------------
$idCurso = $idCurso ?? 0;

$gestionActualAnio = (function () use ($cursos) {
    $anios = array_column($cursos, 'anio');
    return $anios ? max($anios) : (int) date('Y');
})();

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
    if (!empty($c['nombreCurso'])) {
        $cursosUnicos[$c['nombreCurso']] = $c['nombreCurso'];
    }
    $nivelesUnicos[$c['idNivel']] = $c['nivel'];
    $turnosUnicos[$c['idTurno']] = $c['turno'];
    $paralelosUnicos[$c['idParalelo']] = $c['paralelo'];
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

// Encontrar el curso seleccionado actualmente, para pre-seleccionar
$cursoSeleccionado = null;
if ($idCurso > 0) {
    foreach ($cursosGestionActual as $c) {
        if ((int)$c['idCurso'] === $idCurso) {
            $cursoSeleccionado = $c;
            break;
        }
    }
}
?>

<!-- Datos de cursos de la gestión activa, para resolver idCurso en el navegador -->
<script>
    const CURSOS_DATA = <?= json_encode(array_map(fn($c) => [
        'idCurso'     => (int) $c['idCurso'],
        'cursoNombre' => $c['nombreCurso'] ?? '',
        'idNivel'     => (int) $c['idNivel'],
        'idTurno'     => (int) $c['idTurno'],
        'idParalelo'  => (int) $c['idParalelo'],
    ], $cursosGestionActual)) ?>;
    
    const ASIGNACIONES_DOCENTE = <?= json_encode($asignacionesDocente ?? []) ?>;
    const AULAS_DOCENTE = <?= json_encode($aulasDocente ?? []) ?>;
</script>

<link rel="stylesheet" href="<?= BASE_URL ?>Css/horario.css?v=<?= @filemtime(BASE_PATH . '/Css/horario.css') ?>">

<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-calendar-week"></i> Horario de Clases</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Planificación horaria semanal por curso (Lunes a Viernes)</p>
    </div>
</div>



<!-- Selector de Curso (Cascada Grado, Paralelo, Nivel, Turno) -->
<div class="card-panel mb-4">
    <form method="get" action="<?= BASE_URL ?>index.php" id="formSeleccionCurso">
        <input type="hidden" name="c" value="horario">
        <!-- El idCurso resuelto por JS -->
        <input type="hidden" name="idCurso" id="idCursoResuelto" value="<?= $idCurso ?>">

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="form-seccion mb-0 fw-semibold text-jaegasa"><i class="bi bi-filter-square"></i> Planificación por Cursos</h6>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-secondary p-2"><i class="bi bi-info-circle-fill"></i> 7 Períodos + Recreo</span>
                <div class="text-end">
                    <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                    <div class="text-primary fw-semibold fs-6">
                        <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualInfo['anio'] ?? date('Y')) ?>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-muted small mb-3">Seleccione el curso, paralelo, nivel y turno para cargar su cuadrícula de horarios semanal.</p>

        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-semibold">Curso / Grado *</label>
                <select class="form-select curso-selector" data-tipo="grado" name="grado" <?= empty($cursosUnicos) ? 'disabled' : '' ?>>
                    <option value="">Seleccione...</option>
                    <?php foreach ($cursosUnicos as $id => $nomG): ?>
                        <option value="<?= $e($id) ?>" <?= ($cursoSeleccionado && $cursoSeleccionado['nombreCurso'] === $id) ? 'selected' : '' ?>><?= $e($nomG) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Paralelo *</label>
                <select class="form-select curso-selector" data-tipo="paralelo" name="paralelo" <?= empty($paralelosUnicos) ? 'disabled' : '' ?>>
                    <option value="">Seleccione...</option>
                    <?php foreach ($paralelosUnicos as $idP => $nomP): ?>
                        <option value="<?= (int) $idP ?>" <?= ($cursoSeleccionado && (int)$cursoSeleccionado['idParalelo'] === (int)$idP) ? 'selected' : '' ?>><?= $e($nomP) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Nivel *</label>
                <select class="form-select curso-selector" data-tipo="nivel" name="nivel" <?= empty($nivelesUnicos) ? 'disabled' : '' ?>>
                    <option value="">Seleccione...</option>
                    <?php foreach ($nivelesUnicos as $idN => $nomN): ?>
                        <option value="<?= (int) $idN ?>" <?= ($cursoSeleccionado && (int)$cursoSeleccionado['idNivel'] === (int)$idN) ? 'selected' : '' ?>><?= $e($nomN) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Turno *</label>
                <select class="form-select curso-selector" data-tipo="turno" name="turno" <?= empty($turnosUnicos) ? 'disabled' : '' ?>>
                    <option value="">Seleccione...</option>
                    <?php foreach ($turnosUnicos as $idT => $nomT): ?>
                        <option value="<?= (int) $idT ?>" <?= ($cursoSeleccionado && (int)$cursoSeleccionado['idTurno'] === (int)$idT) ? 'selected' : '' ?>><?= $e($nomT) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-jaegasa w-100" id="btnMostrar" disabled>
                    <i class="bi bi-eye-fill"></i> Mostrar
                </button>
                <a href="index.php?c=horario" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-1">
                    <i class="bi bi-trash-fill"></i> Limpiar
                </a>
            </div>
        </div>
        
        <!-- Aviso de curso no encontrado -->
        <div class="text-danger small mt-2 cursoNoEncontrado" hidden>
            <i class="bi bi-exclamation-triangle-fill"></i> El curso seleccionado no existe para la gestión actual.
        </div>
    </form>
</div>

<?php if ($idCurso > 0): ?>
    <!-- Cuadrícula Semanal Visual -->
    <div class="timetable-container">
        <div class="timetable-header-info d-flex justify-content-between align-items-center flex-wrap gap-2">
            <?php 
            $cur = null;
            if (!empty($cursos)) {
                foreach ($cursos as $cItem) {
                    if ((int)($cItem['idCurso'] ?? 0) === (int)$idCurso) {
                        $cur = $cItem;
                        break;
                    }
                }
            }
            if ($cur) {
                $nombreG   = $cur['nombreCurso'] ?? $cur['cursoNombre'] ?? '';
                $paraleloG = $cur['paralelo'] ?? '';
                $nivelG    = $cur['nivel'] ?? '';
                $turnoG    = $cur['turno'] ?? '';
                $cursoDetalle = 'Curso: ' . $nombreG . ', Paralelo: ' . $paraleloG . ', Nivel: ' . $nivelG . ', Turno: ' . $turnoG;
            } else {
                $cursoDetalle = '';
            }
            ?>
            <div class="flex-grow-1 text-center">
                <h3 class="timetable-header-title mb-1 fw-bold text-uppercase">HORARIO DEL CURSO</h3>
                <p class="timetable-header-subtitle mb-0 fs-6 text-dark fw-bold"><?= $e($cursoDetalle) ?></p>
            </div>
            <button type="button" class="btn btn-sm btn-success text-white fw-bold d-inline-flex align-items-center gap-2 px-3 py-2 shadow-sm no-print" onclick="window.print()" title="Imprimir horario escolar">
                <i class="bi bi-printer-fill fs-6"></i> Imprimir Horario
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="timetable-table">
                <thead>
                    <tr>
                        <th style="width: 120px;">Hora</th>
                        <?php foreach ($diasGrid as $dia): ?>
                            <?php $diaNormalizado = strtolower(str_replace(['Miercoles'], ['miercoles'], $dia)); ?>
                            <th class="th-color-<?= $diaNormalizado ?>"><?= $diasMap[$dia] ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($periodos)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-info-circle"></i> No hay períodos escolares registrados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($periodos as $per): ?>
                            <?php if ((int)$per['esRecreo'] === 1): ?>
                                <!-- Fila horizontal de RECREO -->
                                <tr class="timetable-row-recreo">
                                    <td class="timetable-period-col">
                                        <span class="timetable-period-num">RECREO</span>
                                        <span class="timetable-period-time">
                                            <?= date('H:i', strtotime($per['horaInicio'])) ?> - <?= date('H:i', strtotime($per['horaFin'])) ?>
                                        </span>
                                    </td>
                                    <td colspan="5" class="text-center align-middle fw-bold">
                                        <i class="bi bi-cup-hot-fill"></i> RECREO (<?= date('H:i', strtotime($per['horaInicio'])) ?> - <?= date('H:i', strtotime($per['horaFin'])) ?>)
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <!-- Columna del Período -->
                                    <td class="timetable-period-col">
                                        <span class="timetable-period-num"><?= (int)$per['numero'] ?></span>
                                        <span class="timetable-period-time">
                                            <?= date('H:i', strtotime($per['horaInicio'])) ?> - <?= date('H:i', strtotime($per['horaFin'])) ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Columnas de Lunes a Viernes -->
                                    <?php foreach ($diasGrid as $dia): ?>
                                        <?php 
                                        $hasAssignment = isset($horariosCurso[$dia][$per['idPeriodo']]);
                                        $assignment = $hasAssignment ? $horariosCurso[$dia][$per['idPeriodo']] : null;
                                        $diaNormalizado = strtolower(str_replace(['Miercoles'], ['miercoles'], $dia));
                                        ?>
                                        <td style="width: 180px; min-width: 180px; max-width: 180px; box-sizing: border-box;">
                                            <?php if ($hasAssignment): ?>
                                                <!-- Casillero Asignado -->
                                                <div class="timetable-cell-active color-<?= $diaNormalizado ?>" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalCelda_<?= $dia ?>_<?= $per['idPeriodo'] ?>"
                                                    style="cursor: pointer;">
                                                    <div class="cell-subject"><?= $e($assignment['materiaNombre']) ?> (<?= $e($assignment['materiaSigla']) ?>)</div>
                                                    <div class="cell-info">
                                                        <i class="bi bi-person-fill"></i> <?= $e($assignment['docenteNombre'] ?: 'Sin docente') ?>
                                                    </div>
                                                    <div class="cell-aula">
                                                        <i class="bi bi-door-closed"></i>
                                                        <?= !empty($assignment['aula']) ? $e($assignment['aula']) : '<span class="text-muted">Sin aula asignada</span>' ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <!-- Casillero Vacío -->
                                                <div class="timetable-cell-empty" 
                                                     data-bs-toggle="modal" 
                                                     data-bs-target="#modalCelda_<?= $dia ?>_<?= $per['idPeriodo'] ?>" 
                                                     style="cursor: pointer; height: 100%; border: 2px dashed #d1d5db; border-radius: 8px; min-height: 74px;">
                                                    <span class="text-muted"><i class="bi bi-plus-lg"></i> Asignar</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Leyenda explicativa -->
        <div class="timetable-legend">
            <div class="legend-item"><span class="legend-color" style="background-color: #b5c71a;"></span> Lunes</div>
            <div class="legend-item"><span class="legend-color" style="background-color: #8b5cf6;"></span> Martes</div>
            <div class="legend-item"><span class="legend-color" style="background-color: #eab308;"></span> Miércoles</div>
            <div class="legend-item"><span class="legend-color" style="background-color: #06b6d4;"></span> Jueves</div>
            <div class="legend-item"><span class="legend-color" style="background-color: #f43f5e;"></span> Viernes</div>
        </div>
    </div>

    <!-- Generación dinámica de modales por cada casilla de la cuadrícula -->
    <?php foreach ($diasGrid as $dia): ?>
        <?php foreach ($periodos as $per): ?>
            <?php 
            if ((int)$per['esRecreo'] === 1) continue; 
            $hasAssignment = isset($horariosCurso[$dia][$per['idPeriodo']]);
            $assignment = $hasAssignment ? $horariosCurso[$dia][$per['idPeriodo']] : null;
            ?>
            <!-- Modal para celda <?= $dia ?> Período <?= $per['idPeriodo'] ?> -->
            <div class="modal fade" id="modalCelda_<?= $dia ?>_<?= $per['idPeriodo'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <!-- Formulario de Guardar -->
                        <form id="formGuardar_<?= $dia ?>_<?= $per['idPeriodo'] ?>" action="<?= $accionGuardar ?>" method="post" autocomplete="off">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-calendar-plus-fill text-primary"></i> 
                                    <?= $hasAssignment ? 'Editar Asignación' : 'Nueva Asignación' ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="idCurso" value="<?= $idCurso ?>">
                                <input type="hidden" name="diaSemana" value="<?= $dia ?>">
                                <input type="hidden" name="idPeriodo" value="<?= $per['idPeriodo'] ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">DÍA Y PERÍODO</label>
                                    <div class="bg-light p-2 rounded text-dark">
                                        <b><?= $diasMap[$dia] ?></b> — Período <?= (int)$per['numero'] ?> (<?= date('H:i', strtotime($per['horaInicio'])) ?> - <?= date('H:i', strtotime($per['horaFin'])) ?>)
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Materia *</label>
                                    <select class="form-select" name="idMateria" required>
                                        <option value="">Seleccione Materia...</option>
                                        <?php 
                                        $assignedMatInList = false;
                                        if ($hasAssignment) {
                                            foreach ($materias as $mat) {
                                                if ((int)$mat['idMateria'] === (int)$assignment['idMateria']) {
                                                    $assignedMatInList = true;
                                                    break;
                                                }
                                            }
                                        }
                                        if ($hasAssignment && !$assignedMatInList): 
                                        ?>
                                            <option value="<?= (int)$assignment['idMateria'] ?>" selected>[<?= $e($assignment['materiaSigla']) ?>] <?= $e($assignment['materiaNombre']) ?> (Asignada previamente)</option>
                                        <?php endif; ?>

                                        <?php if (empty($materias) && !$hasAssignment): ?>
                                            <option value="" disabled>Sin materias registradas en la malla curricular de este curso</option>
                                        <?php else: ?>
                                            <?php foreach ($materias as $mat): ?>
                                                <?php 
                                                $idMat = (int)$mat['idMateria'];
                                                $carga = $mat['cargaHoraria'] !== null ? (int)$mat['cargaHoraria'] : null;
                                                $cantAsignada = $conteoMaterias[$idMat] ?? 0;
                                                $isSelected = ($hasAssignment && (int)$assignment['idMateria'] === $idMat);
                                                $cantEnOtros = $isSelected ? max(0, $cantAsignada - 1) : $cantAsignada;
                                                
                                                // Si la materia ya alcanzó su tope semanal en otras celdas y no está asignada aquí, no la mostramos
                                                if ($carga !== null && $carga > 0 && $cantEnOtros >= $carga && !$isSelected) {
                                                    continue;
                                                }

                                                $infoCarga = ($carga !== null && $carga > 0) ? " ({$cantAsignada}/{$carga} per.)" : "";
                                                ?>
                                                <option value="<?= $idMat ?>" <?= $isSelected ? 'selected' : '' ?>>[<?= $e($mat['sigla']) ?>] <?= $e($mat['nombre']) ?><?= $infoCarga ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                     <label class="form-label fw-bold">Docente</label>
                                     <div class="docente-display-container p-2 border rounded bg-light fw-semibold" style="min-height: 38px; display: flex; align-items: center;">
                                         <span class="docente-display-text text-secondary"></span>
                                     </div>
                                     <input type="hidden" class="docente-hidden-input" name="idDocente" value="<?= $hasAssignment ? (int)$assignment['idDocente'] : '0' ?>">
                                 </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Aula</label>
                                    <input type="text" class="form-control aula-input" name="aula" value="<?= $hasAssignment ? $e($assignment['aula']) : '' ?>" placeholder="">
                                </div>
                            </div>
                        </form>

                        <!-- Formulario de Eliminar (fuera del de Guardar, se activa por botón en modal-footer) -->
                        <form id="formEliminar_<?= $dia ?>_<?= $per['idPeriodo'] ?>" action="<?= BASE_URL ?>index.php?c=horario&a=eliminar" method="post">
                            <input type="hidden" name="idCurso" value="<?= $idCurso ?>">
                            <input type="hidden" name="diaSemana" value="<?= $dia ?>">
                            <input type="hidden" name="idPeriodo" value="<?= $per['idPeriodo'] ?>">
                        </form>
                        
                        <div class="modal-footer justify-content-between align-items-center">
                            <div class="text-start">
                                <label class="form-label fw-bold mb-0 text-muted small" style="font-size: 11px;">Gestión Escolar</label>
                                <div class="text-primary fw-semibold fs-6">
                                    <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualInfo['anio'] ?? date('Y')) ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($hasAssignment): ?>
                                    <button type="submit" form="formEliminar_<?= $dia ?>_<?= $per['idPeriodo'] ?>" class="btn btn-danger text-white fw-bold me-2">
                                        <i class="bi bi-trash-fill"></i> Eliminar
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" form="formGuardar_<?= $dia ?>_<?= $per['idPeriodo'] ?>" class="btn btn-jaegasa">
                                    <i class="bi bi-save"></i> Guardar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selGrado    = document.querySelector('[data-tipo="grado"]');
    const selParalelo = document.querySelector('[data-tipo="paralelo"]');
    const selNivel    = document.querySelector('[data-tipo="nivel"]');
    const selTurno    = document.querySelector('[data-tipo="turno"]');
    const hiddenCurso = document.getElementById('idCursoResuelto');
    const avisoCurso  = document.querySelector('.cursoNoEncontrado');
    const btnMostrar  = document.getElementById('btnMostrar');

    if (selGrado && selParalelo && selNivel && selTurno && hiddenCurso && btnMostrar) {
        const resolverCurso = function () {
            const grado = selGrado.value;
            const paralelo = parseInt(selParalelo.value, 10);
            const nivel = parseInt(selNivel.value, 10);
            const turno = parseInt(selTurno.value, 10);

            let match = null;
            if (grado && paralelo && nivel && turno) {
                if (typeof CURSOS_DATA !== 'undefined') {
                    match = CURSOS_DATA.find(function (c) {
                        return c.cursoNombre === grado 
                            && c.idParalelo === paralelo 
                            && c.idNivel === nivel 
                            && c.idTurno === turno;
                    });
                }
            }

            hiddenCurso.value = match ? match.idCurso : '';

            if (grado && paralelo && nivel && turno) {
                avisoCurso.hidden = !!match;
                btnMostrar.disabled = !match;
            } else {
                avisoCurso.hidden = true;
                btnMostrar.disabled = true;
            }
        };

        [selGrado, selParalelo, selNivel, selTurno].forEach(function (s) {
            s.addEventListener('change', resolverCurso);
        });

        // Habilitar inicialmente el botón si ya está seleccionado un curso cargado
        resolverCurso();
    }

    // ============================================================
    // Manejo dinámico del Docente según la Materia seleccionada
    // ============================================================
    const materiaSelects = document.querySelectorAll('select[name="idMateria"]');
    
    materiaSelects.forEach(function (select) {
        const form = select.closest('form');
        if (!form) return;
        
        const docenteText = form.querySelector('.docente-display-text');
        const docenteInput = form.querySelector('.docente-hidden-input');
        const aulaInput = form.querySelector('.aula-input');
        
        const actualizarDocente = function () {
            const idMateria = select.value;
            if (!idMateria) {
                if (docenteText) {
                    docenteText.textContent = '';
                    docenteText.className = 'docente-display-text text-secondary';
                }
                if (docenteInput) docenteInput.value = '0';
                if (aulaInput) aulaInput.value = '';
                return;
            }
            
            let idDocenteResolved = 0;
            if (typeof ASIGNACIONES_DOCENTE !== 'undefined' && ASIGNACIONES_DOCENTE[idMateria]) {
                const asig = ASIGNACIONES_DOCENTE[idMateria];
                if (docenteText) {
                    docenteText.textContent = asig.nombreCompleto;
                    docenteText.className = 'docente-display-text text-success';
                }
                if (docenteInput) docenteInput.value = asig.idDocente;
                idDocenteResolved = asig.idDocente;
            } else {
                if (docenteText) {
                    docenteText.textContent = 'Docente por asignar';
                    docenteText.className = 'docente-display-text text-danger';
                }
                if (docenteInput) docenteInput.value = '0';
            }
            
            // Resolver Aula según el docente asignado
            if (idDocenteResolved > 0 && typeof AULAS_DOCENTE !== 'undefined' && AULAS_DOCENTE[idDocenteResolved]) {
                if (aulaInput) {
                    aulaInput.value = AULAS_DOCENTE[idDocenteResolved];
                }
            } else {
                if (aulaInput) {
                    aulaInput.value = 'Aula por designar';
                }
            }
        };
        
        select.addEventListener('change', actualizarDocente);
        actualizarDocente(); // Inicializar para edición
    });
});
</script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>
