<?php
/**
 * vnivel.php — Vista del CRUD Nivel (sin JS)
 * Recibe: $niveles, $flash, $buscar
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$accionGuardar  = BASE_URL . 'index.php?c=nivel&a=guardar';
$accionEliminar = BASE_URL . 'index.php?c=nivel&a=eliminar';
?>

<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-layers-fill"></i> Niveles</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Niveles académicos (Primaria, Secundaria...)</p>
    </div>
    <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="bi bi-plus-lg"></i> Nuevo nivel
    </button>
</div>



<div class="card-panel">
    <div class="tabla-toolbar">
        <form method="get" action="<?= BASE_URL ?>index.php" class="buscador-form">
            <input type="hidden" name="c" value="nivel">
            <div class="buscador">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="<?= $e($buscar ?? '') ?>" placeholder="Buscar nivel..." autocomplete="off">
            </div>
            <button type="submit" class="btn btn-sm btn-jaegasa">Buscar</button>
            <?php if (!empty($buscar)): ?>
                <a href="<?= BASE_URL ?>index.php?c=nivel" class="btn btn-sm btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </form>
        <div class="d-flex flex-column align-items-end justify-content-start align-self-start mt-1">
            <div class="mb-1 text-end">
                <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                <div class="text-primary fw-semibold fs-6">
                    <i class="bi bi-calendar2-check"></i> <?= $e($gestionActual ?? date('Y')) ?>
                </div>
            </div>
            <span class="tabla-total m-0"><?= count($niveles) ?> NIVELES</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaAdmin">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Hora inicio</th>
                    <th>Hora fin</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($niveles)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> No hay niveles registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($niveles as $i => $a): ?>
                        <?php $idN = (int) $a['idNivel']; ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= $e($a['nombre']) ?></td>
                            <td><?= $e($a['horaInicio'] ?: '—') ?></td>
                            <td><?= $e($a['horaFin'] ?: '—') ?></td>
                            <td class="text-end text-nowrap">
                                <div class="acciones-tabla">
                                    <button class="btn-accion btn-editar" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idN ?>"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="btn-accion btn-eliminar" title="Eliminar" data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $idN ?>"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($niveles as $a): $idN = (int) $a['idNivel']; ?>
    <!-- Editar -->
    <div class="modal fade" id="modalEditar<?= $idN ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar nivel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idNivel" value="<?= $idN ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="nombre" value="<?= $e($a['nombre']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hora inicio</label>
                                <input type="time" class="form-control" name="horaInicio" value="<?= $e($a['horaInicio']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hora fin</label>
                                <input type="time" class="form-control" name="horaFin" value="<?= $e($a['horaFin']) ?>">
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
    <!-- Eliminar -->
    <div class="modal fade" id="modalEliminar<?= $idN ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionEliminar ?>" method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Eliminar nivel</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idNivel" value="<?= $idN ?>">
                        <p>¿Está seguro de eliminar el nivel <b><?= $e($a['nombre']) ?></b>?</p>
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

<!-- Nuevo -->
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= $accionGuardar ?>" method="post" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo nivel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idNivel" value="0">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" name="nombre" placeholder="Ej. Primaria, Secundaria" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hora inicio</label>
                            <input type="time" class="form-control" name="horaInicio">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hora fin</label>
                            <input type="time" class="form-control" name="horaFin">
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

<?php require_once BASE_PATH . '/View/footer.php'; ?>
