<?php
/**
 * vasignacion_docente.php — Vista del CRUD Asignación Docente
 * Recibe: $asignaciones, $gestiones, $cursos, $materias, $docentes, $flash, $buscar
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$accionGuardar  = BASE_URL . 'index.php?c=asignacion_docente&a=guardar';
$accionEliminar = BASE_URL . 'index.php?c=asignacion_docente&a=eliminar';

$fotoDefault = BASE_URL . 'inagenes/Admi/default.png';
$rutaFoto = fn($foto) => (!empty($foto) && $foto !== 'default.png')
    ? BASE_URL . 'inagenes/Docente/' . rawurlencode($foto)
    : $fotoDefault;

// Agrupar asignaciones por docente
$docentesAsignados = [];
foreach ($asignaciones as $a) {
    $idDoc = (int) $a['idDocente'];
    if (!isset($docentesAsignados[$idDoc])) {
        $docentesAsignados[$idDoc] = [
            'idDocente'       => $idDoc,
            'docenteNombre'   => $a['docenteNombre'],
            'docenteFoto'     => $a['docenteFoto'],
            'docenteCi'       => $a['docenteCi'],
            'docenteTelefono' => $a['docenteTelefono'],
            'docenteCorreo'   => $a['docenteCorreo'],
            'asignaciones'    => []
        ];
    }
    $docentesAsignados[$idDoc]['asignaciones'][] = $a;
}
?>

<!-- Carga del archivo CSS específico para Asignación Docente -->
<link rel="stylesheet" href="<?= BASE_URL ?>Css/asignacion_docente.css?v=<?= @filemtime(BASE_PATH . '/Css/asignacion_docente.css') ?>">

<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-person-badge-fill"></i> Asignación Docente</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Asignación de docentes a materias y cursos para cada gestión escolar</p>
    </div>
    <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="bi bi-plus-lg"></i> Asignación Docente
    </button>
</div>



<div class="card-panel">
    <div class="tabla-toolbar">
        <form method="get" action="<?= BASE_URL ?>index.php" class="buscador-form">
            <input type="hidden" name="c" value="asignacion_docente">
            
            <?php if (!empty($buscar)): ?>
                <a href="<?= BASE_URL ?>index.php?c=asignacion_docente" class="btn btn-sm btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </form>
        <div class="d-flex flex-column align-items-end justify-content-start align-self-start mt-1">
            <div class="mb-1 text-end">
                <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                <div class="text-primary fw-semibold fs-6">
                    <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualInfo['anio'] ?? date('Y')) ?>
                </div>
            </div>
            <span class="tabla-total m-0"><?= count($docentesAsignados) ?> DOCENTE(S) ASIGNADO(S)</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaAsignaciones">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Foto</th>
                    <th>Docente Asignado</th>
                    <th>C.I</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($docentesAsignados)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i> No se encontraron docentes con asignaciones.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = 0; foreach ($docentesAsignados as $idDoc => $doc): ?>
                        <tr>
                            <td><?= ++$i ?></td>
                            <td>
                                <img src="<?= $rutaFoto($doc['docenteFoto']) ?>" alt="Foto"
                                     class="tabla-foto"
                                     onerror="this.src='<?= $fotoDefault ?>'">
                            </td>
                            <td class="fw-semibold">
                                <span class="text-dark"><i class="bi bi-person-fill text-muted"></i> <?= $e($doc['docenteNombre']) ?></span>
                            </td>
                            <td><?= $e($doc['docenteCi'] ?: '—') ?></td>
                            <td><?= $e($doc['docenteTelefono'] ?: '—') ?></td>
                            <td class="text-muted small"><?= $e($doc['docenteCorreo'] ?: '—') ?></td>
                            <td class="text-end text-nowrap">
                                <div class="acciones-tabla">
                                    <button class="btn-accion btn-ver" title="" data-bs-toggle="modal" data-bs-target="#modalVer<?= $idDoc ?>"><i class="bi bi-eye-fill"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modales de Detalle y Edición (Agrupados por Docente) -->
<?php foreach ($docentesAsignados as $idDoc => $doc): ?>
    <!-- Modal Ver Detalles -->
    <div class="modal fade" id="modalVer<?= $idDoc ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-badge-fill"></i> Asignaciones del Docente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="detalle-cabecera">
                        <img src="<?= $rutaFoto($doc['docenteFoto']) ?>" alt="Foto"
                             onerror="this.src='<?= $fotoDefault ?>'">
                        <div>
                            <h3><?= $e($doc['docenteNombre']) ?></h3>
                            <span class="badge bg-primary text-white"><i class="bi bi-briefcase-fill"></i> Docente</span>
                        </div>
                    </div>

                    <h6 class="form-seccion mt-3">Información del Docente</h6>
                    <div class="detalle-grid mb-4">
                        <div class="detalle-item"><span>C.I.</span><b><?= $e($doc['docenteCi'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Teléfono</span><b><?= $e($doc['docenteTelefono'] ?: '—') ?></b></div>
                        <div class="detalle-item detalle-ancho"><span>Correo Electrónico</span><b><?= $e($doc['docenteCorreo'] ?: '—') ?></b></div>
                    </div>

                    <h6 class="form-seccion mt-3">Materias y Cursos Asignados</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Curso / Paralelo</th>
                                    <th>Materia / Sigla</th>
                                    <th>Gestión</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doc['asignaciones'] as $as): ?>
                                    <?php 
                                    $cDesc = trim($as['cursoNombre'] . ' ' . $as['paraleloNombre'] . ' (' . $as['nivelNombre'] . ' - ' . $as['turnoNombre'] . ')');
                                    ?>
                                    <tr>
                                        <td><span class="badge-curso py-1 px-2"><i class="bi bi-mortarboard-fill"></i> <?= $e($cDesc) ?></span></td>
                                        <td>
                                            <span class="badge-materia">
                                                <span class="sigla"><?= $e($as['materiaSigla']) ?></span> - <?= $e($as['materiaNombre']) ?>
                                            </span>
                                        </td>
                                        <td><span class="badge-gestion py-1 px-2"><i class="bi bi-calendar-event"></i> <?= $e($as['gestionAnio']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="text-start">
                        <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                        <div class="text-primary fw-semibold fs-6">
                            <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualInfo['anio'] ?? '') ?>
                        </div>
                        <input type="hidden" name="idGestion" value="<?= $idGestionActual ?>">
                    </div>
                    <div>
                        <button type="button" class="btn btn-warning text-dark fw-bold me-2" data-bs-toggle="modal" data-bs-target="#modalEditarDocente<?= $idDoc ?>" data-bs-dismiss="modal">
                            <i class="bi bi-pencil-square"></i> Editar Asignación
                        </button>
                        <button type="button" class="btn btn-danger text-white fw-bold" data-bs-toggle="modal" data-bs-target="#modalEliminarDocente<?= $idDoc ?>" data-bs-dismiss="modal">
                            <i class="bi bi-trash-fill"></i> Eliminar Asignaciones
                        </button>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Asignaciones del Docente -->
    <div class="modal fade" id="modalEditarDocente<?= $idDoc ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" autocomplete="off" class="form-asignacion-validador">
                    <input type="hidden" name="edicion_completa" value="1">
                    <input type="hidden" name="idDocente" value="<?= $idDoc ?>">
                    
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square"></i> Editar Asignaciones: <?= $e($doc['docenteNombre']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            <i class="bi bi-info-circle-fill"></i> Seleccione o desmarque los cursos y materias correspondientes para este docente. Las materias asignadas vendrán pre-marcadas y pre-seleccionadas.
                        </p>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold mb-2">Seleccione Cursos y Materias a Asignar *</label>
                                <div class="invalid-feedback feedback-grid-asignacion">Debe marcar al menos un curso y seleccionar su materia.</div>
                                
                                <div class="card bg-light border-0 shadow-sm p-3 asignacion-grid-wrapper">
                                    <div class="row text-muted small fw-bold pb-2 border-bottom mb-2 px-2">
                                        <div class="col-6">Curso / Grado / Paralelo</div>
                                        <div class="col-6">Materia / Asignatura disponible</div>
                                    </div>
                                    
                                    <div class="container-listado-cursos" style="max-height: 300px; overflow-y: auto;">
                                        <?php 
                                        // Agrupar asignaciones de este docente por curso
                                        $asignacionesDelDocentePorCurso = [];
                                        foreach ($doc['asignaciones'] as $as) {
                                            $idC = (int) $as['idCurso'];
                                            if (!isset($asignacionesDelDocentePorCurso[$idC])) {
                                                $asignacionesDelDocentePorCurso[$idC] = [];
                                            }
                                            $asignacionesDelDocentePorCurso[$idC][] = (int) $as['idMateria'];
                                        }
                                        
                                        foreach ($cursos as $c): 
                                            $idC = (int)$c['idCurso'];
                                            $optDesc = trim($c['nombre'] . ' "' . $c['paralelo'] . '" - ' . $c['nivel'] . ' (' . $c['turno'] . ')');
                                            
                                            // Obtener materias disponibles
                                            $mats = $materiasDisponibles[$idC] ?? [];
                                            
                                            // Asignaciones de este docente para este curso
                                            $matsAsignadas = $asignacionesDelDocentePorCurso[$idC] ?? [];
                                            $isChecked = !empty($matsAsignadas);
                                            
                                            // Asegurar que las materias asignadas del docente estén en la lista de opciones
                                            foreach ($matsAsignadas as $idMat) {
                                                $materiaYaEnLista = false;
                                                foreach ($mats as $m) {
                                                    if ((int)$m['idMateria'] === $idMat) {
                                                        $materiaYaEnLista = true;
                                                        break;
                                                    }
                                                }
                                                if (!$materiaYaEnLista) {
                                                    $materiaInfo = null;
                                                    foreach ($materias as $mat) {
                                                        if ((int)$mat['idMateria'] === $idMat) {
                                                            $materiaInfo = $mat;
                                                            break;
                                                        }
                                                    }
                                                    if ($materiaInfo) {
                                                        $mats[] = [
                                                            'idMateria'     => (int)$materiaInfo['idMateria'],
                                                            'materiaNombre' => $materiaInfo['nombre'],
                                                            'materiaSigla'  => $materiaInfo['sigla']
                                                        ];
                                                    }
                                                }
                                            }
                                            ?>
                                            <div class="row align-items-start py-2 border-bottom-dashed px-2 fila-curso-opcion <?= $isChecked ? 'active-row' : '' ?>" data-curso-id="<?= $idC ?>">
                                                <div class="col-6 d-flex align-items-center pt-2">
                                                    <div class="form-check mb-0">
                                                        <input class="form-check-input check-curso-select" type="checkbox" 
                                                               name="cursos[]" 
                                                               id="checkCurso_edit_doc_<?= $idDoc ?>_<?= $idC ?>" 
                                                               value="<?= $idC ?>" 
                                                               <?= $isChecked ? 'checked' : '' ?>>
                                                        <label class="form-check-label fw-semibold text-dark cursor-pointer" for="checkCurso_edit_doc_<?= $idDoc ?>_<?= $idC ?>">
                                                            <?= $e($optDesc) ?>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="materias-select-container" data-curso-id="<?= $idC ?>">
                                                        <?php if ($isChecked): ?>
                                                            <?php foreach ($matsAsignadas as $index => $idMat): ?>
                                                                <div class="materia-row mb-2 d-flex align-items-center gap-2">
                                                                    <select name="materias[<?= $idC ?>][]" class="form-select select-materia-curso" data-curso-id="<?= $idC ?>" required>
                                                                        <option value="">Seleccione materia...</option>
                                                                        <?php foreach ($mats as $m): ?>
                                                                            <option value="<?= $m['idMateria'] ?>" <?= ((int)$m['idMateria'] === $idMat) ? 'selected' : '' ?>>
                                                                                [<?= $e($m['materiaSigla']) ?>] - <?= $e($m['materiaNombre']) ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <?php if ($index === 0): ?>
                                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-agregar-materia" title="Agregar otra materia"><i class="bi bi-plus-lg"></i></button>
                                                                    <?php else: ?>
                                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remover-materia" title="Quitar materia"><i class="bi bi-dash-lg"></i></button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <div class="materia-row mb-2 d-flex align-items-center gap-2">
                                                                <select name="materias[<?= $idC ?>][]" class="form-select select-materia-curso" data-curso-id="<?= $idC ?>" disabled required>
                                                                    <option value="">Seleccione materia...</option>
                                                                    <?php foreach ($mats as $m): ?>
                                                                        <option value="<?= $m['idMateria'] ?>">
                                                                            [<?= $e($m['materiaSigla']) ?>] - <?= $e($m['materiaNombre']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <button type="button" class="btn btn-sm btn-outline-primary btn-agregar-materia" title="Agregar otra materia" disabled><i class="bi bi-plus-lg"></i></button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <div class="text-start">
                            <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                            <div class="text-primary fw-semibold fs-6">
                                <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualInfo['anio'] ?? '') ?>
                            </div>
                            <input type="hidden" name="idGestion" value="<?= $idGestionActual ?>">
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalVer<?= $idDoc ?>" data-bs-dismiss="modal">Volver</button>
                            <button type="submit" class="btn btn-jaegasa">Guardar Cambios</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Asignaciones del Docente -->
    <div class="modal fade" id="modalEliminarDocente<?= $idDoc ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" autocomplete="off">
                    <input type="hidden" name="edicion_agrupada" value="1">
                    <input type="hidden" name="idDocente" value="<?= $idDoc ?>">
                    
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-trash-fill"></i> Eliminar Asignaciones: <?= $e($doc['docenteNombre']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            <i class="bi bi-info-circle-fill"></i> Seleccione las asignaciones que desee eliminar de la malla docente. Al hacer clic en "Eliminar Seleccionados", las asignaciones marcadas serán dadas de baja de manera definitiva.
                        </p>
                        
                        <div class="list-group">
                            <?php foreach ($doc['asignaciones'] as $as): ?>
                                <?php 
                                $idAs = (int) $as['idAsignacion'];
                                $cDesc = trim($as['cursoNombre'] . ' ' . $as['paraleloNombre'] . ' (' . $as['nivelNombre'] . ' - ' . $as['turnoNombre'] . ')');
                                ?>
                                <label class="list-group-item d-flex align-items-center justify-content-between p-3 cursor-pointer">
                                    <div class="d-flex align-items-center gap-3">
                                        <input class="form-check-input me-0" type="checkbox" 
                                               name="asignaciones_a_eliminar[]" 
                                               value="<?= $idAs ?>" 
                                               style="width: 20px; height: 20px;">
                                        <div>
                                            <span class="badge-gestion me-2"><i class="bi bi-calendar-event"></i> <?= $e($as['gestionAnio']) ?></span>
                                            <span class="badge-curso me-2"><i class="bi bi-mortarboard-fill"></i> <?= $e($cDesc) ?></span>
                                            <span class="badge-materia">
                                                <span class="sigla"><?= $e($as['materiaSigla']) ?></span> - <?= $e($as['materiaNombre']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <div class="text-start">
                            <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                            <div class="text-primary fw-semibold fs-6">
                                <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualInfo['anio'] ?? '') ?>
                            </div>
                            <input type="hidden" name="idGestion" value="<?= $idGestionActual ?>">
                        </div>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalVer<?= $idDoc ?>" data-bs-dismiss="modal">Volver</button>
                        <button type="submit" class="btn btn-danger text-white fw-bold"><i class="bi bi-trash-fill"></i> Eliminar Seleccionados</button>
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
            <form action="<?= $accionGuardar ?>" method="post" autocomplete="off" class="form-asignacion-validador">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Asignación de Docente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idAsignacion" value="0">
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label fw-bold mb-1">Docente Asignado *</label>
                            <select class="form-select select-docente" name="idDocente" required>
                                <option value="">Seleccione el docente...</option>
                                <?php foreach ($docentes as $doc): 
                                    $idDoc = (int)$doc['idDocente'];
                                    if (isset($docentesAsignados[$idDoc])) {
                                        continue;
                                    }
                                ?>
                                    <option value="<?= $idDoc ?>">
                                        <?= $e($doc['nombreCompleto']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Debe seleccionar un docente.</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold mb-2">Seleccione Cursos y Materias a Asignar *</label>
                            <div class="invalid-feedback feedback-grid-asignacion">Debe marcar al menos un curso y seleccionar su materia.</div>
                            
                            <div class="card bg-light border-0 shadow-sm p-3 asignacion-grid-wrapper disabled-wrapper">
                                <div class="row text-muted small fw-bold pb-2 border-bottom mb-2 px-2">
                                    <div class="col-6">Curso / Grado / Paralelo</div>
                                    <div class="col-6">Materia / Asignatura disponible</div>
                                </div>
                                
                                <div class="container-listado-cursos" style="max-height: 300px; overflow-y: auto;">
                                    <?php foreach ($cursos as $c): ?>
                                        <?php 
                                        $idC = (int)$c['idCurso'];
                                        $optDesc = trim($c['nombre'] . ' "' . $c['paralelo'] . '" - ' . $c['nivel'] . ' (' . $c['turno'] . ')');
                                        
                                        // Obtener materias disponibles
                                        $mats = $materiasDisponibles[$idC] ?? [];
                                        ?>
                                        <div class="row align-items-start py-2 border-bottom-dashed px-2 fila-curso-opcion" data-curso-id="<?= $idC ?>">
                                            <div class="col-6 d-flex align-items-center pt-2">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input check-curso-select" type="checkbox" 
                                                           name="cursos[]" 
                                                           id="checkCurso_nuevo_<?= $idC ?>" 
                                                           value="<?= $idC ?>" disabled>
                                                    <label class="form-check-label fw-semibold text-dark cursor-pointer" for="checkCurso_nuevo_<?= $idC ?>">
                                                        <?= $e($optDesc) ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="materias-select-container" data-curso-id="<?= $idC ?>">
                                                    <div class="materia-row mb-2 d-flex align-items-center gap-2">
                                                        <select name="materias[<?= $idC ?>][]" class="form-select select-materia-curso" data-curso-id="<?= $idC ?>" disabled required>
                                                            <option value="">Seleccione materia...</option>
                                                            <?php foreach ($mats as $m): ?>
                                                                <option value="<?= $m['idMateria'] ?>">
                                                                    [<?= $e($m['materiaSigla']) ?>] - <?= $e($m['materiaNombre']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-agregar-materia" title="Agregar otra materia" disabled><i class="bi bi-plus-lg"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="text-start">
                        <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                        <div class="text-primary fw-semibold fs-6">
                            <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualInfo['anio'] ?? '') ?>
                        </div>
                        <input type="hidden" name="idGestion" value="<?= $idGestionActual ?>">
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-jaegasa">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<!-- Carga del archivo JavaScript específico para Asignación Docente -->
<script src="<?= BASE_URL ?>Js/asignacion_docente.js?v=<?= @filemtime(BASE_PATH . '/Js/asignacion_docente.js') ?>"></script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>
