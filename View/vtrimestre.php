<?php
/**
 * ============================================================
 * vtrimestre.php — Vista del CRUD Trimestre
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Permite listar, configurar las fechas manuales de inicio y fin,
 * e inicializar los 3 trimestres de cada gestión académica.
 * ============================================================
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$accionGuardar     = BASE_URL . 'index.php?c=trimestre&a=guardar';
$accionEliminar    = BASE_URL . 'index.php?c=trimestre&a=eliminar';
$accionInicializar = BASE_URL . 'index.php?c=trimestre&a=inicializar';

// Organizar trimestres por su número (1, 2, 3) para las tarjetas superiores
$trimestresPorNumero = [1 => null, 2 => null, 3 => null];
foreach ($trimestres as $t) {
    $num = (int)$t['numero'];
    if ($num >= 1 && $num <= 3) {
        $trimestresPorNumero[$num] = $t;
    }
}
?>

<!-- Carga del CSS específico de Trimestre -->
<link rel="stylesheet" href="<?= BASE_URL ?>Css/trimestre.css?v=<?= @filemtime(BASE_PATH . '/Css/trimestre.css') ?>">

<div class="dash-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1"><i class="bi bi-calendar3-range"></i> Trimestres Académicos</h1>
        <p class="page-subtitle mb-0">Programación manual de inicio, fin y estado de los 3 trimestres lectivos</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if ($idGestionSel > 0): ?>
            <form action="<?= $accionInicializar ?>" method="post" class="d-inline">
                <input type="hidden" name="idGestion" value="<?= $idGestionSel ?>">
                <button type="submit" class="btn btn-outline-primary" title="Generar automáticamente los 3 trimestres para esta gestión">
                    <i class="bi bi-arrow-repeat"></i> Restablecer 3 Trimestres
                </button>
            </form>
        <?php endif; ?>
        <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
            <i class="bi bi-plus-lg"></i> Agregar Trimestre
        </button>
    </div>
</div>

<!-- Selector de Gestión y Barra de Búsqueda -->
<div class="card-panel mb-4">
    <div class="row g-3 align-items-center justify-content-between">
        <div class="col-md-5 col-lg-4">
            <form action="<?= BASE_URL ?>index.php" method="get" class="d-flex align-items-center gap-2">
                <input type="hidden" name="c" value="trimestre">
                <label for="selectGestionFiltro" class="form-label mb-0 text-nowrap fw-semibold text-muted">Gestión Escolar:</label>
                <select name="idGestion" id="selectGestionFiltro" class="form-select form-select-md fw-bold text-primary" onchange="this.form.submit()">
                    <?php foreach ($gestiones as $g): ?>
                        <option value="<?= (int)$g['idGestion'] ?>" <?= (int)$g['idGestion'] === $idGestionSel ? 'selected' : '' ?>>
                            Gestión <?= $e($g['anio']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="col-md-7 col-lg-5 ms-auto">
            <form action="<?= BASE_URL ?>index.php" method="get" class="d-flex gap-2">
                <input type="hidden" name="c" value="trimestre">
                <input type="hidden" name="idGestion" value="<?= $idGestionSel ?>">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Buscar por trimestre u observación..." value="<?= $e($buscar) ?>">
                    <?php if ($buscar !== ''): ?>
                        <a href="<?= BASE_URL ?>index.php?c=trimestre&idGestion=<?= $idGestionSel ?>" class="btn btn-outline-secondary">Limpiar</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TARJETAS RESUMEN DE LOS 3 TRIMESTRES DE LA GESTIÓN -->
<div class="trimestres-grid">
    <?php for ($n = 1; $n <= 3; $n++): 
        $tri = $trimestresPorNumero[$n];
        $claseNum = "t-{$n}";
        $badgeNum = "badge-trimestre-{$n}";
        $nombreDefecto = $n === 1 ? 'Primer Trimestre' : ($n === 2 ? 'Segundo Trimestre' : 'Tercer Trimestre');
    ?>
        <div class="card-trimestre-resumen <?= $claseNum ?>">
            <div class="card-trimestre-header">
                <span class="badge-trimestre <?= $badgeNum ?>">
                    <i class="bi bi-calendar-event"></i> Trimestre <?= $n ?>
                </span>
                <?php if ($tri): ?>
                    <?php 
                    $estClase = match($tri['estadoTrimestre']) {
                        'En Curso' => 'estado-en-curso',
                        'Cerrado'  => 'estado-cerrado',
                        default    => 'estado-pendiente'
                    };
                    $estIcono = match($tri['estadoTrimestre']) {
                        'En Curso' => 'bi-play-circle-fill',
                        'Cerrado'  => 'bi-check-circle-fill',
                        default    => 'bi-clock-history'
                    };
                    ?>
                    <span class="estado-badge <?= $estClase ?>">
                        <i class="bi <?= $estIcono ?>"></i> <?= $e($tri['estadoTrimestre']) ?>
                    </span>
                <?php else: ?>
                    <span class="estado-badge estado-pendiente">Sin Configurar</span>
                <?php endif; ?>
            </div>

            <h5 class="fw-bold text-dark mb-3"><?= $tri ? $e($tri['nombre']) : $nombreDefecto ?></h5>

            <div class="card-trimestre-body mb-3">
                <div class="fecha-item">
                    <span class="label"><i class="bi bi-box-arrow-in-right text-primary"></i> Fecha Inicio:</span>
                    <span class="val"><?= ($tri && !empty($tri['fechaInicio'])) ? date('d/m/Y', strtotime($tri['fechaInicio'])) : '<em class="text-muted">No definida</em>' ?></span>
                </div>
                <div class="fecha-item">
                    <span class="label"><i class="bi bi-box-arrow-right text-danger"></i> Fecha Fin:</span>
                    <span class="val"><?= ($tri && !empty($tri['fechaFin'])) ? date('d/m/Y', strtotime($tri['fechaFin'])) : '<em class="text-muted">No definida</em>' ?></span>
                </div>
                
                <?php if ($tri && !empty($tri['fechaInicio']) && !empty($tri['fechaFin'])): 
                    $d1 = new DateTime($tri['fechaInicio']);
                    $d2 = new DateTime($tri['fechaFin']);
                    $dias = $d1->diff($d2)->days + 1;
                ?>
                    <div class="duracion-badge">
                        <i class="bi bi-hourglass-split"></i> Duración: <strong><?= $dias ?> días</strong> (<?= round($dias / 7, 1) ?> semanas)
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($tri): ?>
                <button class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modalEditar<?= (int)$tri['idTrimestre'] ?>">
                    <i class="bi bi-pencil-square"></i> Editar Fechas y Estado
                </button>
            <?php else: ?>
                <form action="<?= $accionInicializar ?>" method="post">
                    <input type="hidden" name="idGestion" value="<?= $idGestionSel ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-plus-circle"></i> Inicializar Trimestre
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endfor; ?>
</div>

<!-- TABLA DE TRIMESTRES DETALLADA -->
<div class="card-panel">
    <div class="tabla-toolbar d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark m-0"><i class="bi bi-list-columns-reverse"></i> Detalle de Trimestres</h5>
        <span class="tabla-total m-0"><?= count($trimestres) ?> TRIMESTRES REGISTRADOS</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaTrimestres">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Gestión</th>
                    <th>Trimestre</th>
                    <th>Nombre</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Duración</th>
                    <th>Estado</th>
                    <th>Observación</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trimestres)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i> No se encontraron trimestres para esta gestión.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trimestres as $i => $t): 
                        $idT = (int)$t['idTrimestre'];
                        $nNum = (int)$t['numero'];
                        $badgeClass = "badge-trimestre-{$nNum}";

                        $diasDur = 0;
                        if (!empty($t['fechaInicio']) && !empty($t['fechaFin'])) {
                            $dt1 = new DateTime($t['fechaInicio']);
                            $dt2 = new DateTime($t['fechaFin']);
                            $diasDur = $dt1->diff($dt2)->days + 1;
                        }

                        $estClass = match($t['estadoTrimestre']) {
                            'En Curso' => 'estado-en-curso',
                            'Cerrado'  => 'estado-cerrado',
                            default    => 'estado-pendiente'
                        };
                    ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><span class="badge bg-light text-dark border"><i class="bi bi-calendar-check"></i> <?= $e($t['gestionAnio']) ?></span></td>
                            <td>
                                <span class="badge-trimestre <?= $badgeClass ?>">
                                    Trimestre <?= $nNum ?>
                                </span>
                            </td>
                            <td class="fw-semibold text-dark"><?= $e($t['nombre']) ?></td>
                            <td>
                                <?php if (!empty($t['fechaInicio'])): ?>
                                    <span class="text-success font-monospace"><i class="bi bi-calendar-event"></i> <?= date('d/m/Y', strtotime($t['fechaInicio'])) ?></span>
                                <?php else: ?>
                                    <em class="text-muted">No asignada</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($t['fechaFin'])): ?>
                                    <span class="text-danger font-monospace"><i class="bi bi-calendar-event"></i> <?= date('d/m/Y', strtotime($t['fechaFin'])) ?></span>
                                <?php else: ?>
                                    <em class="text-muted">No asignada</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($diasDur > 0): ?>
                                    <span class="small fw-semibold text-secondary"><?= $diasDur ?> días</span>
                                <?php else: ?>
                                    <em class="text-muted">-</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="estado-badge <?= $estClass ?>">
                                    <?= $e($t['estadoTrimestre']) ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= !empty($t['observacion']) ? $e($t['observacion']) : '<em class="text-light-gray">Sin observación</em>' ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="acciones-tabla">
                                    <button class="btn-accion btn-editar" title="Editar Trimester" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idT ?>">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn-accion btn-eliminar" title="Eliminar Trimestre" data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $idT ?>">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODALES DE EDICIÓN Y ELIMINACIÓN -->
<?php foreach ($trimestres as $t): 
    $idT = (int)$t['idTrimestre'];
?>
    <!-- Modal Editar Trimestre -->
    <div class="modal fade" id="modalEditar<?= $idT ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" autocomplete="off" class="form-trimestre-validador">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Configurar Trimestre <?= (int)$t['numero'] ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idTrimestre" value="<?= $idT ?>">
                        <input type="hidden" name="idGestion" value="<?= (int)$t['idGestion'] ?>">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">N° Trimestre</label>
                                <select class="form-select" name="numero" required>
                                    <option value="1" <?= (int)$t['numero'] === 1 ? 'selected' : '' ?>>1er Trimestre</option>
                                    <option value="2" <?= (int)$t['numero'] === 2 ? 'selected' : '' ?>>2do Trimestre</option>
                                    <option value="3" <?= (int)$t['numero'] === 3 ? 'selected' : '' ?>>3er Trimestre</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Nombre del Trimestre *</label>
                                <input type="text" class="form-control input-nombre-trimestre" name="nombre" value="<?= $e($t['nombre']) ?>" required maxlength="50" placeholder="Ej: Primer Trimestre">
                                <div class="invalid-feedback">El nombre es obligatorio (máx. 50 caracteres).</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-calendar-event text-primary"></i> Fecha de Inicio *</label>
                                <input type="date" class="form-control input-fecha-inicio" name="fechaInicio" value="<?= $e($t['fechaInicio']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-calendar-event text-danger"></i> Fecha de Fin *</label>
                                <input type="date" class="form-control input-fecha-fin" name="fechaFin" value="<?= $e($t['fechaFin']) ?>" required>
                                <div class="invalid-feedback">La fecha de fin debe ser posterior a la de inicio.</div>
                            </div>

                            <div class="col-12">
                                <div class="duracion-preview small text-center p-2 rounded bg-light border"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Estado del Trimestre</label>
                                <select class="form-select" name="estadoTrimestre">
                                    <option value="Pendiente" <?= $t['estadoTrimestre'] === 'Pendiente' ? 'selected' : '' ?>>Pendiente (No iniciado)</option>
                                    <option value="En Curso" <?= $t['estadoTrimestre'] === 'En Curso' ? 'selected' : '' ?>>En Curso (Actualmente activo)</option>
                                    <option value="Cerrado" <?= $t['estadoTrimestre'] === 'Cerrado' ? 'selected' : '' ?>>Cerrado (Concluido)</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Observaciones / Notas</label>
                                <textarea class="form-control" name="observacion" rows="3" maxlength="255" placeholder="Ej: Período de evaluaciones finales del 10 al 15 de mayo"><?= $e($t['observacion']) ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Trimestre -->
    <div class="modal fade" id="modalEliminar<?= $idT ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionEliminar ?>" method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Eliminar Trimestre</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idTrimestre" value="<?= $idT ?>">
                        <input type="hidden" name="idGestion" value="<?= (int)$t['idGestion'] ?>">
                        <p>¿Está seguro de eliminar el <b><?= $e($t['nombre']) ?></b> de la gestión <b><?= $e($t['gestionAnio']) ?></b>?</p>
                        <p class="small text-muted mb-0">Esta acción dará de baja el trimestre pero podrá volver a inicializarlo cuando lo necesite.</p>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash-fill"></i> Confirmar Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- MODAL NUEVO TRIMESTRE -->
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= $accionGuardar ?>" method="post" autocomplete="off" class="form-trimestre-validador">
                <div class="modal-header bg-jaegasa text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Agregar Trimestre</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idTrimestre" value="0">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Gestión Escolar *</label>
                            <select class="form-select" name="idGestion" required>
                                <?php foreach ($gestiones as $g): ?>
                                    <option value="<?= (int)$g['idGestion'] ?>" <?= (int)$g['idGestion'] === $idGestionSel ? 'selected' : '' ?>>
                                        Gestión <?= $e($g['anio']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">N° Trimestre *</label>
                            <select class="form-select" name="numero" required>
                                <option value="1">1er Trimestre</option>
                                <option value="2">2do Trimestre</option>
                                <option value="3">3er Trimestre</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Nombre del Trimestre *</label>
                            <input type="text" class="form-control input-nombre-trimestre" name="nombre" required maxlength="50" placeholder="Ej: Primer Trimestre">
                            <div class="invalid-feedback">El nombre es obligatorio.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold"><i class="bi bi-calendar-event text-primary"></i> Fecha de Inicio *</label>
                            <input type="date" class="form-control input-fecha-inicio" name="fechaInicio" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><i class="bi bi-calendar-event text-danger"></i> Fecha de Fin *</label>
                            <input type="date" class="form-control input-fecha-fin" name="fechaFin" required>
                            <div class="invalid-feedback">La fecha de fin debe ser posterior a la de inicio.</div>
                        </div>

                        <div class="col-12">
                            <div class="duracion-preview small text-center p-2 rounded bg-light border"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Estado Inicial</label>
                            <select class="form-select" name="estadoTrimestre">
                                <option value="Pendiente" selected>Pendiente (No iniciado)</option>
                                <option value="En Curso">En Curso (Actualmente activo)</option>
                                <option value="Cerrado">Cerrado (Concluido)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Observaciones / Notas</label>
                            <textarea class="form-control" name="observacion" rows="3" maxlength="255" placeholder="Ej: Observaciones iniciales del trimestre..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-jaegasa"><i class="bi bi-check-lg"></i> Registrar Trimestre</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Carga del JS específico de Trimestre -->
<script src="<?= BASE_URL ?>Js/trimestre.js?v=<?= @filemtime(BASE_PATH . '/Js/trimestre.js') ?>"></script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>
