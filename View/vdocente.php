<?php
/**
 * ============================================================
 * vdocente.php  —  Vista del CRUD Docente (sin JS)
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Recibe del controlador:
 *   $docentes    -> lista de docentes
 *   $categorias  -> categorías para el formulario
 *   $flash       -> mensaje ['tipo','mensaje'] (opcional)
 *   $buscar      -> término de búsqueda actual
 * ============================================================
 */

require_once BASE_PATH . '/View/header.php';
require_once BASE_PATH . '/partes/nacionalidades.php';
require_once BASE_PATH . '/partes/bolivia.php';

// Helpers locales
$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$genero = fn($g) => $g === 'M' ? 'Masculino' : ($g === 'F' ? 'Femenino' : '—');
$fotoDefault = BASE_URL . 'inagenes/Admi/default.png';   // imagen por defecto existente
$rutaFoto = fn($foto) => (!empty($foto) && $foto !== 'default.png')
    ? BASE_URL . 'inagenes/Docente/' . rawurlencode($foto)
    : $fotoDefault;

$accionGuardar  = BASE_URL . 'index.php?c=docente&a=guardar';
$accionEliminar = BASE_URL . 'index.php?c=docente&a=eliminar';
?>

<?php
$logoPathPng = BASE_PATH . '/inagenes/logos/Logo.png';
$logoPathJpg = BASE_PATH . '/inagenes/logos/Logo.jpeg';
$logoSrcBase64 = '';
if (file_exists($logoPathPng)) {
    $logoSrcBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPathPng));
} elseif (file_exists($logoPathJpg)) {
    $logoSrcBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPathJpg));
}
?>
<script>
    window.schoolLogoBase64 = <?= json_encode($logoSrcBase64) ?>;
</script>

<!-- Encabezado -->
<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-person-badge"></i> Docentes</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Registro y control de profesores</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-success shadow-sm px-3" onclick="imprimirListadoDocentes()" title="Imprimir listado">
            <i class="bi bi-printer-fill me-1"></i> Imprimir
        </button>
        <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
            <i class="bi bi-plus-lg"></i> Nuevo docente
        </button>
    </div>
</div>



<!-- Tabla -->
<div class="card-panel">

    <div class="tabla-toolbar">
        <form method="get" action="<?= BASE_URL ?>index.php" class="buscador-form">
            <input type="hidden" name="c" value="docente">
            <div class="buscador">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="<?= $e($buscar ?? '') ?>"
                       placeholder="Buscar por nombre o CI..." autocomplete="off">
            </div>
            <button type="submit" class="btn btn-sm btn-jaegasa">Buscar</button>
            <a href="<?= BASE_URL ?>index.php?c=docente" id="btnLimpiarDocente" class="btn btn-sm btn-outline-secondary" style="<?= empty($buscar) ? 'display: none;' : '' ?>">Limpiar</a>
            <button type="button" id="btnImprimirDocente" class="btn btn-sm btn-success shadow-sm px-3" onclick="imprimirListadoDocentes()" title="Imprimir listado" style="<?= empty($buscar) ? 'display: none;' : '' ?>">
                <i class="bi bi-printer-fill me-1"></i> Imprimir
            </button>
        </form>
        <div class="d-flex flex-column align-items-end justify-content-start align-self-start mt-1">
            <div class="mb-1 text-end">
                <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                <div class="text-primary fw-semibold fs-6">
                    <i class="bi bi-calendar2-check"></i> <?= $e($gestionActual ?? date('Y')) ?>
                </div>
            </div>
            <span class="tabla-total m-0"><?= count($docentes) ?> DOCENTE(S) REGISTRADO(S)</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaAdmin">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Foto</th>
                    <th>Nombre completo</th>
                    <th>CI</th>
                    <th>Categoría</th>
                    <th>Ítem</th>
                    <th>Teléfono</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($docentes)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox"></i> No hay docentes registrados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($docentes as $i => $a): ?>
                        <?php
                            $idDoc = (int) $a['idDocente'];
                            $nombreCompleto = trim($a['apPaterno'] . ' ' . $a['apMaterno'] . ' ' . $a['nombre']);
                        ?>
                        <tr data-paterno="<?= $e($a['apPaterno']) ?>"
                            data-materno="<?= $e($a['apMaterno']) ?>"
                            data-nombre="<?= $e($a['nombre']) ?>"
                            data-completo="<?= $e($nombreCompleto) ?>"
                            data-ci="<?= $e($a['ci']) ?>"
                            data-categoria="<?= $e($a['categoria'] ?: '—') ?>"
                            data-item="<?= $e($a['numeroItem'] ?: '—') ?>">
                            <td><?= $i + 1 ?></td>
                            <td>
                                <img src="<?= $rutaFoto($a['foto']) ?>" alt="Foto"
                                     class="tabla-foto"
                                     onerror="this.src='<?= $fotoDefault ?>'">
                            </td>
                            <td class="fw-semibold"><?= $e($nombreCompleto) ?></td>
                            <td><?= $e($a['ci']) ?></td>
                            <td>
                                <?php if (!empty($a['categoria'])): ?>
                                    <span class="badge-cat"><?= $e($a['categoria']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $e($a['numeroItem'] ?: '—') ?></td>
                            <td><?= $e($a['telefono'] ?: '—') ?></td>
                            <td class="text-end text-nowrap">
                                <div class="acciones-tabla">
                                    <button class="btn-accion btn-ver" title="Mostrar completo"
                                            data-bs-toggle="modal" data-bs-target="#modalVer<?= $idDoc ?>">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                    <button class="btn-accion btn-editar" title="Editar"
                                            data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idDoc ?>">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn-accion btn-eliminar" title="Eliminar"
                                            data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $idDoc ?>">
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

<?php
// =========================================================
// MODALES POR FILA (Ver, Editar, Eliminar) — sin JS
// =========================================================
foreach ($docentes as $a):
    $idDoc = (int) $a['idDocente'];
    $nombreCompleto = trim($a['apPaterno'] . ' ' . $a['apMaterno'] . ' ' . $a['nombre']);
?>

    <!-- ===== Modal VER <?= $idDoc ?> ===== -->
    <div class="modal fade" id="modalVer<?= $idDoc ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-vcard"></i> Detalle del docente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="detalle-cabecera">
                        <img src="<?= $rutaFoto($a['foto']) ?>" alt="Foto"
                             onerror="this.src='<?= $fotoDefault ?>'">
                        <div>
                            <h3><?= $e($nombreCompleto) ?></h3>
                            <span class="badge-cat"><?= $e($a['categoria'] ?: 'Sin categoría') ?></span>
                            <p class="detalle-usuario"><i class="bi bi-person-circle"></i> <?= $e($a['usuario'] ?: '—') ?></p>
                        </div>
                    </div>

                    <h6 class="form-seccion">Datos personales</h6>
                    <div class="detalle-grid">
                        <div class="detalle-item"><span>CI</span><b><?= $e($a['ci']) ?></b></div>
                        <div class="detalle-item"><span>Género</span><b><?= $e($genero($a['genero'])) ?></b></div>
                        <div class="detalle-item"><span>Fecha de nacimiento</span><b><?= $e($a['fechaNacimiento'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Nacionalidad</span><b><?= $e($a['nacionalidad'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Correo</span><b><?= $e($a['correo'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Teléfono</span><b><?= $e($a['telefono'] ?: '—') ?></b></div>
                        <div class="detalle-item detalle-ancho"><span>Dirección</span><b><?= $e($a['direccion'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Nacionalidad</span><b><?= $e($a['nacionalidad'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Departamento</span><b><?= $e($a['departamento'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Provincia</span><b><?= $e($a['provincia'] ?: '—') ?></b></div>
                    </div>

                    <h6 class="form-seccion mt-4">Datos laborales</h6>
                    <div class="detalle-grid">
                        <div class="detalle-item"><span>Categoría</span><b><?= $e($a['categoria'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>N.º de ítem</span><b><?= $e($a['numeroItem'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>RDA</span><b><?= $e($a['rda'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Fecha de registro</span><b><?= $e($a['fechaRegistro'] ?? '—') ?></b></div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-jaegasa" data-bs-dismiss="modal"
                            data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idDoc ?>">
                        <i class="bi bi-pencil-fill"></i> Editar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Modal EDITAR <?= $idDoc ?> ===== -->
    <div class="modal fade" id="modalEditar<?= $idDoc ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" enctype="multipart/form-data" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar docente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idDocente" value="<?= $idDoc ?>">

                        <div class="foto-uploader">
                            <img id="previewFoto<?= $idDoc ?>" src="<?= $rutaFoto($a['foto']) ?>" alt="Vista previa"
                                onerror="this.src='<?= $fotoDefault ?>'">
                            <div class="foto-uploader-info">
                                <label class="form-label mb-1">Foto</label>
                                <input type="file" class="form-control form-control-sm" name="foto"
                                    accept="image/png, image/jpeg, image/webp"
                                    onchange="previsualizarFoto(this, 'previewFoto<?= $idDoc ?>')">
                                <small class="text-muted">JPG, PNG o WEBP. Máx. 2 MB. Opcional.</small>
                            </div>
                        </div>

                        <h6 class="form-seccion">Datos personales</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">CI *</label>
                                <input type="text" class="form-control input-persona-ci" name="ci" value="<?= $e($a['ci']) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Apellido paterno *</label>
                                <input type="text" class="form-control" name="apPaterno" value="<?= $e($a['apPaterno']) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Apellido materno *</label>
                                <input type="text" class="form-control" name="apMaterno" value="<?= $e($a['apMaterno']) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nombre(s) *</label>
                                <input type="text" class="form-control" name="nombre" value="<?= $e($a['nombre']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Género</label>
                                <select class="form-select" name="genero">
                                    <option value="">Seleccione...</option>
                                    <option value="M" <?= $a['genero'] === 'M' ? 'selected' : '' ?>>Masculino</option>
                                    <option value="F" <?= $a['genero'] === 'F' ? 'selected' : '' ?>>Femenino</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha de nacimiento</label>
                                <input type="date" class="form-control" name="fechaNac" value="<?= $e($a['fechaNacimiento']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Correo</label>
                                <input type="email" class="form-control" name="correo" value="<?= $e($a['correo']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" value="<?= $e($a['telefono']) ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="direccion" value="<?= $e($a['direccion']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nacionalidad</label>
                                <select class="form-select" name="nacionalidad">
                                    <?= opcionesNacionalidades($a['nacionalidad'] ?? 'Boliviana') ?>
                                </select>
                            </div>
                            <div class="col-md-4 ubicacion-bolivia">
                                <label class="form-label">Departamento</label>
                                <select class="form-select" name="departamento">
                                    <?= opcionesDepartamentos($a['departamento'] ?? '') ?>
                                </select>
                            </div>
                            <div class="col-md-4 ubicacion-bolivia">
                                <label class="form-label">Provincia</label>
                                <select class="form-select" name="provincia" data-actual="<?= $e($a['provincia'] ?? '') ?>">
                                    <option value="">Seleccione...</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="form-seccion mt-4">Datos laborales</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Categoría</label>
                                <select class="form-select" name="categoria">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($categorias as $c): ?>
                                        <option value="<?= $e($c['nombre']) ?>" <?= ($a['categoria'] === $c['nombre']) ? 'selected' : '' ?>>
                                            <?= $e($c['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">N.º de ítem</label>
                                <input type="text" class="form-control" name="numeroItem" value="<?= $e($a['numeroItem']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">RDA</label>
                                <input type="text" class="form-control" name="rda" value="<?= $e($a['rda']) ?>">
                            </div>
                        </div>

                        <h6 class="form-seccion mt-4">Acceso al sistema</h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Usuario de acceso</label>
                                <input type="text" class="form-control bg-light" name="usuario" value="<?= $e($a['usuario']) ?>" readonly>
                                <small class="text-muted">Nombre de usuario (no modificable).</small>
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

    <!-- ===== Modal ELIMINAR <?= $idDoc ?> ===== -->
    <div class="modal fade" id="modalEliminar<?= $idDoc ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionEliminar ?>" method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Eliminar docente</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body text-center">
                        <input type="hidden" name="idDocente" value="<?= $idDoc ?>">
                        <img src="<?= $rutaFoto($a['foto']) ?>" alt="Foto" class="tabla-foto mb-3"
                             onerror="this.src='<?= $fotoDefault ?>'">
                        <p>¿Está seguro de eliminar a <b><?= $e($nombreCompleto) ?></b>?</p>
                        <p class="text-muted mb-0"><small>Esta acción lo desactiva del sistema (baja lógica).</small></p>
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

<!-- ===================== Modal NUEVO ===================== -->
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form action="<?= $accionGuardar ?>" method="post" enctype="multipart/form-data" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo docente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idDocente" value="0">

                    <div class="foto-uploader">
                        <div class="foto-uploader">
                            <img id="previewFotoNuevo" src="<?= $fotoDefault ?>" alt="Vista previa" onerror="this.src='<?= $fotoDefault ?>'">
                            <div class="foto-uploader-info">
                                <label class="form-label mb-1">Foto</label>
                                <input type="file" class="form-control form-control-sm" name="foto"
                                    accept="image/png, image/jpeg, image/webp"
                                    onchange="previsualizarFoto(this, 'previewFotoNuevo')">
                                <small class="text-muted">JPG, PNG o WEBP. Máx. 2 MB. Opcional.</small>
                            </div>
                        </div>
                    </div>

                    <h6 class="form-seccion">Datos personales</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">CI *</label>
                            <input type="text" class="form-control input-persona-ci" name="ci" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Apellido paterno *</label>
                            <input type="text" class="form-control" name="apPaterno" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Apellido materno *</label>
                            <input type="text" class="form-control" name="apMaterno" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nombre(s) *</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Género</label>
                            <select class="form-select" name="genero">
                                <option value="">Seleccione...</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de nacimiento</label>
                            <input type="date" class="form-control" name="fechaNac">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo *</label>
                            <input type="email" class="form-control" name="correo" id="correoNuevoDocente" required placeholder="ejemplo@correo.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Dirección</label>
                            <input type="text" class="form-control" name="direccion">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nacionalidad</label>
                            <select class="form-select" name="nacionalidad">
                                <?= opcionesNacionalidades('Boliviana') ?>
                            </select>
                        </div>
                        <div class="col-md-4 ubicacion-bolivia">
                            <label class="form-label">Departamento</label>
                            <select class="form-select" name="departamento">
                                <?= opcionesDepartamentos() ?>
                            </select>
                        </div>
                        <div class="col-md-4 ubicacion-bolivia">
                            <label class="form-label">Provincia</label>
                            <select class="form-select" name="provincia" data-actual="">
                                <option value="">Seleccione...</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="form-seccion mt-4">Datos laborales</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="categoria">
                                <option value="">Seleccione...</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= $e($c['nombre']) ?>"><?= $e($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">N.º de ítem</label>
                            <input type="text" class="form-control" name="numeroItem">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">RDA</label>
                            <input type="text" class="form-control" name="rda">
                        </div>
                    </div>

                    <h6 class="form-seccion mt-4">Acceso al sistema</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Usuario *</label>
                            <input type="text" class="form-control bg-light" name="usuario" id="usuarioNuevoDocente" readonly placeholder="Se autocompleta con el correo">
                            <small class="text-muted">Se asignará automáticamente con el correo.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contraseña de acceso</label>
                            <input type="text" class="form-control bg-light text-success fw-bold" value="⚡ Generada automáticamente" readonly>
                            <small class="text-muted"><i class="bi bi-shield-lock-fill text-success"></i> Contraseña segura enviada por WhatsApp al guardar.</small>
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

<!-- Lista de categorías compartida por los formularios -->
<datalist id="listaCategorias">
    <?php foreach ($categorias as $c): ?>
        <option value="<?= $e($c['nombre']) ?>"></option>
    <?php endforeach; ?>
</datalist>

<!-- ===================== Modal IMPRIMIR DOCENTES ===================== -->
<div class="modal fade" id="modalImprimirDocentes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-printer-fill"></i> Listado de docentes para impresión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="areaImpresionDocentes" class="p-3">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-jaegasa mb-1">UNIDAD EDUCATIVA JAIME ESCALANTE GUTIÉRREZ</h4>
                        <h5 class="fw-bold text-secondary mb-1">LISTADO OFICIAL DE DOCENTES REGISTRADOS</h5>
                        <p class="text-muted small mb-0">
                            Gestión Escolar: <strong><?= $e($gestionActual ?? date('Y')) ?></strong> &nbsp;|&nbsp;
                            Total: <strong><?= count($docentes) ?> docente(s)</strong>
                        </p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr class="table-light">
                                    <th style="width: 5%" class="text-center">#</th>
                                    <th style="width: 35%">Nombre completo</th>
                                    <th style="width: 15%">CI</th>
                                    <th style="width: 15%">Usuario</th>
                                    <th style="width: 15%">N.º de ítem</th>
                                    <th style="width: 15%">Teléfono</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($docentes)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No hay docentes registrados.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($docentes as $i => $d): ?>
                                        <?php $nombreComp = trim($d['apPaterno'] . ' ' . $d['apMaterno'] . ' ' . $d['nombre']); ?>
                                        <tr>
                                            <td class="text-center"><?= $i + 1 ?></td>
                                            <td class="fw-bold"><?= $e($nombreComp) ?></td>
                                            <td><?= $e($d['ci']) ?></td>
                                            <td><?= $e($d['usuario'] ?: '—') ?></td>
                                            <td><?= $e($d['numeroItem'] ?: '—') ?></td>
                                            <td><?= $e($d['telefono'] ?: '—') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <a href="<?= BASE_URL ?>index.php?c=docente&a=exportar_pdf" target="_blank" class="btn btn-jaegasa">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Descargar / Imprimir PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previsualizarFoto(input, idImagen) {
    const img = document.getElementById(idImagen);
    if (!img) return;

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            img.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function imprimirListadoDocentes() {
    const contenido = document.getElementById('areaImpresionDocentes').innerHTML;
    const ventana = window.open('', '_blank', 'width=950,height=750');
    ventana.document.write(`
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Listado de Docentes - U.E. Jaime Escalante</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; padding: 25px; color: #1f2937; background: #fff; }
                .text-jaegasa { color: #1D8AB5 !important; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                th { background-color: #f3f4f6 !important; color: #1f2937; font-weight: 700; border: 1px solid #d1d5db !important; padding: 8px 10px; }
                td { border: 1px solid #e5e7eb !important; padding: 8px 10px; font-size: 0.9rem; }
                @media print {
                    @page { size: portrait; margin: 12mm; }
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            \${contenido}
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 600);
                };
            <\/script>
        </body>
        </html>
    `);
    ventana.document.close();
}
</script>

<script src="<?= BASE_URL ?>Js/persona_autocompletar.js?v=<?= @filemtime(BASE_PATH . '/Js/persona_autocompletar.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const correoInp = document.getElementById('correoNuevoDocente');
    const userInp   = document.getElementById('usuarioNuevoDocente');
    if (correoInp && userInp) {
        correoInp.addEventListener('input', () => {
            userInp.value = correoInp.value.trim();
        });
    }

    // ------------------------------------------------------------
    // Búsqueda en tiempo real (filtrado instantáneo por nombre/apellidos/CI)
    // ------------------------------------------------------------
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

        function filtrarTabla() {
            if (!tbody) return;
            const query   = normalizarTexto(inputBuscar.value);
            const tokens  = query.split(/\s+/).filter(t => t.length > 0);
            const filas   = tbody.querySelectorAll('tr:not(.fila-sin-resultados)');
            let visibles  = 0;

            filas.forEach(fila => {
                if (fila.querySelector('td[colspan]')) {
                    const text = fila.textContent.toLowerCase();
                    if (text.includes('no hay docentes')) return;
                }

                if (tokens.length === 0) {
                    fila.style.display = '';
                    visibles++;
                    return;
                }

                const paterno     = normalizarTexto(fila.dataset.paterno || '');
                const materno     = normalizarTexto(fila.dataset.materno || '');
                const nombre      = normalizarTexto(fila.dataset.nombre || '');
                const ci          = normalizarTexto(fila.dataset.ci || '');

                const fullPaterno = [paterno, materno, nombre].filter(Boolean).join(' ');
                const fullNombre  = [nombre, paterno, materno].filter(Boolean).join(' ');
                const palabras    = (fullPaterno + ' ' + ci).split(/\s+/).filter(Boolean);

                const coincide = tokens.every(token => {
                    return paterno.startsWith(token) ||
                           materno.startsWith(token) ||
                           nombre.startsWith(token)  ||
                           ci.startsWith(token)      ||
                           fullPaterno.startsWith(token) ||
                           fullNombre.startsWith(token)  ||
                           palabras.some(p => p.startsWith(token));
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
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-search fs-3 d-block mb-1"></i>
                            No se encontraron docentes que coincidan con "<strong>${inputBuscar.value}</strong>".
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
                badgeTotal.textContent = `${visibles} DOCENTE(S) REGISTRADO(S)`;
            }
        }

        const btnLimpiarDocente = document.getElementById('btnLimpiarDocente');
        const btnImprimirDocente = document.getElementById('btnImprimirDocente');

        function actualizarBotonesDocente() {
            if (!inputBuscar) return;
            const tieneTexto = (inputBuscar.value.trim().length > 0);
            if (btnLimpiarDocente) btnLimpiarDocente.style.display = tieneTexto ? 'inline-block' : 'none';
            if (btnImprimirDocente) btnImprimirDocente.style.display = tieneTexto ? 'inline-block' : 'none';
        }

        inputBuscar.addEventListener('input', function () {
            actualizarBotonesDocente();
            filtrarTabla();
        });

        if (btnLimpiarDocente) {
            btnLimpiarDocente.addEventListener('click', function (e) {
                if (inputBuscar.value.trim() !== '') {
                    inputBuscar.value = '';
                    actualizarBotonesDocente();
                    filtrarTabla();
                    if (window.location.search.includes('q=')) {
                        e.preventDefault();
                        window.location.href = '<?= BASE_URL ?>index.php?c=docente';
                    } else {
                        e.preventDefault();
                    }
                }
            });
        }

        actualizarBotonesDocente();
        if (inputBuscar.value.trim() !== '') {
            filtrarTabla();
        }
    }
});

function imprimirListadoDocentes() {
    const logoSrc = window.schoolLogoBase64 || '';
    
    let filtroTexto = 'LISTADO GENERAL DE DOCENTES';
    const inputQ = document.querySelector('input[name="q"]');
    if (inputQ && inputQ.value.trim() !== '') {
        filtroTexto = 'FILTRO DE BÚSQUEDA: "' + inputQ.value.trim().toUpperCase() + '"';
    }

    const fechaHoy = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    const horaHoy = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

    const tbody = document.querySelector('#tablaAdmin tbody');
    let filasAImprimir = [];
    if (tbody) {
        const trs = tbody.querySelectorAll('tr');
        let contador = 1;
        trs.forEach(tr => {
            if (tr.style.display !== 'none' && !tr.classList.contains('fila-sin-resultados') && !tr.querySelector('td[colspan]')) {
                const completo  = tr.dataset.completo || (tr.querySelectorAll('td')[2] ? tr.querySelectorAll('td')[2].textContent.trim() : '');
                const ci        = tr.dataset.ci || (tr.querySelectorAll('td')[3] ? tr.querySelectorAll('td')[3].textContent.trim() : '—');
                const categoria = tr.dataset.categoria || (tr.querySelectorAll('td')[4] ? tr.querySelectorAll('td')[4].textContent.trim() : '—');
                const item      = tr.dataset.item || (tr.querySelectorAll('td')[5] ? tr.querySelectorAll('td')[5].textContent.trim() : '—');
                
                filasAImprimir.push({
                    num: contador++,
                    completo: completo || '—',
                    ci: ci || '—',
                    categoria: categoria || '—',
                    item: item || '—'
                });
            }
        });
    }

    if (filasAImprimir.length === 0) {
        alert('No hay docentes visibles en la lista para imprimir.');
        return;
    }

    let rowsHtml = '';
    filasAImprimir.forEach(doc => {
        rowsHtml += `
            <tr>
                <td style="text-align: center; font-weight: bold;">${doc.num}</td>
                <td style="font-weight: 600; text-align: left;">${doc.completo}</td>
                <td style="text-align: center;">${doc.ci}</td>
                <td style="text-align: center;">${doc.categoria}</td>
                <td style="text-align: center; font-weight: 700;">${doc.item}</td>
            </tr>
        `;
    });

    const htmlContent = `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Listado Oficial de Docentes - U.E. Prof. Jaime Alfonso Escalante Gutiérrez "A"</title>
            <style>
                @page { margin: 8mm 8mm; size: portrait; }
                body { font-family: 'Segoe UI', Arial, sans-serif; padding: 10px; color: #1e293b; background: #fff; font-size: 10px; }
                
                .header-container { display: flex; align-items: center; justify-content: space-between; border-bottom: 2.5px solid #475569; padding-bottom: 6px; margin-bottom: 8px; }
                .logo-img { height: 55px; width: auto; object-fit: contain; }
                .header-title { text-align: center; flex-grow: 1; padding: 0 10px; }
                .header-title h5 { font-size: 8.5px; margin: 0; font-weight: 900; color: #334155; letter-spacing: 0.5px; }
                .header-title h3 { font-size: 14px; margin: 2px 0; font-weight: 900; color: #000000; text-transform: uppercase; letter-spacing: 0.5px; }
                .header-title p { font-size: 10px; margin: 2px 0 0 0; font-weight: 900; color: #ffffff; background: #334155; padding: 4px 10px; border-radius: 3px; display: inline-block; border: 1.5px solid #1e293b; text-transform: uppercase; }
                .header-info { text-align: right; font-size: 8.5px; color: #1e293b; line-height: 1.3; min-width: 110px; font-weight: 700; }

                .tabla-listado { width: 100%; border-collapse: collapse; margin-top: 6px; page-break-inside: auto; }
                .tabla-listado tr { page-break-inside: avoid; page-break-after: auto; }
                .tabla-listado th, .tabla-listado td { border: 1px solid #475569; padding: 4.5px 8px; font-size: 9.5px; }
                .tabla-listado th { background-color: #334155; color: #ffffff; font-weight: 900; text-transform: uppercase; font-size: 10px; text-align: center; letter-spacing: 0.5px; border: 1.5px solid #1e293b; }
                .tabla-listado tr:nth-child(even) { background-color: #f1f5f9; }

                .resumen-footer { margin-top: 8px; display: flex; justify-content: space-between; font-weight: 700; font-size: 9px; color: #334155; }

                @media print {
                    body { padding: 0; }
                    html, body { height: 99%; }
                }
            </style>
        </head>
        <body>
            <div class="header-container">
                <div>
                    ${logoSrc ? `<img src="${logoSrc}" class="logo-img" alt="Logo Colegio">` : '<div style="font-weight:bold; color:#475569; font-size:14px;">ESCALANTE "A"</div>'}
                </div>
                <div class="header-title">
                    <h5>ESTADO PLURINACIONAL DE BOLIVIA - MINISTERIO DE EDUCACIÓN</h5>
                    <h3>UNIDAD EDUCATIVA PROF. JAIME ALFONSO ESCALANTE GUTIÉRREZ "A"</h3>
                    <p>${filtroTexto}</p>
                </div>
                <div class="header-info">
                    <strong>GESTIÓN ESCOLAR:</strong> 2026<br>
                    <strong>FECHA:</strong> ${fechaHoy}<br>
                    <strong>HORA:</strong> ${horaHoy}<br>
                    <strong>CÓDIGO SIE:</strong> 80122379
                </div>
            </div>

            <table class="tabla-listado">
                <thead>
                    <tr>
                        <th style="width: 6%;">N°</th>
                        <th style="width: 38%;">NOMBRE COMPLETO</th>
                        <th style="width: 18%;">C.I.</th>
                        <th style="width: 20%;">CATEGORÍA</th>
                        <th style="width: 18%;">N° ÍTEM</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>

            <div class="resumen-footer">
                <span>TOTAL DOCENTES: ${filasAImprimir.length}</span>
                <span>SANTA CRUZ - BOLIVIA</span>
            </div>
        </body>
        </html>
    `;

    const printWin = window.open('', '_blank', 'width=1050,height=800');
    if (printWin) {
        printWin.document.write(htmlContent);
        printWin.document.close();
        printWin.focus();
        setTimeout(() => { printWin.print(); }, 400);
    } else {
        let iframe = document.getElementById('iframeImpresionDocentes');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'iframeImpresionDocentes';
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            document.body.appendChild(iframe);
        }
        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write(htmlContent);
        doc.close();
        setTimeout(() => {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }, 400);
    }
}
</script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>

