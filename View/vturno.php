<?php
/**
 * vturno.php — Vista del CRUD Turno (sin JS)
 * Recibe: $turnos, $flash, $buscar
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$accionGuardar  = BASE_URL . 'index.php?c=turno&a=guardar';
$accionEliminar = BASE_URL . 'index.php?c=turno&a=eliminar';
?>

<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-clock-fill"></i> Turnos</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Turnos de clases (Mañana, Tarde, Noche)</p>
    </div>
    <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="bi bi-plus-lg"></i> Nuevo turno
    </button>
</div>



<div class="card-panel">
    <div class="tabla-toolbar">
        <form method="get" action="<?= BASE_URL ?>index.php" class="buscador-form">
            <input type="hidden" name="c" value="turno">
            <div class="buscador">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="<?= $e($buscar ?? '') ?>" placeholder="Buscar turno..." autocomplete="off">
            </div>
            <button type="submit" class="btn btn-sm btn-jaegasa">Buscar</button>
            <a href="<?= BASE_URL ?>index.php?c=turno" id="btnLimpiarTurno" class="btn btn-sm btn-outline-secondary" style="<?= empty($buscar) ? 'display: none;' : '' ?>">Limpiar</a>
        </form>
        <div class="d-flex flex-column align-items-end justify-content-start align-self-start mt-1">
            <div class="mb-1 text-end">
                <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                <div class="text-primary fw-semibold fs-6">
                    <i class="bi bi-calendar2-check"></i> <?= $e($gestionActual ?? date('Y')) ?>
                </div>
            </div>
            <span class="tabla-total m-0"><?= count($turnos) ?> TURNOS</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaAdmin">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($turnos)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> No hay turnos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($turnos as $i => $a): ?>
                        <?php $idT = (int) $a['idTurno']; ?>
                        <tr data-nombre="<?= $e($a['nombre'] ?? '') ?>">
                            <td><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= $e($a['nombre']) ?></td>
                            <td class="text-end text-nowrap">
                                <div class="acciones-tabla">
                                    <button class="btn-accion btn-editar" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idT ?>"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="btn-accion btn-eliminar" title="Eliminar" data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $idT ?>"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($turnos as $a): $idT = (int) $a['idTurno']; ?>
    <div class="modal fade" id="modalEditar<?= $idT ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar turno</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idTurno" value="<?= $idT ?>">
                        <label class="form-label">Nombre *</label>
                        <input type="text" class="form-control" name="nombre" value="<?= $e($a['nombre']) ?>" required>
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
    <div class="modal fade" id="modalEliminar<?= $idT ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionEliminar ?>" method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Eliminar turno</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idTurno" value="<?= $idT ?>">
                        <p>¿Está seguro de eliminar el turno <b><?= $e($a['nombre']) ?></b>?</p>
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

<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= $accionGuardar ?>" method="post" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo turno</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idTurno" value="0">
                    <label class="form-label">Nombre *</label>
                    <input type="text" class="form-control" name="nombre" placeholder="Ej. Mañana, Tarde, Noche" required>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputBuscar = document.querySelector('input[name="q"]');
    const tablaAdmin  = document.getElementById('tablaAdmin');
    const badgeTotal  = document.querySelector('.tabla-total');

    if (inputBuscar && tablaAdmin) {
        const tbody = tablaAdmin.querySelector('tbody');

        function normalizarTexto(str) {
            if (!str) return '';
            return str
                .replace(/Ñ/g, '__ENNE__')
                .replace(/ñ/g, '__ENNE__')
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/__ENNE__/g, 'ñ')
                .toLowerCase()
                .trim();
        }

        const STOP_WORDS = new Set(['en', 'de', 'del', 'la', 'las', 'los', 'el', 'un', 'una', 'y', 'e', 'o']);

        function filtrarTabla() {
            if (!tbody) return;
            const query   = normalizarTexto(inputBuscar.value);
            const tokens  = query.split(/\s+/).filter(t => t.length > 0);
            const filas   = tbody.querySelectorAll('tr:not(.fila-sin-resultados)');
            let visibles  = 0;

            filas.forEach(fila => {
                if (fila.querySelector('td[colspan]')) {
                    const text = fila.textContent.toLowerCase();
                    if (text.includes('no hay turnos')) return;
                }

                if (tokens.length === 0) {
                    fila.style.display = '';
                    visibles++;
                    return;
                }

                const nombre   = normalizarTexto(fila.dataset.nombre || fila.textContent || '');
                const palabras = nombre.split(/\s+/).filter(Boolean);

                const coincide = tokens.every(token => {
                    let palabrasEvaluar = palabras;
                    if (token.length === 1) {
                        palabrasEvaluar = palabras.filter(p => !STOP_WORDS.has(p));
                    }
                    return nombre.startsWith(token) || palabrasEvaluar.some(p => p.startsWith(token));
                });

                if (coincide) {
                    fila.style.display = '';
                    visibles++;
                } else {
                    fila.style.display = 'none';
                }
            });

            let filaMensaje = tbody.querySelector('.fila-sin-resultados');
            if (visibles === 0 && tokens.length > 0) {
                if (!filaMensaje) {
                    filaMensaje = document.createElement('tr');
                    filaMensaje.className = 'fila-sin-resultados';
                    filaMensaje.innerHTML = `
                        <td colspan="3" class="text-center text-muted py-4">
                            <i class="bi bi-search fs-3 d-block mb-1"></i>
                            No se encontraron turnos que coincidan con "<strong>${inputBuscar.value}</strong>".
                        </td>
                    `;
                    tbody.appendChild(filaMensaje);
                } else {
                    const strong = filaMensaje.querySelector('strong');
                    if (strong) strong.textContent = inputBuscar.value;
                    filaMensaje.style.display = '';
                }
            } else if (filaMensaje) {
                filaMensaje.style.display = 'none';
            }

            if (badgeTotal) {
                badgeTotal.textContent = `${visibles} TURNOS`;
            }
        }

        const btnLimpiarTurno = document.getElementById('btnLimpiarTurno');

        function actualizarBotonLimpiarTurno() {
            if (!inputBuscar || !btnLimpiarTurno) return;
            if (inputBuscar.value.trim().length > 0) {
                btnLimpiarTurno.style.display = 'inline-block';
            } else {
                btnLimpiarTurno.style.display = 'none';
            }
        }

        inputBuscar.addEventListener('input', function () {
            actualizarBotonLimpiarTurno();
            filtrarTabla();
        });

        if (btnLimpiarTurno) {
            btnLimpiarTurno.addEventListener('click', function (e) {
                if (inputBuscar.value.trim() !== '') {
                    inputBuscar.value = '';
                    actualizarBotonLimpiarTurno();
                    filtrarTabla();
                    if (window.location.search.includes('q=')) {
                        e.preventDefault();
                        window.location.href = '<?= BASE_URL ?>index.php?c=turno';
                    } else {
                        e.preventDefault();
                    }
                }
            });
        }

        actualizarBotonLimpiarTurno();
        if (inputBuscar.value.trim() !== '') {
            filtrarTabla();
        }
    }
});
</script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>
