<?php
/**
 * vcurso.php — Vista del CRUD Curso (sin JS)
 * Recibe: $cursos, $gestiones, $niveles, $turnos, $paralelos, $horarios, $flash, $buscar
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$accionGuardar  = BASE_URL . 'index.php?c=curso&a=guardar';
$accionEliminar = BASE_URL . 'index.php?c=curso&a=eliminar';

// Helpers para generar <option> reutilizables en nuevo y editar
$optGestiones = function ($sel = 0) use ($gestiones, $e) {
    $h = '<option value="">Seleccione...</option>';
    foreach ($gestiones as $g) {
        $s = ((int) $g['idGestion'] === (int) $sel) ? ' selected' : '';
        $h .= '<option value="' . (int) $g['idGestion'] . '"' . $s . '>' . $e($g['anio']) . '</option>';
    }
    return $h;
};
$optNiveles = function ($sel = 0) use ($niveles, $e) {
    $h = '<option value="">Seleccione...</option>';
    foreach ($niveles as $n) {
        $s = ((int) $n['idNivel'] === (int) $sel) ? ' selected' : '';
        $h .= '<option value="' . (int) $n['idNivel'] . '"' . $s . '>' . $e($n['nombre']) . '</option>';
    }
    return $h;
};
$optTurnos = function ($sel = 0) use ($turnos, $e) {
    $h = '<option value="">Seleccione...</option>';
    foreach ($turnos as $t) {
        $s = ((int) $t['idTurno'] === (int) $sel) ? ' selected' : '';
        $h .= '<option value="' . (int) $t['idTurno'] . '"' . $s . '>' . $e($t['nombre']) . '</option>';
    }
    return $h;
};
$optParalelos = function ($sel = 0) use ($paralelos, $e) {
    $h = '<option value="">Seleccione...</option>';
    foreach ($paralelos as $p) {
        $s = ((int) $p['idParalelo'] === (int) $sel) ? ' selected' : '';
        $h .= '<option value="' . (int) $p['idParalelo'] . '"' . $s . '>' . $e($p['nombre']) . '</option>';
    }
    return $h;
};
$optHorarios = function ($sel = 0) use ($horarios, $e) {
    $h = '<option value="">Sin horario</option>';
    foreach ($horarios as $ho) {
        $s = ((int) $ho['idHorario'] === (int) $sel) ? ' selected' : '';
        $h .= '<option value="' . (int) $ho['idHorario'] . '"' . $s . '>' . $e($ho['descripcion']) . '</option>';
    }
    return $h;
};
?>

<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-collection-fill"></i> Cursos</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Gestión + Nivel + Turno + Paralelo</p>
    </div>
    <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="bi bi-plus-lg"></i> Nuevo curso
    </button>
</div>



<?php if (empty($gestiones) || empty($niveles) || empty($turnos) || empty($paralelos)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill"></i>
        Para crear cursos necesita tener registrados: gestiones, niveles, turnos y paralelos.
    </div>
<?php endif; ?>

<div class="card-panel">
    <div class="tabla-toolbar">
        <form method="get" action="<?= BASE_URL ?>index.php" class="buscador-form">
            <input type="hidden" name="c" value="curso">
            
        </form>
        <div class="d-flex flex-column align-items-end justify-content-start align-self-start mt-1">
            <div class="mb-1 text-end">
                <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                <div class="text-primary fw-semibold fs-6">
                    <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualAnio ?? $gestionActual ?? date('Y')) ?>
                </div>
            </div>
            <span class="tabla-total m-0"><?= count($cursos) ?> CURSO(S) REGISTRADO(S)</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaAdmin">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Nivel</th>
                    <th>Paralelo</th>
                    <th>Turno</th>
                    <th>Gestión</th>
                    <th>Horario</th>
                    <th>Cupo</th>
                    <th>Inscritos</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cursos)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> No hay cursos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($cursos as $i => $a): ?>
                        <?php $idC = (int) $a['idCurso']; ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= $e($a['nombre'] ?: '—') ?></td>
                            <td><?= $e($a['nivel']) ?></td>
                            <td><span class="badge-cat"><?= $e($a['paralelo']) ?></span></td>
                            <td><?= $e($a['turno']) ?></td>
                            <td><?= $e($a['anio']) ?></td>
                            <td><?= $e($a['horario'] ?: '—') ?></td>
                            <td><span class="badge bg-secondary"><?= (int) ($a['cupo'] ?? 36) ?></span></td>
                            <td><?= (int) $a['inscritos'] ?></td>
                            <td class="text-end text-nowrap">
                                <div class="acciones-tabla">
                                    <button class="btn-accion btn-editar" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idC ?>"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="btn-accion btn-eliminar" title="Eliminar" data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $idC ?>"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($cursos as $a): $idC = (int) $a['idCurso']; ?>
    <!-- Editar -->
    <div class="modal fade" id="modalEditar<?= $idC ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar curso</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idCurso" value="<?= $idC ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Gestión *</label>
                                <select class="form-select" name="idGestion" required><?= $optGestiones($a['idGestion']) ?></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nivel *</label>
                                <select class="form-select" name="idNivel" required><?= $optNiveles($a['idNivel']) ?></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Turno *</label>
                                <select class="form-select" name="idTurno" required><?= $optTurnos($a['idTurno']) ?></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Paralelo *</label>
                                <select class="form-select" name="idParalelo" required><?= $optParalelos($a['idParalelo']) ?></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Horario</label>
                                <select class="form-select" name="idHorario"><?= $optHorarios($a['idHorario']) ?></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cupo máximo *</label>
                                <input type="number" class="form-control" name="cupo" value="<?= (int) ($a['cupo'] ?? 36) ?>" min="1" max="100" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre (opcional)</label>
                                <input type="text" class="form-control" name="nombre" value="<?= $e($a['nombre']) ?>">
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
    <div class="modal fade" id="modalEliminar<?= $idC ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionEliminar ?>" method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Eliminar curso</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idCurso" value="<?= $idC ?>">
                        <p>¿Está seguro de eliminar el curso <b><?= $e(trim($a['nivel'] . ' ' . $a['paralelo'] . ' - ' . $a['turno'] . ' (' . $a['anio'] . ')')) ?></b>?</p>
                        <p class="text-muted mb-0"><small>No se podrá eliminar si tiene estudiantes inscritos.</small></p>
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
                    <h5 class="modal-title">Nuevo curso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idCurso" value="0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Gestión *</label>
                            <select class="form-select" name="idGestion" required><?= $optGestiones() ?></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nivel *</label>
                            <select class="form-select" name="idNivel" required><?= $optNiveles() ?></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Turno *</label>
                            <select class="form-select" name="idTurno" required><?= $optTurnos() ?></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Paralelo *</label>
                            <select class="form-select" name="idParalelo" required><?= $optParalelos() ?></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Horario</label>
                            <select class="form-select" name="idHorario"><?= $optHorarios() ?></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cupo máximo *</label>
                            <input type="number" class="form-control" name="cupo" value="36" min="1" max="100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre (opcional)</label>
                            <input type="text" class="form-control" name="nombre" placeholder="Ej. 1ro A Primaria">
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
