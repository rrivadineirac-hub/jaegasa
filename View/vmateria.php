<?php
/**
 * vmateria.php — Vista del CRUD Materia
 * Recibe: $materias, $flash, $buscar
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$accionGuardar  = BASE_URL . 'index.php?c=materia&a=guardar';
$accionEliminar = BASE_URL . 'index.php?c=materia&a=eliminar';
?>

<!-- Carga del archivo CSS específico para Materia -->
<link rel="stylesheet" href="<?= BASE_URL ?>Css/materia.css?v=<?= @filemtime(BASE_PATH . '/Css/materia.css') ?>">

<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-book-half"></i> Materias</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Catálogo global de materias e identificaciones</p>
    </div>
    <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="bi bi-plus-lg"></i> Nueva materia
    </button>
</div>



<div class="card-panel">
    <div class="tabla-toolbar">
        <div class="d-flex flex-column align-items-end justify-content-start align-self-start ms-auto mt-1">
            <div class="mb-1 text-end">
                <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                <div class="text-primary fw-semibold fs-6">
                    <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualAnio ?? $gestionActual ?? date('Y')) ?>
                </div>
            </div>
            <span class="tabla-total m-0"><?= count($materias) ?> MATERIAS</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaMaterias">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Materia / Asignatura</th>
                    <th>Sigla</th>
                    <th>Fecha Registro</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($materias)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i> No hay materias registradas.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($materias as $i => $m): ?>
                        <?php $idM = (int) $m['idMateria']; ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td class="fw-semibold">
                                <span class="badge-materia-main"><i class="bi bi-book"></i> <?= $e($m['nombre']) ?></span>
                            </td>
                            <td>
                                <?php if ($m['sigla']): ?>
                                    <span class="badge-sigla"><?= $e($m['sigla']) ?></span>
                                <?php else: ?>
                                    <em class="text-light-gray">Sin sigla</em>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= date('d/m/Y', strtotime($m['fechaRegistro'])) ?></td>
                            <td class="text-end text-nowrap">
                                <div class="acciones-tabla">
                                    <button class="btn-accion btn-editar" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idM ?>"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="btn-accion btn-eliminar" title="Eliminar" data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $idM ?>"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modales de Edición y Eliminación -->
<?php foreach ($materias as $m): ?>
    <?php $idM = (int) $m['idMateria']; ?>

    <!-- Modal Editar -->
    <div class="modal fade" id="modalEditar<?= $idM ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" autocomplete="off" class="form-materia-validador">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar materia</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idMateria" value="<?= $idM ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre de la materia *</label>
                                <input type="text" class="form-control input-nombre-materia" name="nombre" value="<?= $e($m['nombre']) ?>" required maxlength="80" placeholder="Ej: Matemática, Biología-Geografía">
                                <div class="invalid-feedback">El nombre es obligatorio y debe tener menos de 80 caracteres.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Sigla o Código abreviado</label>
                                <input type="text" class="form-control input-sigla-materia" name="sigla" value="<?= $e($m['sigla']) ?>" maxlength="15" placeholder="Ej: MAT, BIO-GEO">
                                <div class="invalid-feedback">La sigla debe tener menos de 15 caracteres.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between align-items-center">
                        <div class="text-start">
                            <label class="form-label fw-bold mb-0 text-muted small" style="font-size: 11px;">Gestión Escolar</label>
                            <div class="text-primary fw-semibold fs-6">
                                <i class="bi bi-calendar2-check"></i> <?= $e($gestionActual ?? date('Y')) ?>
                            </div>
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
    <div class="modal fade" id="modalEliminar<?= $idM ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionEliminar ?>" method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Eliminar materia</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idMateria" value="<?= $idM ?>">
                        <p>¿Está seguro de eliminar la materia <b><?= $e($m['nombre']) ?></b>?</p>
                        <div class="alert alert-warning mb-0 mt-2">
                            <i class="bi bi-exclamation-triangle-fill"></i> <strong>Atención:</strong> Al eliminar esta materia, se dará de baja en el catálogo de materias. Esto podría afectar a los cursos y mallas curriculares que la tengan vinculada en la gestión actual.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash-fill"></i> Eliminar</button>
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
            <form action="<?= $accionGuardar ?>" method="post" autocomplete="off" class="form-materia-validador">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva materia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idMateria" value="0">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre de la materia *</label>
                            <input type="text" class="form-control input-nombre-materia" name="nombre" required maxlength="80" placeholder="Ej: Lenguaje y Comunicación, Ciencias Sociales">
                            <div class="invalid-feedback">El nombre es obligatorio y debe tener menos de 80 caracteres.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sigla o Código abreviado</label>
                            <input type="text" class="form-control input-sigla-materia" name="sigla" maxlength="15" placeholder="Ej: LENG, SOC">
                            <div class="invalid-feedback">La sigla debe tener menos de 15 caracteres.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between align-items-center">
                    <div class="text-start">
                        <label class="form-label fw-bold mb-0 text-muted small" style="font-size: 11px;">Gestión Escolar</label>
                        <div class="text-primary fw-semibold fs-6">
                            <i class="bi bi-calendar2-check"></i> <?= $e($gestionActual ?? date('Y')) ?>
                        </div>
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

<!-- Carga del archivo JavaScript específico para Materia -->
<script src="<?= BASE_URL ?>Js/materia.js?v=<?= @filemtime(BASE_PATH . '/Js/materia.js') ?>"></script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>
