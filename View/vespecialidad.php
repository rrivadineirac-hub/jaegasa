<?php
/**
 * vespecialidad.php — Vista del CRUD Especialidad (sin JS)
 * Recibe: $especialidades, $categorias, $flash, $buscar
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$accionGuardar  = BASE_URL . 'index.php?c=especialidad&a=guardar';
$accionEliminar = BASE_URL . 'index.php?c=especialidad&a=eliminar';
?>

<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-mortarboard"></i> Especialidades</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Especialidades de los docentes</p>
    </div>
    <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="bi bi-plus-lg"></i> Nueva especialidad
    </button>
</div>



<div class="card-panel">
    <div class="tabla-toolbar">
        <form method="get" action="<?= BASE_URL ?>index.php" class="buscador-form">
            <input type="hidden" name="c" value="especialidad">
            <div class="buscador">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="<?= $e($buscar ?? '') ?>" placeholder="Buscar especialidad o categoría..." autocomplete="off">
            </div>
            <button type="submit" class="btn btn-sm btn-jaegasa">Buscar</button>
            <a href="<?= BASE_URL ?>index.php?c=especialidad" id="btnLimpiarEspecialidad" class="btn btn-sm btn-outline-secondary" style="<?= empty($buscar) ? 'display: none;' : '' ?>">Limpiar</a>
        </form>
        <div class="d-flex flex-column align-items-end justify-content-start align-self-start mt-1">
            <div class="mb-1 text-end">
                <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                <div class="text-primary fw-semibold fs-6">
                    <i class="bi bi-calendar2-check"></i> <?= $e($gestionActual ?? date('Y')) ?>
                </div>
            </div>
            <span class="tabla-total m-0"><?= count($especialidades) ?> ESPECIALIDADES</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaAdmin">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Especialidad</th>
                    <th>Categoría</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($especialidades)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> No hay especialidades registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($especialidades as $i => $a): ?>
                        <?php $idE = (int) $a['idEspecialidad']; ?>
                        <tr data-nombre="<?= $e($a['nombre'] ?? '') ?>" data-categoria="<?= $e($a['categoria'] ?? '') ?>">
                            <td><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= $e($a['nombre']) ?></td>
                            <td>
                                <?php if (!empty($a['categoria'])): ?>
                                    <span class="badge-cat"><?= $e($a['categoria']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="acciones-tabla">
                                    <button class="btn-accion btn-editar" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idE ?>"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="btn-accion btn-eliminar" title="Eliminar" data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $idE ?>"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($especialidades as $a): $idE = (int) $a['idEspecialidad']; ?>
    <div class="modal fade" id="modalEditar<?= $idE ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar especialidad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idEspecialidad" value="<?= $idE ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="nombre" value="<?= $e($a['nombre']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Categoría</label>
                                <input type="text" class="form-control" name="categoria" value="<?= $e($a['categoria']) ?>" list="listaCategorias">
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
    <div class="modal fade" id="modalEliminar<?= $idE ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionEliminar ?>" method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Eliminar especialidad</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idEspecialidad" value="<?= $idE ?>">
                        <p>¿Está seguro de eliminar la especialidad <b><?= $e($a['nombre']) ?></b>?</p>
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
                    <h5 class="modal-title">Nueva especialidad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idEspecialidad" value="0">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" name="nombre" placeholder="Ej. Matemáticas, Lenguaje" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Categoría</label>
                            <input type="text" class="form-control" name="categoria" list="listaCategorias" placeholder="Opcional">
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

<datalist id="listaCategorias">
    <?php foreach ($categorias as $c): ?>
        <option value="<?= $e($c['nombre']) ?>"></option>
    <?php endforeach; ?>
</datalist>

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
                    if (text.includes('no hay especialidades')) return;
                }

                if (tokens.length === 0) {
                    fila.style.display = '';
                    visibles++;
                    return;
                }

                const nombre    = normalizarTexto(fila.dataset.nombre || '');
                const categoria = normalizarTexto(fila.dataset.categoria || '');
                const textoFull = normalizarTexto(fila.textContent || '');
                const palabras  = (nombre + ' ' + categoria + ' ' + textoFull).split(/\s+/).filter(Boolean);

                const coincide = tokens.every(token => {
                    let palabrasEvaluar = palabras;
                    if (token.length === 1) {
                        palabrasEvaluar = palabras.filter(p => !STOP_WORDS.has(p));
                    }
                    return nombre.startsWith(token) || categoria.startsWith(token) || palabrasEvaluar.some(p => p.startsWith(token));
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
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="bi bi-search fs-3 d-block mb-1"></i>
                            No se encontraron especialidades que coincidan con "<strong>${inputBuscar.value}</strong>".
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
                badgeTotal.textContent = `${visibles} ESPECIALIDADES`;
            }
        }

        const btnLimpiarEspecialidad = document.getElementById('btnLimpiarEspecialidad');

        function actualizarBotonLimpiarEspecialidad() {
            if (!inputBuscar || !btnLimpiarEspecialidad) return;
            if (inputBuscar.value.trim().length > 0) {
                btnLimpiarEspecialidad.style.display = 'inline-block';
            } else {
                btnLimpiarEspecialidad.style.display = 'none';
            }
        }

        inputBuscar.addEventListener('input', function () {
            actualizarBotonLimpiarEspecialidad();
            filtrarTabla();
        });

        if (btnLimpiarEspecialidad) {
            btnLimpiarEspecialidad.addEventListener('click', function (e) {
                if (inputBuscar.value.trim() !== '') {
                    inputBuscar.value = '';
                    actualizarBotonLimpiarEspecialidad();
                    filtrarTabla();
                    if (window.location.search.includes('q=')) {
                        e.preventDefault();
                        window.location.href = '<?= BASE_URL ?>index.php?c=especialidad';
                    } else {
                        e.preventDefault();
                    }
                }
            });
        }

        actualizarBotonLimpiarEspecialidad();
        if (inputBuscar.value.trim() !== '') {
            filtrarTabla();
        }
    }
});
</script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>
