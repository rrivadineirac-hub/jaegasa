<?php
/**
 * vqr.php — Vista del CRUD QR
 * Sistema Académico JAEGASA
 */

require_once BASE_PATH . '/View/header.php';

$fotoDefault = BASE_URL . 'inagenes/Estudiante/default.png';
$rutaFoto = function (?string $foto) {
    if (empty($foto)) return BASE_URL . 'inagenes/Estudiante/default.png';
    return BASE_URL . 'inagenes/Estudiante/' . $foto;
};

$e = fn(?string $val) => htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');

// QR_DIR_RELATIVO ya está definida en qr.controller.php (no volver a declarar aquí)
$qrExiste = function (?string $nombreArchivo) {
    if (empty($nombreArchivo)) return false;
    return is_file(BASE_PATH . QR_DIR_RELATIVO . $nombreArchivo);
};
$qrUrl = fn(string $nombreArchivo) => BASE_URL . 'imagenes/qr/' . $nombreArchivo;
?>

<link rel="stylesheet" href="<?= BASE_URL ?>Css/qr.css?v=<?= @filemtime(BASE_PATH . '/Css/qr.css') ?>">

<!-- Datos de cursos para el filtrado dinámico de paralelos -->
<script>
    const CURSOS_DATA = <?= json_encode(array_map(fn($c) => [
        'idCurso'     => (int) $c['idCurso'],
        'cursoNombre' => $c['cursoNombre'] ?? $c['nombreCurso'] ?? $c['nombre'] ?? '',
        'idNivel'     => (int) $c['idNivel'],
        'idTurno'     => (int) $c['idTurno'],
        'idParalelo'  => (int) $c['idParalelo'],
    ], $cursos ?? [])) ?>;
</script>

<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-qr-code"></i> Generador de Credenciales y QRs</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Generación de credenciales en PDF y códigos QR para estudiantes</p>
    </div>
</div>



<?php
$modoBusqueda = $_GET['modoBusqueda'] ?? (!empty($_GET['grado']) || !empty($_GET['paralelo']) || !empty($_GET['nivel']) || !empty($_GET['turno']) ? 'curso' : 'estudiante');
$realizoBusqueda = (!empty($_GET['q']) || !empty($_GET['grado']) || !empty($_GET['paralelo']) || !empty($_GET['nivel']) || !empty($_GET['turno']));

$hayBusquedaCurso = ($modoBusqueda === 'curso' && (!empty($_GET['grado']) || !empty($_GET['paralelo']) || !empty($_GET['nivel']) || !empty($_GET['turno'])));

$totalConRude = 0;
$totalConQr   = 0;

if ($hayBusquedaCurso && !empty($estudiantes)) {
    foreach ($estudiantes as $estItem) {
        if (!empty($estItem['rude'])) {
            $totalConRude++;
            if ($qrExiste($estItem['qr'] ?? null)) {
                $totalConQr++;
            }
        }
    }
}
$todoElCursoTieneQr = ($hayBusquedaCurso && $totalConRude > 0 && $totalConQr >= $totalConRude);
?>

<div class="card-panel border-0 shadow-sm p-0 overflow-hidden">
    <div class="tabla-toolbar-sticky" style="position: sticky; top: 64px; z-index: 1020; background-color: #ffffff; padding: 1.25rem 1.5rem 0.8rem 1.5rem; border-bottom: 2px solid #eef2f4; border-radius: 16px 16px 0 0; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);">
        <form method="get" action="<?= BASE_URL ?>index.php" class="d-flex flex-column gap-2 w-100" id="formBusquedaQr">
            <input type="hidden" name="c" value="qr">
            <input type="hidden" name="modoBusqueda" id="inputModoBusqueda" value="<?= $e($modoBusqueda ?? 'estudiante') ?>">

            <!-- Selector de modo (izquierda) y Gestión Escolar (derecha) -->
            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                <div class="modo-busqueda">
                    <div class="btn-group" role="group" aria-label="Modo de búsqueda">
                        <input type="radio" class="btn-check" name="modoBusquedaRadio" id="modoEstudiante" value="estudiante"
                               <?= ($modoBusqueda ?? 'estudiante') === 'estudiante' ? 'checked' : '' ?> autocomplete="off">
                        <label class="btn btn-outline-jaegasa" for="modoEstudiante">
                            <i class="bi bi-person-search"></i> Buscar por estudiante
                        </label>

                        <input type="radio" class="btn-check" name="modoBusquedaRadio" id="modoCurso" value="curso"
                               <?= ($modoBusqueda ?? '') === 'curso' ? 'checked' : '' ?> autocomplete="off">
                        <label class="btn btn-outline-jaegasa" for="modoCurso">
                            <i class="bi bi-people"></i> Buscar por curso
                        </label>
                    </div>
                </div>

                <div class="d-flex flex-column align-items-end justify-content-start align-self-start">
                    <div class="mb-1 text-end">
                        <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                        <div class="text-primary fw-semibold fs-6">
                            <i class="bi bi-calendar2-check"></i> <?= $e($gestionActual ?? date('Y')) ?>
                        </div>
                    </div>
                    <span class="tabla-total m-0"><?= !empty($realizoBusqueda) ? count($estudiantes) . ' ESTUDIANTE(S) ENCONTRADO(S)' : 'SIN BÚSQUEDA REALIZADA' ?></span>
                </div>
            </div>

            <!-- ================================================================ -->
            <!-- BLOQUE 1: Búsqueda por estudiante -->
            <!-- ================================================================ -->
            <fieldset class="bloque-busqueda mb-2 <?= ($modoBusqueda ?? 'estudiante') === 'curso' ? 'd-none' : '' ?>" id="bloqueEstudiante">
                <h6 class="form-seccion mb-2 fw-semibold text-jaegasa"><i class="bi bi-filter-square"></i> BÚSQUEDA POR ESTUDIANTE</h6>
                <p class="text-muted small mb-3">La busqueda la pueedes realizar por Apellidos Nombres, C.I. o RUDE.</p>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="buscador flex-grow-1" style="max-width: 450px;">
                        <i class="bi bi-search"></i>
                        <input type="text" name="q" id="inputBuscarEstudiante"
                               value="<?= $e($buscar ?? '') ?>"
                               placeholder="BUSCAR ESTUDIANTE POR NOMBRE, CI O RUDE..." style="width: 100%;" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-sm btn-jaegasa">Buscar</button>
                    <a href="<?= BASE_URL ?>index.php?c=qr" id="btnLimpiarEstudiante"
                       class="btn btn-sm btn-outline-secondary btn-limpiar-todo"
                       style="<?= empty($buscar) ? 'display: none;' : '' ?>">Limpiar</a>

                    <!-- CONTENEDOR BOTON IMPRIMIR SELECCION EN BÚSQUEDA POR ESTUDIANTE -->
                    <div class="contenedor-imprimir-seleccion position-relative d-none" style="display: none !important;">
                        <span class="badge-contador-seleccion position-absolute badge rounded-pill shadow-sm px-3 py-1 fw-bold text-white"
                              style="font-size: 0.85rem; top: -26px; left: 50%; transform: translateX(-50%); z-index: 10; background-color: #ff6b00;">
                            0 Seleccionados
                        </span>
                        <button type="button" class="btn-imprimir-seleccion btn btn-sm btn-success text-white fw-bold d-inline-flex align-items-center gap-1" disabled
                                title="Seleccione exactamente 3, 6 o 9 estudiantes para habilitar la impresión">
                            <i class="bi bi-printer-fill fs-6"></i> Imprimir Selección
                        </button>
                    </div>
                </div>
            </fieldset>

            <!-- ================================================================ -->
            <!-- BLOQUE 2: Búsqueda por curso -->
            <!-- ================================================================ -->
            <fieldset class="bloque-busqueda mb-2 <?= ($modoBusqueda ?? 'estudiante') === 'estudiante' ? 'd-none' : '' ?>" id="bloqueCurso">
                <h6 class="form-seccion mb-2 fw-semibold text-jaegasa"><i class="bi bi-filter-square"></i> BÚSQUEDA POR CURSO</h6>
                <p class="text-muted small mb-3">Seleccione el curso, paralelo, nivel y turno para consultar los estudiantes y generar sus códigos QR.</p>

                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-semibold mb-1">Curso / Grado</label>
                        <select class="form-select form-select-sm curso-selector" data-tipo="grado" name="grado" <?= empty($cursosUnicos) ? 'disabled' : '' ?>>
                            <option value="">Seleccione...</option>
                            <?php foreach ($cursosUnicos as $id => $nomG): ?>
                                <option value="<?= $e($id) ?>" <?= (isset($_GET['grado']) && (string)$_GET['grado'] === (string)$id) ? 'selected' : '' ?>><?= $e($nomG) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold mb-1">Paralelo</label>
                        <select class="form-select form-select-sm curso-selector" data-tipo="paralelo" name="paralelo" <?= empty($paralelosUnicos) ? 'disabled' : '' ?>>
                            <option value="">Seleccione...</option>
                            <?php foreach ($paralelosUnicos as $idP => $nomP): ?>
                                <option value="<?= (int) $idP ?>" <?= (isset($_GET['paralelo']) && (string)$_GET['paralelo'] === (string)$idP) ? 'selected' : '' ?>><?= $e($nomP) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold mb-1">Nivel</label>
                        <select class="form-select form-select-sm curso-selector" data-tipo="nivel" name="nivel" <?= empty($nivelesUnicos) ? 'disabled' : '' ?>>
                            <option value="">Seleccione...</option>
                            <?php foreach ($nivelesUnicos as $idN => $nomN): ?>
                                <option value="<?= (int) $idN ?>" <?= (isset($_GET['nivel']) && (string)$_GET['nivel'] === (string)$idN) ? 'selected' : '' ?>><?= $e($nomN) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold mb-1">Turno</label>
                        <select class="form-select form-select-sm curso-selector" data-tipo="turno" name="turno" <?= empty($turnosUnicos) ? 'disabled' : '' ?>>
                            <option value="">Seleccione...</option>
                            <?php foreach ($turnosUnicos as $idT => $nomT): ?>
                                <option value="<?= (int) $idT ?>" <?= (isset($_GET['turno']) && (string)$_GET['turno'] === (string)$idT) ? 'selected' : '' ?>><?= $e($nomT) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-auto d-flex gap-2 align-items-end flex-wrap">
                        <button type="submit" class="btn btn-sm btn-jaegasa">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                        <?php if ($hayBusquedaCurso): ?>
                            <a href="<?= BASE_URL ?>index.php?c=qr&modoBusqueda=curso" id="btnLimpiarCurso" class="btn btn-sm btn-outline-secondary btn-limpiar-todo">Limpiar</a>

                            <?php if ($todoElCursoTieneQr): ?>
                                <!-- SI YA TIENE TODO EL CURSO SUS QRS: Muestra botón de Imprimir QRs -->
                                <a href="<?= BASE_URL ?>index.php?c=qr&a=pdf_curso<?= $urlFiltros ?>" target="_blank"
                                   id="btnImprimirCurso"
                                   class="btn btn-sm btn-success text-white fw-bold d-inline-flex align-items-center gap-1 btn-accion-curso"
                                   title="Imprimir credenciales QR de todo el curso (9 por hoja Oficio)">
                                    <i class="bi bi-printer-fill fs-6"></i> Imprimir QRs del curso
                                </a>
                            <?php else: ?>
                                <!-- SI AÚN FALTA AL MENOS UN ESTUDIANTE: Muestra botón de Generar QR de todo el curso -->
                                <a href="<?= BASE_URL ?>index.php?c=qr&a=crear_curso<?= $urlFiltros ?>"
                                   id="btnGenerarCurso"
                                   class="btn btn-sm btn-success text-white fw-bold d-inline-flex align-items-center gap-1 btn-accion-curso"
                                   title="Generar códigos QR para todos los estudiantes del curso con RUDE">
                                    <i class="bi bi-qr-code-scan fs-6"></i> Generar QRs del curso
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- CONTENEDOR BOTON IMPRIMIR SELECCION (Sustituye a Imprimir QRs del curso cuando se marca algun estudiante) -->
                        <div class="contenedor-imprimir-seleccion position-relative d-none" style="display: none !important;">
                            <span class="badge-contador-seleccion position-absolute badge rounded-pill shadow-sm px-3 py-1 fw-bold text-white"
                                  style="font-size: 0.85rem; top: -26px; left: 50%; transform: translateX(-50%); z-index: 10; background-color: #ff6b00;">
                                0 Seleccionados
                            </span>
                            <button type="button" class="btn-imprimir-seleccion btn btn-sm btn-success text-white fw-bold d-inline-flex align-items-center gap-1" disabled
                                    title="Seleccione exactamente 3, 6 o 9 estudiantes para habilitar la impresión">
                                <i class="bi bi-printer-fill fs-6"></i> Imprimir Selección
                            </button>
                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
    </div>

    <div class="table-responsive p-3 p-md-4 pt-0" style="max-height: calc(100vh - 280px); overflow-y: auto;">
        <table class="table table-hover align-middle mb-0" id="tablaQr">
            <thead style="position: sticky; top: 0; z-index: 1010; background-color: #f8fafc;">
                <tr>
                    <th>N°</th>
                    <th>Foto</th>
                    <th>Nombre completo</th>
                    <th>RUDE</th>
                    <th>CI</th>
                    <th>Curso / Paralelo</th>
                    <th>Código QR</th>
                    <th class="text-center" style="width: 90px;">
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <input type="checkbox" class="form-check-input" id="checkTodosQr" style="width: 17px; height: 17px; cursor: pointer;" title="Marcar / Desmarcar todos los que tienen QR">
                            <span class="small">Elegir</span>
                        </div>
                    </th>
                    <th class="text-end" style="width: 80px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($estudiantes)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i> No se encontraron estudiantes con RUDE registrado.
                        </td>
                    </tr>
                <?php else: ?>
                    <tr id="filaNoBusqueda" <?= !empty($realizoBusqueda) ? 'style="display: none;"' : '' ?>>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-search fs-1 d-block text-secondary mb-2 opacity-50"></i>
                            <h6 class="fw-bold text-dark">No se han realizado búsquedas</h6>
                            <p class="small text-muted mb-0">Seleccione un filtro (Curso, Paralelo, Nivel o Turno) o ingrese un nombre para consultar los estudiantes y sus códigos QR.</p>
                        </td>
                    </tr>
                    <?php foreach ($estudiantes as $i => $a): ?>
                        <?php
                            $idEst = (int) $a['idEstudiante'];
                            $nombreCompleto = trim($a['apPaterno'] . ' ' . $a['apMaterno'] . ' ' . $a['nombre']);
                            $rude = $a['rude'];
                            $tieneQr = $qrExiste($a['qr'] ?? null);
                        ?>
                        <tr class="fila-estudiante-item"
                            data-paterno="<?= $e($a['apPaterno'] ?? '') ?>"
                            data-materno="<?= $e($a['apMaterno'] ?? '') ?>"
                            data-nombre="<?= $e($a['nombre'] ?? '') ?>"
                            data-rude="<?= $e($a['rude'] ?? '') ?>"
                            data-ci="<?= $e($a['ci'] ?? '') ?>"
                            <?= empty($realizoBusqueda) ? 'style="display: none;"' : '' ?>>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <img src="<?= $rutaFoto($a['foto']) ?>" alt="Foto"
                                     class="tabla-foto"
                                     onerror="this.src='<?= $fotoDefault ?>'">
                            </td>
                            <td class="fw-semibold"><?= $e($nombreCompleto) ?></td>
                            <td><span class="badge bg-secondary"><?= $e($rude ?: 'Sin RUDE') ?></span></td>
                            <td><?= $e($a['ci'] ?: '—') ?></td>
                            <td>
                                <?php if (!empty($a['cursoNivel'])): ?>
                                    <span class="badge-cat"><?= $e($a['cursoNombre'] ?: $a['cursoNivel']) ?> "<?= $e($a['cursoParalelo']) ?>"</span>
                                <?php else: ?>
                                    <span class="text-muted">Sin inscribir</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($tieneQr): ?>
                                    <img src="<?= $qrUrl($a['qr']) ?>" alt="QR RUDE" class="img-thumbnail qr-preview"
                                         style="width: 48px; height: 48px; cursor: pointer;"
                                         data-src="<?= $qrUrl($a['qr']) ?>"
                                         data-name="<?= $e($nombreCompleto) ?>"
                                         data-rude="<?= $e($rude) ?>"
                                         title="Clic para ampliar" />
                                <?php elseif (empty($rude)): ?>
                                    <span class="text-danger small"><i class="bi bi-exclamation-triangle"></i> Requiere RUDE</span>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="bi bi-qr-code"></i> Sin QR generado</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($tieneQr): ?>
                                    <input type="checkbox" class="form-check-input border-secondary check-estudiante-qr" value="<?= $idEst ?>" style="width: 19px; height: 19px; cursor: pointer;" title="Seleccionar estudiante para credencial QR">
                                <?php else: ?>
                                    <input type="checkbox" class="form-check-input" disabled style="width: 19px; height: 19px;" title="Sin código QR">
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="acciones-tabla">
                                    <?php if (empty($rude)): ?>
                                        <span class="text-muted small">No disponible</span>

                                    <?php elseif (!$tieneQr): ?>
                                        <a href="<?= BASE_URL ?>index.php?c=qr&a=crear&id=<?= $idEst ?><?= $urlFiltros ?>"
                                           class="btn-accion btn-crear" title="Generar Nuevo QR">
                                            <i class="bi bi-qr-code-scan text-success fs-5"></i>
                                        </a>

                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>index.php?c=qr&a=eliminar&id=<?= $idEst ?><?= $urlFiltros ?>"
                                           class="btn-accion btn-eliminar btn-eliminar-qr" title="Eliminar QR"
                                           data-nombre="<?= $e($nombreCompleto) ?>"
                                           data-rude="<?= $e($rude) ?>">
                                            <i class="bi bi-trash text-danger fs-5"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para ver el QR ampliado -->
<div class="modal fade" id="modalVerQr" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 360px;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Código QR del Estudiante</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center bg-light">
                <h5 id="modalQrEstudiante" class="mb-1 text-jaegasa fw-bold" style="font-size: 0.95rem;">-</h5>
                <p id="modalQrRude" class="text-muted font-monospace small mb-3">-</p>
                <div class="p-2 bg-white rounded border d-inline-block shadow-sm">
                    <img id="modalQrImg" src="" alt="QR Ampliado" style="width: 240px; height: 240px; object-fit: contain;" />
                </div>
            </div>
            <div class="modal-footer py-1 d-flex justify-content-between">
                <a id="modalQrDownload" href="" download="" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-download"></i> Descargar QR (.png)
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmación Eliminar QR -->
<div class="modal fade" id="modalConfirmarEliminarQr" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white py-2">
                <h6 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> Confirmar Eliminación de QR</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-danger">
                    <i class="bi bi-qr-code-scan fs-1 d-block mb-2"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1" id="nombreEstudianteEliminar">Estudiante</h6>
                <p class="text-muted small mb-3">RUDE: <span id="rudeEstudianteEliminar" class="fw-semibold text-dark">-</span></p>
                <div class="alert alert-warning border-0 small py-2 px-3 mb-0 text-start">
                    <i class="bi bi-info-circle me-1"></i> ¿Está seguro de que desea eliminar el código QR? Esta acción no se puede deshacer y el estudiante ya no podrá registrar su asistencia por QR hasta generar uno nuevo.
                </div>
            </div>
            <div class="modal-footer bg-light py-2 justify-content-between">
                <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Cancelar
                </button>
                <a id="btnConfirmarEliminarQrUrl" href="#" class="btn btn-sm btn-danger px-3 fw-semibold">
                    <i class="bi bi-trash-fill me-1"></i> Sí, Eliminar QR
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>Js/qr.js?v=<?= @filemtime(BASE_PATH . '/Js/qr.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Handler para confirmación elegante de eliminación de QR
    document.addEventListener('click', function(e) {
        const btnEliminar = e.target.closest('.btn-eliminar-qr');
        if (btnEliminar) {
            e.preventDefault();
            const url = btnEliminar.getAttribute('href');
            const nombre = btnEliminar.getAttribute('data-nombre') || 'este estudiante';
            const rude = btnEliminar.getAttribute('data-rude') || '-';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar Código QR?',
                    html: `¿Está seguro de que desea eliminar el código QR del estudiante?<br><br><strong class="text-danger fs-6">${nombre}</strong><br><span class="badge bg-secondary mt-2">RUDE: ${rude}</span><br><br><small class="text-muted">El estudiante ya no podrá registrar su asistencia por QR hasta generar uno nuevo.</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-trash-fill"></i> Sí, eliminar QR',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: {
                        popup: 'shadow-lg border-0 rounded-4'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            } else {
                const modalEl = document.getElementById('modalConfirmarEliminarQr');
                if (modalEl) {
                    document.getElementById('nombreEstudianteEliminar').textContent = nombre;
                    document.getElementById('rudeEstudianteEliminar').textContent = rude;
                    document.getElementById('btnConfirmarEliminarQrUrl').setAttribute('href', url);
                    const bsModal = new bootstrap.Modal(modalEl);
                    bsModal.show();
                } else if (confirm(`¿Eliminar el código QR de ${nombre}?`)) {
                    window.location.href = url;
                }
            }
        }
    });

    const inputBuscar = document.querySelector('input[name="q"]');
    const tablaQr     = document.getElementById('tablaQr');
    const badgeTotal  = document.querySelector('.tabla-total');

    if (tablaQr) {
        const tbody = tablaQr.querySelector('tbody');

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
            const filas   = tbody.querySelectorAll('tr.fila-estudiante-item');
            const filaNoBusqueda = tbody.querySelector('#filaNoBusqueda');
            let visibles  = 0;

            if (tokens.length === 0) {
                const busquedaRealizada = <?= !empty($realizoBusqueda) ? 'true' : 'false' ?>;
                if (!busquedaRealizada) {
                    filas.forEach(fila => fila.style.display = 'none');
                    if (filaNoBusqueda) filaNoBusqueda.style.display = '';
                    const filaMensaje = tbody.querySelector('.fila-sin-resultados');
                    if (filaMensaje) filaMensaje.style.display = 'none';
                    if (badgeTotal) badgeTotal.textContent = `SIN BÚSQUEDA REALIZADA`;
                    return;
                } else {
                    filas.forEach(fila => { fila.style.display = ''; visibles++; });
                    if (filaNoBusqueda) filaNoBusqueda.style.display = 'none';
                    if (badgeTotal) badgeTotal.textContent = `${visibles} ESTUDIANTE(S) ENCONTRADO(S)`;
                    return;
                }
            }

            if (filaNoBusqueda) filaNoBusqueda.style.display = 'none';

            filas.forEach(fila => {
                const paterno     = normalizarTexto(fila.dataset.paterno || '');
                const materno     = normalizarTexto(fila.dataset.materno || '');
                const nombre      = normalizarTexto(fila.dataset.nombre || '');
                const rude        = normalizarTexto(fila.dataset.rude || '');
                const ci          = normalizarTexto(fila.dataset.ci || '');

                const fullPaterno = [paterno, materno, nombre].filter(Boolean).join(' ');
                const fullNombre  = [nombre, paterno, materno].filter(Boolean).join(' ');
                const palabras    = (fullPaterno + ' ' + fullNombre + ' ' + ci).split(/\s+/).filter(Boolean);

                const coincide = tokens.every(token => {
                    const incluirRude = rude && (token.length >= 3 || /[0-9]/.test(token));
                    return paterno.startsWith(token) ||
                           materno.startsWith(token) ||
                           nombre.startsWith(token)  ||
                           ci.startsWith(token)      ||
                           (incluirRude && rude.startsWith(token)) ||
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
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-search fs-3 d-block mb-1"></i>
                            No se encontraron estudiantes que coincidan con "<strong>${inputBuscar.value}</strong>".
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
                badgeTotal.textContent = `${visibles} ESTUDIANTE(S) ENCONTRADO(S)`;
            }
        }

        const btnLimpiarEstudiante = document.getElementById('btnLimpiarEstudiante');

        function actualizarBotonLimpiarEstudiante() {
            if (!inputBuscar || !btnLimpiarEstudiante) return;
            if (inputBuscar.value.trim().length > 0) {
                btnLimpiarEstudiante.style.display = 'inline-block';
            } else {
                btnLimpiarEstudiante.style.display = 'none';
            }
        }

        if (inputBuscar) {
            inputBuscar.addEventListener('input', function () {
                actualizarBotonLimpiarEstudiante();
                filtrarTabla();
            });

            actualizarBotonLimpiarEstudiante();
            if (inputBuscar.value.trim() !== '') {
                filtrarTabla();
            }
        }
    }
    // Toggle para alternar entre búsqueda por estudiante y búsqueda por curso
    const radiosModo = document.querySelectorAll('input[name="modoBusquedaRadio"]');
    const inputModoOculto = document.getElementById('inputModoBusqueda');
    const bloqueEstudiante = document.getElementById('bloqueEstudiante');
    const bloqueCurso = document.getElementById('bloqueCurso');

    if (radiosModo.length > 0 && bloqueEstudiante && bloqueCurso) {
        radiosModo.forEach(r => r.addEventListener('change', function () {
            const modo = this.value;
            if (inputModoOculto) inputModoOculto.value = modo;
            bloqueEstudiante.classList.toggle('d-none', modo !== 'estudiante');
            bloqueCurso.classList.toggle('d-none', modo === 'estudiante');
        }));
    }

    // ================================================================
    // LOGICA DE SELECCION Y CONTADOR PARA IMPRIMIR SELECCION (3, 6 O 9)
    // ================================================================
    const CLAVE_STORAGE_QR = 'qr_estudiantes_seleccionados_ids';

    function obtenerSeleccionadosStorage() {
        try {
            const raw = sessionStorage.getItem(CLAVE_STORAGE_QR);
            return raw ? JSON.parse(raw) : [];
        } catch(e) {
            return [];
        }
    }

    function guardarSeleccionadosStorage(ids) {
        try {
            sessionStorage.setItem(CLAVE_STORAGE_QR, JSON.stringify(ids));
        } catch(e) {}
    }

    function actualizarInterfazSeleccion() {
        const ids = obtenerSeleccionadosStorage();
        const cant = ids.length;

        const contenedoresSel = document.querySelectorAll('.contenedor-imprimir-seleccion');
        const badgesContador  = document.querySelectorAll('.badge-contador-seleccion');
        const btnsImprimirSel = document.querySelectorAll('.btn-imprimir-seleccion');
        const botonesCurso    = document.querySelectorAll('#btnImprimirCurso, #btnGenerarCurso, .btn-accion-curso');

        badgesContador.forEach(b => {
            b.textContent = `${cant} Seleccionado${cant === 1 ? '' : 's'}`;
        });

        if (cant > 0) {
            // 1. Ocultar de forma INFALIBLE los botones del curso
            botonesCurso.forEach(b => {
                b.classList.add('d-none');
                b.style.setProperty('display', 'none', 'important');
            });

            // 2. Mostrar los contenedores de Imprimir Selección
            contenedoresSel.forEach(c => {
                c.classList.remove('d-none');
                c.style.setProperty('display', 'inline-block', 'important');
            });

            // 3. Habilitar condicionalmente sólo con 3, 6 o 9 marcados (manteniendo color verde)
            const esCantidadValida = (cant === 3 || cant === 6 || cant === 9);
            btnsImprimirSel.forEach(btn => {
                btn.disabled = !esCantidadValida;
                btn.style.setProperty('background-color', '#198754', 'important');
                btn.style.setProperty('border-color', '#198754', 'important');
                btn.style.setProperty('color', '#ffffff', 'important');

                if (esCantidadValida) {
                    btn.classList.remove('opacity-65');
                    btn.style.setProperty('cursor', 'pointer', 'important');
                    btn.title = `Haga clic para imprimir la selección de ${cant} credenciales en hoja Oficio`;
                } else {
                    btn.classList.add('opacity-65');
                    btn.style.setProperty('cursor', 'not-allowed', 'important');
                    btn.title = `Tiene ${cant} seleccionado(s). Debe seleccionar 3, 6 o 9 para habilitar la impresión.`;
                }
            });
        } else {
            // 1. Mostrar los botones del curso si no hay seleccionados
            botonesCurso.forEach(b => {
                b.classList.remove('d-none');
                b.style.removeProperty('display');
            });

            // 2. Ocultar los contenedores de Imprimir Selección
            contenedoresSel.forEach(c => {
                c.classList.add('d-none');
                c.style.setProperty('display', 'none', 'important');
            });

            btnsImprimirSel.forEach(btn => {
                btn.disabled = true;
            });
        }

        // Sincronizar checkboxes visibles en la tabla
        const checksTabla = document.querySelectorAll('.check-estudiante-qr:not(:disabled)');
        checksTabla.forEach(chk => {
            chk.checked = ids.includes(String(chk.value));
        });

        const checkTodos = document.getElementById('checkTodosQr');
        if (checkTodos && checksTabla.length > 0) {
            checkTodos.checked = Array.from(checksTabla).every(c => c.checked);
        }
    }

    function mostrarAlertaLimite9() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Límite alcanzado',
                text: 'Sólo está permitido seleccionar hasta 9 estudiantes.',
                icon: 'warning',
                confirmButtonColor: '#ff6b00',
                confirmButtonText: '<i class="bi bi-check-circle"></i> OK',
                customClass: {
                    popup: 'shadow-lg border-0 rounded-4'
                }
            });
        } else {
            alert('Sólo está permitido seleccionar hasta 9 estudiantes.');
        }
    }

    // Listener para checkboxes de estudiantes
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('check-estudiante-qr')) {
            let ids = obtenerSeleccionadosStorage();
            const idStr = String(e.target.value);

            if (e.target.checked) {
                if (!ids.includes(idStr)) {
                    if (ids.length >= 9) {
                        e.target.checked = false;
                        mostrarAlertaLimite9();
                        return;
                    }
                    ids.push(idStr);
                }
            } else {
                ids = ids.filter(i => i !== idStr);
            }
            guardarSeleccionadosStorage(ids);
            actualizarInterfazSeleccion();
        }
    });

    // Checkbox maestro "Elegir" para la tabla visible
    const checkTodos = document.getElementById('checkTodosQr');
    if (checkTodos) {
        checkTodos.addEventListener('change', function () {
            let ids = obtenerSeleccionadosStorage();
            const checksVisibles = document.querySelectorAll('.check-estudiante-qr:not(:disabled)');

            if (this.checked) {
                let superoLimite = false;
                checksVisibles.forEach(chk => {
                    const val = String(chk.value);
                    if (!ids.includes(val)) {
                        if (ids.length < 9) {
                            ids.push(val);
                        } else {
                            superoLimite = true;
                        }
                    }
                });

                if (superoLimite) {
                    mostrarAlertaLimite9();
                }
            } else {
                checksVisibles.forEach(chk => {
                    const val = String(chk.value);
                    ids = ids.filter(i => i !== val);
                });
            }

            guardarSeleccionadosStorage(ids);
            actualizarInterfazSeleccion();
        });
    }

    // Resetear/limpiar todos los seleccionados al hacer clic en los botones Limpiar
    document.addEventListener('click', function (e) {
        if (e.target.closest('#btnLimpiarCurso, #btnLimpiarEstudiante, .btn-limpiar-todo')) {
            sessionStorage.removeItem(CLAVE_STORAGE_QR);
            actualizarInterfazSeleccion();
        }
    });

    // Listener para delegación de clic en Botones Imprimir Selección
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-imprimir-seleccion');
        if (btn && !btn.disabled) {
            const ids = obtenerSeleccionadosStorage();
            if (ids.length === 3 || ids.length === 6 || ids.length === 9) {
                window.open('<?= BASE_URL ?>index.php?c=qr&a=pdf_seleccion&ids=' + ids.join(','), '_blank');
            }
        }
    });

    // Ejecutar al cargar la vista
    actualizarInterfazSeleccion();
});
</script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>