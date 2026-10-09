<?php
/**
 * vaula.php — Vista del CRUD Aula
 * Recibe: $aulas, $flash, $buscar
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$accionGuardar  = BASE_URL . 'index.php?c=aula&a=guardar';
$accionEliminar = BASE_URL . 'index.php?c=aula&a=eliminar';

// Obtener docentes que ya tienen un aula asignada
$docentesConAula = [];
foreach ($aulas as $au) {
    if (!empty($au['idDocente'])) {
        $docentesConAula[(int)$au['idDocente']] = true;
    }
}
?>

<!-- Carga del archivo CSS específico para Aula -->
<link rel="stylesheet" href="<?= BASE_URL ?>Css/aula.css?v=<?= @filemtime(BASE_PATH . '/Css/aula.css') ?>">

<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-door-closed-fill"></i> Aulas</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Aulas y ambientes de la institución</p>
    </div>
    <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="bi bi-plus-lg"></i> Nueva aula
    </button>
</div>



<div class="card-panel">
    <div class="tabla-toolbar">
        <div class="d-flex flex-column align-items-end justify-content-start align-self-start ms-auto mt-1">
            <div class="mb-1 text-end">
                <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                <div class="text-primary fw-semibold fs-6">
                    <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualInfo['anio'] ?? $gestionActualAnio ?? date('Y')) ?>
                </div>
            </div>
            <span class="tabla-total m-0"><?= count($aulas) ?> AULAS</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaAulas">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Aula / Ambiente</th>
                    <th>Docente Encargado</th>
                    <th>Turno</th>
                    <th>Observación / Notas</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($aulas)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> No hay aulas registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($aulas as $i => $a): ?>
                        <?php $idA = (int) $a['idAula']; ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td class="fw-semibold"><span class="badge-aula"><i class="bi bi-door-closed"></i> <?= $e($a['nombre']) ?></span></td>
                            <td class="small"><?= $a['docenteNombre'] ? $e($a['docenteNombre']) : '<em class="text-light-gray">Sin docente</em>' ?></td>
                            <td>
                                <?php if ($a['turnoNombre']): ?>
                                    <span class="badge bg-secondary"><?= $e($a['turnoNombre']) ?></span>
                                <?php else: ?>
                                    <em class="text-light-gray">Sin turno</em>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= $a['observacion'] ? $e($a['observacion']) : '<em class="text-light-gray">Sin observación</em>' ?></td>
                           
                            <td class="text-end text-nowrap">
                                <div class="acciones-tabla">
                                    <button class="btn-accion btn-editar" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idA ?>"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="btn-accion btn-eliminar" title="Eliminar" data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $idA ?>"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($aulas as $a): $idA = (int) $a['idAula']; ?>
    <!-- Modal Editar -->
    <div class="modal fade" id="modalEditar<?= $idA ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" autocomplete="off" class="form-aula-validador">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar aula</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idAula" value="<?= $idA ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre del aula / ambiente *</label>
                                <input type="text" class="form-control input-nombre-aula" name="nombre" value="<?= $e($a['nombre']) ?>" required maxlength="30" placeholder="Ej: Aula 101, Laboratorio de Física">
                                <div class="invalid-feedback">El nombre es obligatorio y debe tener menos de 30 caracteres.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Docente Encargado</label>
                                <select class="form-select" name="idDocente">
                                    <option value="">Seleccione un docente (opcional)...</option>
                                    <?php foreach ($docentes as $d): 
                                        $idDoc = (int)$d['idDocente'];
                                        $isCurrentDoc = ((int)$a['idDocente'] === $idDoc);
                                        if (isset($docentesConAula[$idDoc]) && !$isCurrentDoc) {
                                            continue;
                                        }
                                    ?>
                                        <option value="<?= $idDoc ?>" <?= $isCurrentDoc ? 'selected' : '' ?>>
                                            <?= $e($d['nombreCompleto']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Turno</label>
                                <select class="form-select" name="idTurno">
                                    <option value="">Seleccione un turno (opcional)...</option>
                                    <?php foreach ($turnos as $t): ?>
                                        <option value="<?= (int) $t['idTurno'] ?>" <?= (int)$a['idTurno'] === (int)$t['idTurno'] ? 'selected' : '' ?>>
                                            <?= $e($t['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observación / Notas adicionales</label>
                                <textarea class="form-control" name="observacion" rows="3" maxlength="100" placeholder="Ej: Pabellón A, segundo piso, capacidad 35 estudiantes"><?= $e($a['observacion']) ?></textarea>
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
                            <button type="submit" class="btn btn-jaegasa">Guardar cambios</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar -->
    <div class="modal fade" id="modalEliminar<?= $idA ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionEliminar ?>" method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Eliminar aula</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idAula" value="<?= $idA ?>">
                        <p>¿Está seguro de eliminar el aula <b><?= $e($a['nombre']) ?></b>?</p>
                        <?php if ((int)$a['totalClases'] > 0): ?>
                            <div class="alert alert-warning mb-0 mt-2">
                                <i class="bi bi-exclamation-triangle-fill"></i> <strong>Atención:</strong> Este aula está vinculada a <b><?= (int)$a['totalClases'] ?> clases activas</b>. Al eliminarla, las clases asociadas podrían perder su asignación física.
                            </div>
                        <?php endif; ?>
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
                            <button type="submit" class="btn btn-danger"><i class="bi bi-trash-fill"></i> Eliminar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Modal Nuevo -->
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= $accionGuardar ?>" method="post" autocomplete="off" class="form-aula-validador">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva aula</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idAula" value="0">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre del aula / ambiente *</label>
                            <input type="text" class="form-control input-nombre-aula" name="nombre" required maxlength="30" placeholder="Ej: Aula 102, Taller de Computación">
                            <div class="invalid-feedback">El nombre es obligatorio y debe tener menos de 30 caracteres.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Docente Encargado</label>
                            <select class="form-select" name="idDocente">
                                <option value="">Seleccione un docente (opcional)...</option>
                                <?php foreach ($docentes as $d): 
                                    $idDoc = (int)$d['idDocente'];
                                    if (isset($docentesConAula[$idDoc])) {
                                        continue;
                                    }
                                ?>
                                    <option value="<?= $idDoc ?>">
                                        <?= $e($d['nombreCompleto']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Turno</label>
                            <select class="form-select" name="idTurno">
                                <option value="">Seleccione un turno (opcional)...</option>
                                <?php foreach ($turnos as $t): ?>
                                    <option value="<?= (int) $t['idTurno'] ?>">
                                        <?= $e($t['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observación / Notas adicionales</label>
                            <textarea class="form-control" name="observacion" rows="3" maxlength="100" placeholder="Ej: Pabellón B, primer piso, capacidad 30 estudiantes"></textarea>
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
</div>

<!-- Carga del archivo JavaScript específico para Aula -->
<script src="<?= BASE_URL ?>Js/aula.js?v=<?= @filemtime(BASE_PATH . '/Js/aula.js') ?>"></script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>
