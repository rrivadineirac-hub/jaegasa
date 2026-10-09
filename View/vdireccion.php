<?php
/**
 * ============================================================
 * vdireccion.php  —  Vista del CRUD Dirección (sin JS)
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Recibe del controlador:
 *   $direcciones -> lista de direcciones
 *   $flash       -> mensaje ['tipo','mensaje'] (opcional)
 *   $buscar      -> término de búsqueda actual
 * ============================================================
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$accionGuardar  = BASE_URL . 'index.php?c=direccion&a=guardar';
$accionEliminar = BASE_URL . 'index.php?c=direccion&a=eliminar';

// ------------------------------------------------------------
// Gestión activa (idéntico a vestudiante.php y vtutor.php)
// ------------------------------------------------------------
$gestionActualAnio = $gestionActual ?? (function () use ($cursos) {
    $anios = array_column($cursos ?? [], 'anio');
    return $anios ? max($anios) : (int) date('Y');
})();

// Cursos de la gestión activa
$cursosGestionActual = array_values(array_filter(
    $cursos ?? [],
    fn($c) => (string) ($c['anio'] ?? '') === (string) $gestionActualAnio
));

// Catálogos únicos de Curso / Nivel / Turno / Paralelo para armar los <select> idéntico a vestudiante.php
$cursosUnicos = [];
$nivelesUnicos = [];
$turnosUnicos = [];
$paralelosUnicos = [];
foreach ($cursosGestionActual as $c) {
    $nomCurso    = $c['cursoNombre'] ?? $c['nombre'] ?? '';
    $nomNivel    = $c['nivelNombre'] ?? $c['nivel'] ?? '';
    $nomTurno    = $c['turnoNombre'] ?? $c['turno'] ?? '';
    $nomParalelo = $c['paraleloNombre'] ?? $c['paralelo'] ?? '';

    if (!empty($nomCurso)) {
        $cursosUnicos[$nomCurso] = $nomCurso;
    }
    if (!empty($c['idNivel'])) {
        $nivelesUnicos[$c['idNivel']] = $nomNivel;
    }
    if (!empty($c['idTurno'])) {
        $turnosUnicos[$c['idTurno']] = $nomTurno;
    }
    if (!empty($c['idParalelo'])) {
        $paralelosUnicos[$c['idParalelo']] = $nomParalelo;
    }
}

// Orden específico para mostrar los grados/cursos (Primero..Sexto)
$ordenGrados = [
    'Primero' => 1,
    'Segundo' => 2,
    'Tercero' => 3,
    'Cuarto'  => 4,
    'Quinto'  => 5,
    'Sexto'   => 6
];
uksort($cursosUnicos, function($a, $b) use ($ordenGrados) {
    $posA = $ordenGrados[$a] ?? 999;
    $posB = $ordenGrados[$b] ?? 999;
    if ($posA === $posB) {
        return strcasecmp($a, $b);
    }
    return $posA <=> $posB;
});
?>

<!-- Datos de cursos de la gestión activa, idéntico a vestudiante.php -->
<script>
    const CURSOS_DATA = <?= json_encode(array_map(fn($c) => [
        'idCurso'     => (int) $c['idCurso'],
        'cursoNombre' => $c['cursoNombre'] ?? $c['nombre'] ?? '',
        'idNivel'     => (int) $c['idNivel'],
        'idTurno'     => (int) $c['idTurno'],
        'idParalelo'  => (int) $c['idParalelo'],
    ], $cursosGestionActual)) ?>;
</script>

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
        <h1 class="page-title"><i class="bi bi-geo-alt-fill"></i> Direcciones y Ubicaciones</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Búsqueda y administración de estudiantes y sus direcciones por curso</p>
    </div>
    <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="bi bi-plus-lg"></i> Nueva dirección
    </button>
</div>

<!-- Tabla -->
<div class="card-panel">
    <form method="get" action="<?= BASE_URL ?>index.php" class="d-flex flex-column gap-2 w-100 mb-3" id="formBusquedaDireccion">
        <input type="hidden" name="c" value="direccion">
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
                        <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualAnio ?? date('Y')) ?>
                    </div>
                </div>
                <?php if (!empty($realizoBusqueda)): ?>
                    <span class="tabla-total m-0"><?= count($estudiantes) ?> ESTUDIANTE(S) ENCONTRADO(S)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- BLOQUE 1: Búsqueda por estudiante -->
        <!-- ================================================================ -->
        <fieldset class="bloque-busqueda mb-2 <?= ($modoBusqueda ?? 'estudiante') === 'curso' ? 'd-none' : '' ?>" id="bloqueEstudiante">
            <h6 class="form-seccion mb-2 fw-semibold text-jaegasa"><i class="bi bi-filter-square"></i> BÚSQUEDA POR ESTUDIANTE</h6>
            <p class="text-muted small mb-3">La búsqueda la puedes realizar por Apellidos Nombres, C.I., Barrio, Calle o Localidad.</p>

            <div class="d-flex align-items-center gap-2">
                <div class="buscador flex-grow-1" style="max-width: 450px;">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" id="inputBuscarEstudiante"
                           value="<?= $e($buscar ?? '') ?>"
                           placeholder="BUSCAR POR ESTUDIANTE, BARRIO, CALLE O LOCALIDAD..." style="width: 100%;" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-sm btn-jaegasa">Buscar</button>
                <a href="<?= BASE_URL ?>index.php?c=direccion" id="btnLimpiarEstudiante"
                   class="btn btn-sm btn-outline-secondary"
                   style="<?= empty($buscar) ? 'display: none;' : '' ?>">Limpiar</a>
                <button type="button" id="btnImprimirEstudiante" class="btn btn-sm btn-success shadow-sm px-3" onclick="imprimirListadoDirecciones()" title="Imprimir listado" style="<?= empty($buscar) ? 'display: none;' : '' ?>">
                    <i class="bi bi-printer-fill me-1"></i> Imprimir
                </button>
            </div>
        </fieldset>

        <!-- ================================================================ -->
        <!-- BLOQUE 2: Búsqueda por curso -->
        <!-- ================================================================ -->
        <fieldset class="bloque-busqueda mb-2 <?= ($modoBusqueda ?? 'estudiante') === 'estudiante' ? 'd-none' : '' ?>" id="bloqueCurso">
            <h6 class="form-seccion mb-2 fw-semibold text-jaegasa"><i class="bi bi-filter-square"></i> BÚSQUEDA POR CURSO</h6>
            <p class="text-muted small mb-3">Seleccione el curso, paralelo, nivel y turno para consultar a los estudiantes y sus direcciones.</p>

            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Curso / Grado</label>
                    <select class="form-select curso-selector" data-tipo="grado" name="grado" <?= empty($cursosUnicos) ? 'disabled' : '' ?>>
                        <option value="">Seleccione...</option>
                        <?php foreach ($cursosUnicos as $id => $nomG): ?>
                            <option value="<?= $e($id) ?>" <?= (isset($_GET['grado']) && (string)$_GET['grado'] === (string)$id) ? 'selected' : '' ?>><?= $e($nomG) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Paralelo</label>
                    <select class="form-select curso-selector" data-tipo="paralelo" name="paralelo" <?= empty($paralelosUnicos) ? 'disabled' : '' ?>>
                        <option value="">Seleccione...</option>
                        <?php foreach ($paralelosUnicos as $idP => $nomP): ?>
                            <option value="<?= (int) $idP ?>" <?= (isset($_GET['paralelo']) && (string)$_GET['paralelo'] === (string)$idP) ? 'selected' : '' ?>><?= $e($nomP) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Nivel</label>
                    <select class="form-select curso-selector" data-tipo="nivel" name="nivel" <?= empty($nivelesUnicos) ? 'disabled' : '' ?>>
                        <option value="">Seleccione...</option>
                        <?php foreach ($nivelesUnicos as $idN => $nomN): ?>
                            <option value="<?= (int) $idN ?>" <?= (isset($_GET['nivel']) && (string)$_GET['nivel'] === (string)$idN) || (count($nivelesUnicos) === 1 && !isset($_GET['nivel'])) ? 'selected' : '' ?>><?= $e($nomN) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Turno</label>
                    <select class="form-select curso-selector" data-tipo="turno" name="turno" <?= empty($turnosUnicos) ? 'disabled' : '' ?>>
                        <option value="">Seleccione...</option>
                        <?php foreach ($turnosUnicos as $idT => $nomT): ?>
                            <option value="<?= (int) $idT ?>" <?= (isset($_GET['turno']) && (string)$_GET['turno'] === (string)$idT) || (count($turnosUnicos) === 1 && !isset($_GET['turno'])) ? 'selected' : '' ?>><?= $e($nomT) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-jaegasa flex-grow-1">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <?php $hayBusquedaCurso = (!empty($_GET['grado']) || !empty($_GET['paralelo']) || !empty($_GET['nivel']) || !empty($_GET['turno'])); ?>
                    <?php if ($hayBusquedaCurso): ?>
                        <a href="<?= BASE_URL ?>index.php?c=direccion&modoBusqueda=curso" id="btnLimpiarCurso"
                           class="btn btn-sm btn-outline-secondary">Limpiar</a>
                        <button type="button" id="btnImprimirCurso" class="btn btn-sm btn-success shadow-sm px-3" onclick="imprimirListadoDirecciones()" title="Imprimir listado">
                            <i class="bi bi-printer-fill me-1"></i> Imprimir
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </fieldset>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaAdmin">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%">N°</th>
                    <th style="width: 35%" class="text-start">Estudiante</th>
                    <th style="width: 25%">Barrio</th>
                    <th style="width: 20%">Calle</th>
                    <th style="width: 8%">N.º</th>
                    <th style="width: 7%">GPS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($estudiantes)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i> No se encontraron estudiantes ni direcciones.
                        </td>
                    </tr>
                <?php else: ?>
                    <tr id="filaNoBusqueda" <?= !empty($realizoBusqueda) ? 'style="display: none;"' : '' ?>>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-search fs-1 d-block text-secondary mb-2 opacity-50"></i>
                            <h6 class="fw-bold text-dark">No se han realizado búsquedas</h6>
                            <p class="small text-muted mb-0">Seleccione un filtro (Curso, Paralelo, Nivel o Turno) o ingrese un nombre para consultar a los estudiantes y sus direcciones.</p>
                        </td>
                    </tr>
                    <?php foreach ($estudiantes as $i => $est): ?>
                        <?php
                            $nombreEstudiante = trim($est['apPaterno'] . ' ' . $est['apMaterno'] . ' ' . $est['nombre']);
                        ?>
                        <tr class="fila-estudiante-item"
                            data-paterno="<?= $e($est['apPaterno'] ?? '') ?>"
                            data-materno="<?= $e($est['apMaterno'] ?? '') ?>"
                            data-nombre="<?= $e($est['nombre'] ?? '') ?>"
                            data-barrio="<?= $e($est['barrio'] ?? '') ?>"
                            data-calle="<?= $e($est['calle'] ?? '') ?>"
                            data-ci="<?= $e($est['ci'] ?? '') ?>"
                            <?= empty($realizoBusqueda) ? 'style="display: none;"' : '' ?>>
                            <td><?= $i + 1 ?></td>
                            <td class="text-start">
                                <span class="fw-bold text-dark"><?= $e($nombreEstudiante) ?></span>
                            </td>
                            <td><?= $e($est['barrio'] ?: '—') ?></td>
                            <td><?= $e($est['calle'] ?: '—') ?></td>
                            <td><?= $e($est['numero'] ?: '—') ?></td>
                            <td><?= $e($est['ubicacion_gps'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// =========================================================
// MODALES POR FILA (Editar, Eliminar) — sin JS
// =========================================================
foreach ($direcciones as $a):
    $idDir = (int) $a['idDireccion'];
    $resumen = trim(($a['calle'] ?? '') . ' ' . ($a['numero'] ?? '') . ', ' . ($a['localidad'] ?? ''), ' ,');
?>

    <!-- ===== Modal EDITAR <?= $idDir ?> ===== -->
    <div class="modal fade" id="modalEditar<?= $idDir ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar dirección</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idDireccion" value="<?= $idDir ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Barrio</label>
                                <input type="text" class="form-control" name="barrio" value="<?= $e($a['barrio']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Localidad</label>
                                <input type="text" class="form-control" name="localidad" value="<?= $e($a['localidad']) ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Calle</label>
                                <input type="text" class="form-control" name="calle" value="<?= $e($a['calle']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Número</label>
                                <input type="text" class="form-control" name="numero" value="<?= $e($a['numero']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Departamento / Estado</label>
                                <input type="text" class="form-control" name="departamento" value="<?= $e($a['departamento']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ubicación GPS</label>
                                <input type="text" class="form-control" name="ubicacionGps" value="<?= $e($a['ubicacion_gps']) ?>" placeholder="lat, lng">
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

    <!-- ===== Modal ELIMINAR <?= $idDir ?> ===== -->
    <div class="modal fade" id="modalEliminar<?= $idDir ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionEliminar ?>" method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Eliminar dirección</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idDireccion" value="<?= $idDir ?>">
                        <p>¿Está seguro de eliminar la dirección <b><?= $e($resumen ?: 'seleccionada') ?></b>?</p>
                        <p class="text-muted mb-0"><small>Esta acción la desactiva del sistema (baja lógica).</small></p>
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
            <form action="<?= $accionGuardar ?>" method="post" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva dirección</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idDireccion" value="0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Barrio</label>
                            <input type="text" class="form-control" name="barrio">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Localidad</label>
                            <input type="text" class="form-control" name="localidad">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Calle</label>
                            <input type="text" class="form-control" name="calle">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Número</label>
                            <input type="text" class="form-control" name="numero">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Departamento / Estado</label>
                            <input type="text" class="form-control" name="departamento">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ubicación GPS</label>
                            <input type="text" class="form-control" name="ubicacionGps" placeholder="lat, lng">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('input[name="modoBusquedaRadio"]');
    const inputModoOculto = document.getElementById('inputModoBusqueda');
    const bloqueEstudiante = document.getElementById('bloqueEstudiante');
    const bloqueCurso = document.getElementById('bloqueCurso');
    const inputEstudiante = document.getElementById('inputBuscarEstudiante');
    const camposCurso = bloqueCurso ? bloqueCurso.querySelectorAll('select, input') : [];

    function actualizarModo() {
        if (!radios.length || !bloqueEstudiante || !bloqueCurso) return;
        const checkedRadio = document.querySelector('input[name="modoBusquedaRadio"]:checked');
        const modo = checkedRadio ? checkedRadio.value : 'estudiante';
        const esEstudiante = modo === 'estudiante';

        if (inputModoOculto) inputModoOculto.value = modo;

        bloqueEstudiante.classList.toggle('d-none', !esEstudiante);
        bloqueCurso.classList.toggle('d-none', esEstudiante);

        if (inputEstudiante) inputEstudiante.disabled = !esEstudiante;
        camposCurso.forEach(campo => { campo.disabled = esEstudiante; });
    }

    radios.forEach(r => r.addEventListener('change', function () {
        const modo = this.value;
        window.location.href = `index.php?c=direccion&modoBusqueda=${modo}`;
    }));
    actualizarModo();

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
                    if (badgeTotal) badgeTotal.textContent = `0 REGISTRO(S) ENCONTRADO(S)`;
                    return;
                } else {
                    filas.forEach(fila => { fila.style.display = ''; visibles++; });
                    if (filaNoBusqueda) filaNoBusqueda.style.display = 'none';
                    const filaMensaje = tbody.querySelector('.fila-sin-resultados');
                    if (filaMensaje) filaMensaje.style.display = 'none';
                    if (badgeTotal) badgeTotal.textContent = `${visibles} ESTUDIANTE(S) ENCONTRADO(S)`;
                    return;
                }
            }

            if (filaNoBusqueda) filaNoBusqueda.style.display = 'none';

            filas.forEach(fila => {
                const paterno     = normalizarTexto(fila.dataset.paterno || '');
                const materno     = normalizarTexto(fila.dataset.materno || '');
                const nombre      = normalizarTexto(fila.dataset.nombre || '');
                const barrio      = normalizarTexto(fila.dataset.barrio || '');
                const calle       = normalizarTexto(fila.dataset.calle || '');
                const ci          = normalizarTexto(fila.dataset.ci || '');

                const fullPaterno = [paterno, materno, nombre].filter(Boolean).join(' ');
                const fullNombre  = [nombre, paterno, materno].filter(Boolean).join(' ');
                const palabras    = (fullPaterno + ' ' + fullNombre + ' ' + barrio + ' ' + calle + ' ' + ci).split(/\s+/).filter(Boolean);

                const coincide = tokens.every(token => {
                    return paterno.startsWith(token) ||
                           materno.startsWith(token) ||
                           nombre.startsWith(token)  ||
                           barrio.startsWith(token)  ||
                           calle.startsWith(token)   ||
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
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-search fs-3 d-block mb-1"></i>
                            No se encontraron direcciones que coincidan con "<strong>${inputBuscar.value}</strong>".
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
        const btnImprimirEstudiante = document.getElementById('btnImprimirEstudiante');

        function actualizarBotonesEstudianteDireccion() {
            if (!inputBuscar) return;
            const tieneTexto = (inputBuscar.value.trim().length > 0);
            if (btnLimpiarEstudiante) btnLimpiarEstudiante.style.display = tieneTexto ? 'inline-block' : 'none';
            if (btnImprimirEstudiante) btnImprimirEstudiante.style.display = tieneTexto ? 'inline-block' : 'none';
        }

        inputBuscar.addEventListener('input', function () {
            actualizarBotonesEstudianteDireccion();
            filtrarTabla();
        });

        actualizarBotonesEstudianteDireccion();
        if (inputBuscar.value.trim() !== '') {
            filtrarTabla();
        }
    }
});

function imprimirListadoDirecciones() {
    const logoSrc = window.schoolLogoBase64 || '';
    
    let filtroTexto = 'LISTADO DE DIRECCIONES Y UBICACIONES DE ESTUDIANTES';
    const inputQ = document.getElementById('inputBuscarEstudiante');
    if (inputQ && inputQ.value.trim() !== '') {
        filtroTexto = 'FILTRO DE BÚSQUEDA: "' + inputQ.value.trim().toUpperCase() + '"';
    } else {
        const modoCursoActive = document.getElementById('modoCurso') && document.getElementById('modoCurso').checked;
        if (modoCursoActive) {
            const selG = document.querySelector('select[name="grado"]');
            const selP = document.querySelector('select[name="paralelo"]');
            const selN = document.querySelector('select[name="nivel"]');
            const selT = document.querySelector('select[name="turno"]');
            let partes = [];
            if (selG && selG.value) partes.push(selG.value.toUpperCase());
            if (selP && selP.options[selP.selectedIndex] && selP.options[selP.selectedIndex].text !== 'Seleccione...') partes.push('PARALELO ' + selP.options[selP.selectedIndex].text.toUpperCase());
            if (selN && selN.options[selN.selectedIndex] && selN.options[selN.selectedIndex].text !== 'Seleccione...') partes.push('NIVEL ' + selN.options[selN.selectedIndex].text.toUpperCase());
            if (selT && selT.options[selT.selectedIndex] && selT.options[selT.selectedIndex].text !== 'Seleccione...') partes.push('TURNO ' + selT.options[selT.selectedIndex].text.toUpperCase());
            if (partes.length > 0) {
                filtroTexto = 'CURSO: ' + partes.join(' - ');
            }
        }
    }

    const fechaHoy = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    const horaHoy = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

    const tbody = document.querySelector('#tablaAdmin tbody');
    let filasAImprimir = [];
    if (tbody) {
        const trs = tbody.querySelectorAll('tr');
        let contador = 1;
        trs.forEach(tr => {
            if (tr.id !== 'filaNoBusqueda' && tr.style.display !== 'none' && !tr.classList.contains('fila-sin-resultados') && !tr.querySelector('td[colspan]')) {
                const tds = tr.querySelectorAll('td');
                if (tds.length >= 5) {
                    filasAImprimir.push({
                        num: contador++,
                        estudiante: tds[1].textContent.trim(),
                        barrio: tds[2].textContent.trim(),
                        calle: tds[3].textContent.trim(),
                        numero: tds[4].textContent.trim(),
                        gps: tds[5] ? tds[5].textContent.trim() : '—'
                    });
                }
            }
        });
    }

    if (filasAImprimir.length === 0) {
        alert('No hay registros visibles en la lista para imprimir.');
        return;
    }

    let rowsHtml = '';
    filasAImprimir.forEach(item => {
        rowsHtml += `
            <tr>
                <td style="text-align: center; font-weight: bold;">${item.num}</td>
                <td style="font-weight: 600; text-align: left;">${item.estudiante}</td>
                <td style="text-align: left;">${item.barrio}</td>
                <td style="text-align: left;">${item.calle}</td>
                <td style="text-align: center;">${item.numero}</td>
                <td style="text-align: center;">${item.gps}</td>
            </tr>
        `;
    });

    const htmlContent = `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Listado de Direcciones de Estudiantes - U.E. Prof. Jaime Alfonso Escalante Gutiérrez "A"</title>
            <style>
                @page { margin: 5mm 6mm; size: portrait; }
                body { font-family: 'Segoe UI', Arial, sans-serif; padding: 6px; color: #1e293b; background: #fff; font-size: 8.5px; }
                
                .header-container { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #475569; padding-bottom: 4px; margin-bottom: 6px; }
                .logo-img { height: 42px; width: auto; object-fit: contain; }
                .header-title { text-align: center; flex-grow: 1; padding: 0 8px; }
                .header-title h5 { font-size: 8px; margin: 0; font-weight: 900; color: #334155; letter-spacing: 0.5px; }
                .header-title h3 { font-size: 12px; margin: 1px 0; font-weight: 900; color: #000000; text-transform: uppercase; letter-spacing: 0.5px; }
                .header-title p { font-size: 8.5px; margin: 2px 0 0 0; font-weight: 900; color: #ffffff; background: #334155; padding: 2px 8px; border-radius: 3px; display: inline-block; border: 1px solid #1e293b; text-transform: uppercase; }
                .header-info { text-align: right; font-size: 8px; color: #1e293b; line-height: 1.25; min-width: 100px; font-weight: 700; }

                .tabla-listado { width: 100%; border-collapse: collapse; margin-top: 4px; page-break-inside: avoid; }
                .tabla-listado tr { page-break-inside: avoid; page-break-after: auto; }
                .tabla-listado th, .tabla-listado td { border: 1px solid #475569; padding: 3px 5px; font-size: 8.5px; }
                .tabla-listado th { background-color: #334155; color: #ffffff; font-weight: 900; text-transform: uppercase; font-size: 8.5px; text-align: center; letter-spacing: 0.5px; border: 1px solid #1e293b; }
                .tabla-listado tr:nth-child(even) { background-color: #f1f5f9; }

                .resumen-footer { margin-top: 6px; display: flex; justify-content: space-between; font-weight: 700; font-size: 8.5px; color: #334155; }

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
                        <th style="width: 5%;">N°</th>
                        <th style="width: 35%;">ESTUDIANTE</th>
                        <th style="width: 25%;">BARRIO / ZONA</th>
                        <th style="width: 20%;">CALLE / AVENIDA</th>
                        <th style="width: 8%;">N.º</th>
                        <th style="width: 7%;">GPS</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>

            <div class="resumen-footer">
                <span>TOTAL ESTUDIANTES: ${filasAImprimir.length}</span>
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
        let iframe = document.getElementById('iframeImpresionDirecciones');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'iframeImpresionDirecciones';
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
