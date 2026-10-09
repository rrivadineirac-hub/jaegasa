<?php
/**
 * ============================================================
 * vadministrativo.php  —  Vista del CRUD Administrativo (sin JS)
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Recibe del controlador:
 *   $administrativos  -> lista de administrativos
 *   $categorias       -> categorías para el formulario
 *   $flash            -> mensaje ['tipo','mensaje'] (opcional)
 *   $buscar           -> término de búsqueda actual
 * ============================================================
 */

require_once BASE_PATH . '/View/header.php';
require_once BASE_PATH . '/partes/nacionalidades.php';
require_once BASE_PATH . '/partes/bolivia.php';

// Helpers locales
$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$genero = fn($g) => $g === 'M' ? 'Masculino' : ($g === 'F' ? 'Femenino' : '—');
$rutaFoto = fn($foto) => BASE_URL . 'inagenes/Admi/' . rawurlencode(!empty($foto) ? $foto : 'default.png');

$accionGuardar  = BASE_URL . 'index.php?c=administrativo&a=guardar';
$accionEliminar = BASE_URL . 'index.php?c=administrativo&a=eliminar';
$fotoDefault    = BASE_URL . 'inagenes/Admi/default.png';

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
        <h1 class="page-title"><i class="bi bi-person-workspace"></i> Administrativos</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Gestión del personal administrativo</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-success shadow-sm px-3" onclick="imprimirListadoAdministrativos()" title="Imprimir listado">
            <i class="bi bi-printer-fill me-1"></i> Imprimir
        </button>
        <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
            <i class="bi bi-plus-lg"></i> Nuevo administrativo
        </button>
    </div>
</div>



<!-- Tabla -->
<div class="card-panel">

    <div class="tabla-toolbar">
        <form method="get" action="<?= BASE_URL ?>index.php" class="buscador-form">
            <input type="hidden" name="c" value="administrativo">
            <div class="buscador">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="<?= $e($buscar ?? '') ?>"
                       placeholder="Buscar por nombre, CI o usuario..." autocomplete="off">
            </div>
            <button type="submit" class="btn btn-sm btn-jaegasa">Buscar</button>
            <a href="<?= BASE_URL ?>index.php?c=administrativo" id="btnLimpiarAdministrativo" class="btn btn-sm btn-outline-secondary" style="<?= empty($buscar) ? 'display: none;' : '' ?>">Limpiar</a>
        </form>
        <div class="d-flex flex-column align-items-end justify-content-start align-self-start mt-1">
            <div class="mb-1 text-end">
                <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                <div class="text-primary fw-semibold fs-6">
                    <i class="bi bi-calendar2-check"></i> <?= $e($gestionActual ?? date('Y')) ?>
                </div>
            </div>
            <span class="tabla-total m-0"><?= count($administrativos) ?> ADMINISTRATIVO(S) REGISTRADO(S)</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaAdmin">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Foto</th>
                    <th>Nombre completo</th>
                    <th>CI</th>
                    <th>Cargo</th>
                    <th>Categoría</th>
                    <th>Ítem</th>
                    <th>Teléfono</th>
                    <th>Usuario</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($administrativos)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="bi bi-inbox"></i> No hay administrativos registrados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($administrativos as $i => $a): ?>
                        <?php
                            $idAdm = (int) $a['idAdministrativo'];
                            $nombreCompleto = trim($a['apPaterno'] . ' ' . $a['apMaterno'] . ' ' . $a['nombre']);
                        ?>
                        <tr data-paterno="<?= $e($a['apPaterno']) ?>"
                            data-materno="<?= $e($a['apMaterno']) ?>"
                            data-nombre="<?= $e($a['nombre']) ?>"
                            data-ci="<?= $e($a['ci']) ?>">
                            <td><?= $i + 1 ?></td>
                            <td>
                                <img src="<?= $rutaFoto($a['foto']) ?>" alt="Foto"
                                     class="tabla-foto"
                                     onerror="this.src='<?= $fotoDefault ?>'">
                            </td>
                            <td class="fw-semibold"><?= $e($nombreCompleto) ?></td>
                            <td><?= $e($a['ci']) ?></td>
                            <td>
                                <?php if (!empty($a['cargo'])): ?>
                                    <span class="badge bg-secondary-subtle text-dark border"><?= $e($a['cargo']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($a['categoria'])): ?>
                                    <span class="badge-cat"><?= $e($a['categoria']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $e($a['nitem'] ?: '—') ?></td>
                            <td><?= $e($a['telefono'] ?: '—') ?></td>
                            <td><?= $e($a['usuario'] ?: '—') ?></td>
                            <td class="text-end text-nowrap">
                                <div class="acciones-tabla">
                                    <button class="btn-accion btn-ver" title="Mostrar completo"
                                            data-bs-toggle="modal" data-bs-target="#modalVer<?= $idAdm ?>">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                    <button class="btn-accion btn-editar" title="Editar"
                                            data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idAdm ?>">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn-accion btn-eliminar" title="Eliminar"
                                            data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $idAdm ?>">
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
foreach ($administrativos as $a):
    $idAdm = (int) $a['idAdministrativo'];
    $nombreCompleto = trim($a['apPaterno'] . ' ' . $a['apMaterno'] . ' ' . $a['nombre']);
?>

    <!-- ===== Modal VER <?= $idAdm ?> ===== -->
    <div class="modal fade" id="modalVer<?= $idAdm ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-vcard"></i> Detalle del administrativo</h5>
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
                    </div>

                    <h6 class="form-seccion mt-4">Datos laborales</h6>
                    <div class="detalle-grid">
                        <div class="detalle-item"><span>Cargo</span><b><?= $e($a['cargo'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Categoría</span><b><?= $e($a['categoria'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>N.º de ítem</span><b><?= $e($a['nitem'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>RDA</span><b><?= $e($a['rda'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Fecha de registro</span><b><?= $e($a['fechaRegistro'] ?? '—') ?></b></div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-success shadow-sm px-3" onclick="imprimirFichaAdministrativo(<?= $idAdm ?>)" title="Imprimir Ficha de Administrativo">
                        <i class="bi bi-printer-fill me-1"></i> Imprimir Ficha
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-jaegasa" data-bs-dismiss="modal"
                                data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idAdm ?>">
                            <i class="bi bi-pencil-fill"></i> Editar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Modal EDITAR <?= $idAdm ?> ===== -->
    <div class="modal fade" id="modalEditar<?= $idAdm ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" enctype="multipart/form-data" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar administrativo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idAdministrativo" value="<?= $idAdm ?>">

                        <div class="foto-uploader">
                            <img src="<?= $rutaFoto($a['foto']) ?>" alt="Foto actual"
                                 onerror="this.src='<?= $fotoDefault ?>'">
                            <div class="foto-uploader-info">
                                <label class="form-label mb-1">Cambiar foto</label>
                                <input type="file" class="form-control form-control-sm" name="foto"
                                       accept="image/png, image/jpeg, image/webp">
                                <small class="text-muted">Dejar vacío para conservar la actual.</small>
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
                            <div class="col-md-3">
                                <label class="form-label">Cargo</label>
                                <input type="text" class="form-control" name="cargo" value="<?= $e($a['cargo'] ?? '') ?>" placeholder="Ej: Secretario, Director...">
                            </div>
                            <div class="col-md-3">
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
                            <div class="col-md-3">
                                <label class="form-label">N.º de ítem</label>
                                <input type="text" class="form-control" name="nitem" value="<?= $e($a['nitem']) ?>">
                            </div>
                            <div class="col-md-3">
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

    <!-- ===== Modal ELIMINAR <?= $idAdm ?> ===== -->
    <div class="modal fade" id="modalEliminar<?= $idAdm ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionEliminar ?>" method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Eliminar administrativo</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <td>
                        <img src="<?= $rutaFoto($a['foto']) ?>" alt="Foto"
                                class="tabla-fotoeliminar"
                                onerror="this.src='<?= $fotoDefault ?>'">
                    </td>
                    <div class="modal-body">
                        <input type="hidden" name="idAdministrativo" value="<?= $idAdm ?>">
                        <p>¿Está seguro de eliminar a <b><?= $e($nombreCompleto) ?></b>?</p>
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
                    <h5 class="modal-title">Nuevo administrativo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idAdministrativo" value="0">

                    <div class="foto-uploader">
                        <img src="<?= $fotoDefault ?>" alt="Vista previa" onerror="this.src='<?= $fotoDefault ?>'">
                        <div class="foto-uploader-info">
                            <label class="form-label mb-1">Foto</label>
                            <input type="file" class="form-control form-control-sm" name="foto"
                                   accept="image/png, image/jpeg, image/webp">
                            <small class="text-muted">JPG, PNG o WEBP. Máx. 2 MB. Opcional.</small>
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
                            <input type="email" class="form-control" name="correo" id="correoNuevoAdmin" required placeholder="ejemplo@correo.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono *</label>
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
                        <div class="col-md-3">
                            <label class="form-label">Cargo</label>
                            <input type="text" class="form-control" name="cargo" placeholder="Ej: Secretario, Director...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="categoria">
                                <option value="">Seleccione...</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= $e($c['nombre']) ?>"><?= $e($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">N.º de ítem</label>
                            <input type="text" class="form-control" name="nitem">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">RDA</label>
                            <input type="text" class="form-control" name="rda">
                        </div>
                    </div>

                    <h6 class="form-seccion mt-4">Acceso al sistema</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Usuario *</label>
                            <input type="text" class="form-control bg-light" name="usuario" id="usuarioNuevoAdmin" readonly placeholder="Se autocompleta con el correo">
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

<script src="<?= BASE_URL ?>Js/persona_autocompletar.js?v=<?= @filemtime(BASE_PATH . '/Js/persona_autocompletar.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const correoInp = document.getElementById('correoNuevoAdmin');
    const userInp   = document.getElementById('usuarioNuevoAdmin');
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
                    if (text.includes('no hay administrativos')) return;
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
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="bi bi-search fs-3 d-block mb-1"></i>
                            No se encontraron administrativos que coincidan con "<strong>${inputBuscar.value}</strong>".
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
                badgeTotal.textContent = `${visibles} ADMINISTRATIVO(S) REGISTRADO(S)`;
            }
        }

        const btnLimpiarAdministrativo = document.getElementById('btnLimpiarAdministrativo');

        function actualizarBotonLimpiarAdministrativo() {
            if (!inputBuscar || !btnLimpiarAdministrativo) return;
            if (inputBuscar.value.trim().length > 0) {
                btnLimpiarAdministrativo.style.display = 'inline-block';
            } else {
                btnLimpiarAdministrativo.style.display = 'none';
            }
        }

        inputBuscar.addEventListener('input', function () {
            actualizarBotonLimpiarAdministrativo();
            filtrarTabla();
        });

        if (btnLimpiarAdministrativo) {
            btnLimpiarAdministrativo.addEventListener('click', function (e) {
                if (inputBuscar.value.trim() !== '') {
                    inputBuscar.value = '';
                    actualizarBotonLimpiarAdministrativo();
                    filtrarTabla();
                    if (window.location.search.includes('q=')) {
                        e.preventDefault();
                        window.location.href = '<?= BASE_URL ?>index.php?c=administrativo';
                    } else {
                        e.preventDefault();
                    }
                }
            });
        }

        actualizarBotonLimpiarAdministrativo();
        if (inputBuscar.value.trim() !== '') {
            filtrarTabla();
        }
    }
});

// =========================================================================
// FUNCIONES DE IMPRESIÓN (FICHA INDIVIDUAL Y LISTADO GENERAL)
// =========================================================================

function abrirVentanaImpresion(html) {
    const ventana = window.open('', '_blank', 'width=950,height=750');
    if (!ventana) {
        alert('Por favor permita las ventanas emergentes (popups) para imprimir.');
        return;
    }
    ventana.document.write(html);
    ventana.document.close();
}

function imprimirFichaAdministrativo(idAdm) {
    const modal = document.getElementById('modalVer' + idAdm);
    if (!modal) {
        alert('No se pudo encontrar la información del administrativo.');
        return;
    }

    const logoSrc = window.schoolLogoBase64 || '';
    const imgFoto = modal.querySelector('.detalle-cabecera img');
    const fotoSrc = imgFoto ? imgFoto.src : '';
    const nombre = modal.querySelector('.detalle-cabecera h3')?.textContent.trim() || 'ADMINISTRATIVO';
    const categoriaBadge = modal.querySelector('.badge-cat')?.textContent.trim() || '—';
    const usuario = modal.querySelector('.detalle-usuario')?.textContent.replace(/[\n\r]/g, '').trim() || '—';

    const items = modal.querySelectorAll('.detalle-grid .detalle-item');
    let datosMap = {};
    items.forEach(it => {
        const span = it.querySelector('span')?.textContent.trim();
        const b = it.querySelector('b')?.textContent.trim();
        if (span) datosMap[span] = b || '—';
    });

    const ci = datosMap['CI'] || '—';
    const genero = datosMap['Género'] || '—';
    const fechaNac = datosMap['Fecha de nacimiento'] || '—';
    const nacionalidad = datosMap['Nacionalidad'] || '—';
    const correo = datosMap['Correo'] || '—';
    const telefono = datosMap['Teléfono'] || '—';
    const direccion = datosMap['Dirección'] || '—';

    const cargo = datosMap['Cargo'] || '—';
    const categoria = datosMap['Categoría'] || categoriaBadge;
    const nitem = datosMap['N.º de ítem'] || '—';
    const rda = datosMap['RDA'] || '—';
    const fechaReg = datosMap['Fecha de registro'] || '—';

    const fechaHoy = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    const horaHoy = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

    const html = `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Ficha de Administrativo - ${nombre}</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
            <style>
                @page { size: letter portrait; margin: 12mm; }
                body { font-family: 'Segoe UI', Roboto, sans-serif; color: #1e293b; background: #fff; font-size: 13px; line-height: 1.4; padding: 15px; }
                .report-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #0284c7; padding-bottom: 12px; margin-bottom: 20px; }
                .report-header img { height: 75px; width: auto; max-width: 140px; object-fit: contain; }
                .header-title { text-align: center; flex: 1; margin: 0 15px; }
                .header-title h2 { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
                .header-title h3 { font-size: 14px; font-weight: 700; color: #0284c7; margin: 3px 0 0 0; text-transform: uppercase; }
                .header-title p { font-size: 11px; color: #64748b; margin: 2px 0 0 0; }
                .header-meta { text-align: right; font-size: 11px; color: #475569; min-width: 130px; }

                .card-profile { display: flex; gap: 20px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; background: #f8fafc; margin-bottom: 20px; }
                .photo-box { width: 110px; height: 130px; border-radius: 6px; border: 2px solid #cbd5e1; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #fff; }
                .photo-box img { width: 100%; height: 100%; object-fit: cover; }
                .profile-info { flex: 1; }
                .profile-info h4 { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0; }
                .profile-badge { display: inline-block; padding: 3px 10px; font-size: 11px; font-weight: 600; color: #0369a1; background: #e0f2fe; border-radius: 12px; margin-bottom: 10px; }

                .sec-title { font-size: 13px; font-weight: 700; color: #0f172a; border-left: 4px solid #0284c7; padding-left: 8px; margin: 18px 0 10px 0; text-transform: uppercase; }
                
                .info-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
                .info-table th, .info-table td { padding: 7px 10px; border: 1px solid #cbd5e1; font-size: 12px; }
                .info-table th { background-color: #f1f5f9; color: #334155; font-weight: 600; width: 25%; }
                .info-table td { background-color: #fff; color: #0f172a; font-weight: 500; }

                .firmas-container { display: flex; justify-content: space-around; margin-top: 60px; padding-top: 10px; page-break-inside: avoid; }
                .firma-box { text-align: center; width: 220px; border-top: 1px dashed #64748b; padding-top: 6px; }
                .firma-box p { margin: 0; font-size: 11px; font-weight: 600; color: #334155; }
                .firma-box span { font-size: 10px; color: #64748b; }

                @media print {
                    body { padding: 0; }
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
            <div class="report-header">
                <div>
                    ${logoSrc ? `<img src="${logoSrc}" alt="Logo">` : ''}
                </div>
                <div class="header-title">
                    <h2>Unidad Educativa Jaime Escalante</h2>
                    <h3>Ficha de Personal Administrativo</h3>
                    <p>CÓDIGO SIE: 80730784 • GESTIÓN 2026</p>
                </div>
                <div class="header-meta">
                    <b>Fecha:</b> ${fechaHoy}<br>
                    <b>Hora:</b> ${horaHoy}<br>
                    <b>Estado:</b> ACTIVO
                </div>
            </div>

            <div class="card-profile">
                <div class="photo-box">
                    <img src="${fotoSrc}" alt="Foto Personal" onerror="this.src='${window.location.origin}/SistemaAcademico/inagenes/Admi/default.png'">
                </div>
                <div class="profile-info">
                    <h4>${nombre}</h4>
                    <span class="profile-badge">Categoría: ${categoria}</span>
                    <p style="margin: 0; font-size: 12px; color: #475569;"><b>Usuario de Sistema:</b> ${usuario}</p>
                    <p style="margin: 3px 0 0 0; font-size: 12px; color: #475569;"><b>Cargo Oficial:</b> ${cargo}</p>
                </div>
            </div>

            <div class="sec-title">DATOS PERSONALES</div>
            <table class="info-table">
                <tr>
                    <th>Cédula de Identidad (CI)</th>
                    <td>${ci}</td>
                    <th>Género</th>
                    <td>${genero}</td>
                </tr>
                <tr>
                    <th>Fecha de Nacimiento</th>
                    <td>${fechaNac}</td>
                    <th>Nacionalidad</th>
                    <td>${nacionalidad}</td>
                </tr>
                <tr>
                    <th>Correo Electrónico</th>
                    <td>${correo}</td>
                    <th>Teléfono / Celular</th>
                    <td>${telefono}</td>
                </tr>
                <tr>
                    <th>Dirección de Domicilio</th>
                    <td colspan="3">${direccion}</td>
                </tr>
            </table>

            <div class="sec-title">INFORMACIÓN LABORAL</div>
            <table class="info-table">
                <tr>
                    <th>Cargo Asignado</th>
                    <td>${cargo}</td>
                    <th>Categoría Laboral</th>
                    <td>${categoria}</td>
                </tr>
                <tr>
                    <th>Número de Ítem</th>
                    <td>${nitem}</td>
                    <th>Registro RDA</th>
                    <td>${rda}</td>
                </tr>
                <tr>
                    <th>Fecha de Registro</th>
                    <td colspan="3">${fechaReg}</td>
                </tr>
            </table>

            <div class="firmas-container">
                <div class="firma-box">
                    <p>Firma del Administrativo</p>
                    <span>CI: ${ci}</span>
                </div>
                <div class="firma-box">
                    <p>Dirección / Recursos Humanos</p>
                    <span>U.E. Jaime Escalante</span>
                </div>
                <div class="firma-box">
                    <p>Sello Institucional</p>
                    <span>Verificación de Datos</span>
                </div>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 700);
                };
            <\/script>
        </body>
        </html>
    `;

    abrirVentanaImpresion(html);
}

function imprimirListadoAdministrativos() {
    const logoSrc = window.schoolLogoBase64 || '';
    const tbody = document.querySelector('#tablaAdmin tbody');
    let filasAImprimir = [];

    if (tbody) {
        const trs = tbody.querySelectorAll('tr');
        let contador = 1;
        trs.forEach(tr => {
            if (tr.style.display !== 'none' && !tr.classList.contains('fila-sin-resultados') && !tr.querySelector('td[colspan]')) {
                const tds = tr.querySelectorAll('td');
                if (tds.length >= 9) {
                    filasAImprimir.push({
                        num: contador++,
                        nombre: tds[2].textContent.trim(),
                        ci: tds[3].textContent.trim(),
                        cargo: tds[4].textContent.trim(),
                        categoria: tds[5].textContent.trim(),
                        item: tds[6].textContent.trim(),
                        telefono: tds[7].textContent.trim(),
                        usuario: tds[8].textContent.trim()
                    });
                }
            }
        });
    }

    if (filasAImprimir.length === 0) {
        alert('No hay registros de administrativos visibles para imprimir.');
        return;
    }

    const fechaHoy = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    const horaHoy = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

    let rowsHtml = '';
    filasAImprimir.forEach(item => {
        rowsHtml += `
            <tr>
                <td style="text-align: center; font-weight: bold;">${item.num}</td>
                <td style="font-weight: 600; text-align: left;">${item.nombre}</td>
                <td style="text-align: center;">${item.ci}</td>
                <td style="text-align: center;">${item.cargo}</td>
                <td style="text-align: center;">${item.categoria}</td>
                <td style="text-align: center;">${item.item}</td>
                <td style="text-align: center;">${item.telefono}</td>
                <td style="text-align: center;">${item.usuario}</td>
            </tr>
        `;
    });

    const html = `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Listado de Personal Administrativo</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
            <style>
                @page { size: letter landscape; margin: 10mm; }
                body { font-family: 'Segoe UI', Roboto, sans-serif; color: #1e293b; background: #fff; font-size: 12px; line-height: 1.3; padding: 15px; }
                .report-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #0284c7; padding-bottom: 10px; margin-bottom: 15px; }
                .report-header img { height: 65px; width: auto; max-width: 130px; object-fit: contain; }
                .header-title { text-align: center; flex: 1; margin: 0 15px; }
                .header-title h2 { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; text-transform: uppercase; }
                .header-title h3 { font-size: 14px; font-weight: 700; color: #0284c7; margin: 2px 0 0 0; text-transform: uppercase; }
                .header-title p { font-size: 11px; color: #64748b; margin: 2px 0 0 0; }
                .header-meta { text-align: right; font-size: 11px; color: #475569; min-width: 140px; }

                .table-report { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
                .table-report th { background-color: #0284c7 !important; color: #ffffff !important; font-weight: 700; text-transform: uppercase; border: 1px solid #0369a1 !important; padding: 7px 6px; text-align: center; }
                .table-report td { border: 1px solid #cbd5e1 !important; padding: 6px 6px; vertical-align: middle; }
                .table-report tbody tr:nth-child(even) { background-color: #f8fafc; }

                .firmas-container { display: flex; justify-content: space-around; margin-top: 45px; padding-top: 10px; page-break-inside: avoid; }
                .firma-box { text-align: center; width: 220px; border-top: 1px dashed #64748b; padding-top: 5px; }
                .firma-box p { margin: 0; font-size: 11px; font-weight: 600; color: #334155; }
                .firma-box span { font-size: 10px; color: #64748b; }

                @media print {
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            <div class="report-header">
                <div>
                    ${logoSrc ? `<img src="${logoSrc}" alt="Logo">` : ''}
                </div>
                <div class="header-title">
                    <h2>Unidad Educativa Jaime Escalante</h2>
                    <h3>Nómina de Personal Administrativo</h3>
                    <p>CÓDIGO SIE: 80730784 • GESTIÓN 2026 • REGISTRO GENERAL</p>
                </div>
                <div class="header-meta">
                    <b>Fecha:</b> ${fechaHoy}<br>
                    <b>Hora:</b> ${horaHoy}<br>
                    <b>Total Registros:</b> ${filasAImprimir.length}
                </div>
            </div>

            <table class="table-report">
                <thead>
                    <tr>
                        <th style="width: 4%;">N°</th>
                        <th style="width: 25%;">Nombre Completo</th>
                        <th style="width: 10%;">CI</th>
                        <th style="width: 14%;">Cargo</th>
                        <th style="width: 12%;">Categoría</th>
                        <th style="width: 10%;">Ítem</th>
                        <th style="width: 12%;">Teléfono</th>
                        <th style="width: 13%;">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>

            <div class="firmas-container">
                <div class="firma-box">
                    <p>Responsable de Personal</p>
                    <span>U.E. Jaime Escalante</span>
                </div>
                <div class="firma-box">
                    <p>Dirección Administrativa</p>
                    <span>Firma y Sello</span>
                </div>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 700);
                };
            <\/script>
        </body>
        </html>
    `;

    abrirVentanaImpresion(html);
}

</script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>
