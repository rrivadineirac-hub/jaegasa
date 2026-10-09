<?php
/**
 * ============================================================
 * vtutor.php  —  Vista del CRUD Tutor (sin JS)
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Recibe del controlador:
 *   $tutores  -> lista de tutores
 *   $flash    -> mensaje ['tipo','mensaje'] (opcional)
 *   $buscar   -> término de búsqueda actual
 * ============================================================
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$accionGuardar  = BASE_URL . 'index.php?c=tutor&a=guardar';
$accionEliminar = BASE_URL . 'index.php?c=tutor&a=eliminar';
$modoBusqueda = $_GET['modoBusqueda'] ?? (!empty($_GET['grado']) || !empty($_GET['paralelo']) || !empty($_GET['nivel']) || !empty($_GET['turno']) ? 'curso' : 'estudiante');

// ------------------------------------------------------------
// Gestión activa (idéntico a vestudiante.php)
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
        <h1 class="page-title"><i class="bi bi-person-heart"></i> Tutores y Apoderados</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Búsqueda y administración de estudiantes y sus tutores por curso</p>
    </div>
    <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="bi bi-plus-lg"></i> Nuevo tutor
    </button>
</div>

<!-- Tabla -->
<div class="card-panel">
    <div class="tabla-toolbar">
        <form method="get" action="<?= BASE_URL ?>index.php" class="d-flex flex-column gap-2 w-100" id="formBusquedaTutor">
            <input type="hidden" name="c" value="tutor">
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
                            <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualAnio ?? $gestionActual ?? date('Y')) ?>
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

                <div class="d-flex align-items-center gap-2">
                    <div class="buscador flex-grow-1" style="max-width: 450px;">
                        <i class="bi bi-search"></i>
                        <input type="text" name="q" id="inputBuscarEstudiante"
                               value="<?= $e($buscar ?? '') ?>"
                               placeholder="BUSCAR ESTUDIANTE POR NOMBRE, CI O RUDE..." style="width: 100%;" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-sm btn-jaegasa">Buscar</button>
                    <a href="<?= BASE_URL ?>index.php?c=tutor" id="btnLimpiarEstudiante"
                       class="btn btn-sm btn-outline-secondary" style="<?= empty($buscar) ? 'display: none;' : '' ?>">Limpiar</a>
                    <button type="button" id="btnImprimirEstudiante" class="btn btn-sm btn-success shadow-sm px-3" onclick="imprimirListadoTutores()" title="Imprimir listado" style="<?= empty($buscar) ? 'display: none;' : '' ?>">
                        <i class="bi bi-printer-fill me-1"></i> Imprimir
                    </button>
                </div>
            </fieldset>

            <!-- ================================================================ -->
            <!-- BLOQUE 2: Búsqueda por curso -->
            <!-- ================================================================ -->
            <fieldset class="bloque-busqueda mb-2 <?= ($modoBusqueda ?? 'estudiante') === 'estudiante' ? 'd-none' : '' ?>" id="bloqueCurso">
                <h6 class="form-seccion mb-2 fw-semibold text-jaegasa"><i class="bi bi-filter-square"></i> BÚSQUEDA POR CURSO</h6>
                <p class="text-muted small mb-3">Seleccione el curso, paralelo, nivel y turno para filtrar los estudiantes y sus tutores.</p>

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

                    <div class="col-md-3">
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

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-jaegasa flex-grow-1">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                        <?php $hayBusquedaCurso = (!empty($_GET['grado']) || !empty($_GET['paralelo']) || !empty($_GET['nivel']) || !empty($_GET['turno'])); ?>
                        <?php if ($hayBusquedaCurso): ?>
                            <a href="<?= BASE_URL ?>index.php?c=tutor&modoBusqueda=curso" id="btnLimpiarCurso"
                               class="btn btn-sm btn-outline-secondary">Limpiar</a>
                            <button type="button" id="btnImprimirCurso" class="btn btn-sm btn-success shadow-sm px-3" onclick="imprimirListadoTutores()" title="Imprimir listado">
                                <i class="bi bi-printer-fill me-1"></i> Imprimir
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </fieldset>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tablaAdmin">
            <thead class="table-light">
                <tr>
                    <th style="width: 3%">N°</th>
                    <th style="width: 15%" class="text-start">Estudiante</th>
                    <th style="width: 7%">CI Est.</th>
                    <th style="width: 12%">Papá</th>
                    <th style="width: 7%">Tel. Papá</th>
                    <th style="width: 7%">CI Papá</th>
                    <th style="width: 12%">Mamá</th>
                    <th style="width: 7%">Tel. Mamá</th>
                    <th style="width: 7%">CI Mamá</th>
                    <th style="width: 10%">Tutor</th>
                    <th style="width: 6%">Parentesco</th>
                    <th style="width: 7%">Tel. Tutor</th>
                    <th style="width: 7%">CI Tutor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($realizoBusqueda)): ?>
                    <tr>
                        <td colspan="13" class="text-center text-muted py-5">
                            <i class="bi bi-search fs-1 d-block text-secondary mb-2 opacity-50"></i>
                            <h6 class="fw-bold text-dark">No se han realizado búsquedas</h6>
                            <p class="small text-muted mb-0">Seleccione un filtro (Curso, Paralelo, Nivel o Turno) o ingrese un nombre para consultar a los estudiantes y sus tutores.</p>
                        </td>
                    </tr>
                <?php elseif (empty($estudiantes)): ?>
                    <tr>
                        <td colspan="13" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i> No se encontraron estudiantes ni tutores con los filtros seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($estudiantes as $i => $est): ?>
                        <?php
                            $nombreEstudiante = trim($est['apPaterno'] . ' ' . $est['apMaterno'] . ' ' . $est['nombre']);
                        ?>
                        <tr data-paterno="<?= $e($est['apPaterno'] ?? '') ?>"
                            data-materno="<?= $e($est['apMaterno'] ?? '') ?>"
                            data-nombre="<?= $e($est['nombre'] ?? '') ?>"
                            data-rude="<?= $e($est['rude'] ?? '') ?>"
                            data-ci="<?= $e($est['ci'] ?? '') ?>"
                            data-padre="<?= $e($est['padreNombre'] ?? '') ?>"
                            data-madre="<?= $e($est['madreNombre'] ?? '') ?>"
                            data-tutor="<?= $e($est['tutorNombre'] ?? '') ?>">
                            <td><?= $i + 1 ?></td>
                            <td class="text-start">
                                <span class="fw-bold text-dark"><?= $e($nombreEstudiante) ?></span>
                            </td>
                            <td>
                                <?php if (!empty(trim($est['ci'] ?? ''))): ?>
                                    <span class="badge bg-light text-dark border"><?= $e(trim($est['ci'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty(trim($est['padreNombre'] ?? ''))): ?>
                                    <span class="fw-semibold text-dark"><?= $e(trim($est['padreNombre'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty(trim($est['padreTelefono'] ?? ''))): ?>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-telephone-fill text-success me-1"></i><?= $e(trim($est['padreTelefono'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty(trim($est['padreCi'] ?? ''))): ?>
                                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle"><?= $e(trim($est['padreCi'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty(trim($est['madreNombre'] ?? ''))): ?>
                                    <span class="fw-semibold text-dark"><?= $e(trim($est['madreNombre'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty(trim($est['madreTelefono'] ?? ''))): ?>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-telephone-fill text-success me-1"></i><?= $e(trim($est['madreTelefono'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty(trim($est['madreCi'] ?? ''))): ?>
                                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle"><?= $e(trim($est['madreCi'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty(trim($est['tutorNombre'] ?? ''))): ?>
                                    <span class="fw-semibold text-dark"><?= $e(trim($est['tutorNombre'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty(trim($est['tutorParentesco'] ?? ''))): ?>
                                    <span class="badge bg-info-subtle text-dark border small" style="font-size: 0.75rem;"><?= $e(trim($est['tutorParentesco'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty(trim($est['tutorTelefono'] ?? ''))): ?>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-telephone-fill text-success me-1"></i><?= $e(trim($est['tutorTelefono'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty(trim($est['tutorCi'] ?? ''))): ?>
                                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle"><?= $e(trim($est['tutorCi'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
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
foreach ($tutores as $a):
    $idTut = (int) $a['idTutor'];
    $nombreCompleto = trim($a['apPaterno'] . ' ' . $a['apMaterno'] . ' ' . $a['nombre']);
?>

    <!-- ===== Modal VER <?= $idTut ?> ===== -->
    <div class="modal fade" id="modalVer<?= $idTut ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-vcard"></i> Detalle del tutor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <h6 class="form-seccion"><?= $e($nombreCompleto) ?></h6>
                    <div class="detalle-grid">
                        <div class="detalle-item"><span>CI</span><b><?= $e($a['ci']) ?></b></div>
                        <div class="detalle-item"><span>Teléfono</span><b><?= $e($a['telefono'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Correo</span><b><?= $e($a['correo'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Parentesco</span><b><?= $e($a['parentesco'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Tipo</span><b><?= $e($a['tipo'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Profesión</span><b><?= $e($a['profesion'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Ocupación</span><b><?= $e($a['ocupacion'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Usuario</span><b><?= $e($a['usuario'] ?: '—') ?></b></div>
                        <div class="detalle-item detalle-ancho"><span>Dirección</span><b><?= $e($a['direccion'] ?: '—') ?></b></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-jaegasa" data-bs-dismiss="modal"
                            data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idTut ?>">
                        <i class="bi bi-pencil-fill"></i> Editar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Modal EDITAR <?= $idTut ?> ===== -->
    <div class="modal fade" id="modalEditar<?= $idTut ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar tutor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idTutor" value="<?= $idTut ?>">

                        <h6 class="form-seccion">Datos personales</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">CI *</label>
                                <input type="text" class="form-control" name="ci" value="<?= $e($a['ci']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Apellido paterno *</label>
                                <input type="text" class="form-control" name="apPaterno" value="<?= $e($a['apPaterno']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Apellido materno *</label>
                                <input type="text" class="form-control" name="apMaterno" value="<?= $e($a['apMaterno']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nombre(s) *</label>
                                <input type="text" class="form-control" name="nombre" value="<?= $e($a['nombre']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" value="<?= $e($a['telefono']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Correo</label>
                                <input type="email" class="form-control" name="correo" value="<?= $e($a['correo']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Parentesco</label>
                                <input type="text" class="form-control" name="parentesco" value="<?= $e($a['parentesco']) ?>" placeholder="Padre / Madre...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo</label>
                                <input type="text" class="form-control" name="tipo" value="<?= $e($a['tipo']) ?>" placeholder="Apoderado...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Profesión</label>
                                <input type="text" class="form-control" name="profesion" value="<?= $e($a['profesion']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ocupación</label>
                                <input type="text" class="form-control" name="ocupacion" value="<?= $e($a['ocupacion']) ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="direccion" value="<?= $e($a['direccion']) ?>">
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

    <!-- ===== Modal ELIMINAR <?= $idTut ?> ===== -->
    <div class="modal fade" id="modalEliminar<?= $idTut ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionEliminar ?>" method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Eliminar tutor</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idTutor" value="<?= $idTut ?>">
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
            <form action="<?= $accionGuardar ?>" method="post" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo tutor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idTutor" value="0">

                    <h6 class="form-seccion">Datos personales</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">CI *</label>
                            <input type="text" class="form-control" name="ci" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Apellido paterno *</label>
                            <input type="text" class="form-control" name="apPaterno" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Apellido materno *</label>
                            <input type="text" class="form-control" name="apMaterno" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre(s) *</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Correo</label>
                            <input type="email" class="form-control" name="correo">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Parentesco</label>
                            <input type="text" class="form-control" name="parentesco" placeholder="Padre / Madre...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo</label>
                            <input type="text" class="form-control" name="tipo" placeholder="Apoderado...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Profesión</label>
                            <input type="text" class="form-control" name="profesion">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ocupación</label>
                            <input type="text" class="form-control" name="ocupacion">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dirección</label>
                            <input type="text" class="form-control" name="direccion">
                        </div>
                    </div>

                    <h6 class="form-seccion mt-4">Acceso al sistema <small class="text-muted">(opcional)</small></h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Usuario</label>
                            <input type="text" class="form-control" name="usuario">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contraseña</label>
                            <input type="password" class="form-control" name="password">
                            <small class="text-muted d-block mt-1">Requisitos: mín. 8 caracteres, 1 mayúscula, 1 número y 1 símbolo.</small>
                        </div>
                    </div>
                    <small class="text-muted">Complete usuario y contraseña solo si el tutor tendrá acceso al sistema.</small>
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

<script src="<?= BASE_URL ?>Js/tutor_autocompletar.js?v=<?= @filemtime(BASE_PATH . '/Js/tutor_autocompletar.js') ?>"></script>
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

        function filtrarTabla() {
            if (!tbody) return;
            const query   = normalizarTexto(inputBuscar.value);
            const tokens  = query.split(/\s+/).filter(t => t.length > 0);
            const filas   = tbody.querySelectorAll('tr:not(.fila-sin-resultados)');
            let visibles  = 0;

            filas.forEach(fila => {
                if (fila.querySelector('td[colspan]')) {
                    const text = fila.textContent.toLowerCase();
                    if (text.includes('no se encontraron') || text.includes('no se han realizado')) return;
                }

                if (tokens.length === 0) {
                    fila.style.display = '';
                    visibles++;
                    return;
                }

                const paterno     = normalizarTexto(fila.dataset.paterno || '');
                const materno     = normalizarTexto(fila.dataset.materno || '');
                const nombre      = normalizarTexto(fila.dataset.nombre || '');
                const rude        = normalizarTexto(fila.dataset.rude || '');
                const ci          = normalizarTexto(fila.dataset.ci || '');
                const padre       = normalizarTexto(fila.dataset.padre || '');
                const madre       = normalizarTexto(fila.dataset.madre || '');
                const tutor       = normalizarTexto(fila.dataset.tutor || '');

                const fullPaterno = [paterno, materno, nombre].filter(Boolean).join(' ');
                const fullNombre  = [nombre, paterno, materno].filter(Boolean).join(' ');
                const palabras    = (fullPaterno + ' ' + fullNombre + ' ' + ci + ' ' + padre + ' ' + madre + ' ' + tutor).split(/\s+/).filter(Boolean);

                const coincide = tokens.every(token => {
                    const incluirRude = rude && (token.length >= 3 || /[0-9]/.test(token));
                    return paterno.startsWith(token) ||
                           materno.startsWith(token) ||
                           nombre.startsWith(token)  ||
                           ci.startsWith(token)      ||
                           (incluirRude && rude.startsWith(token)) ||
                           padre.startsWith(token)   ||
                           madre.startsWith(token)   ||
                           tutor.startsWith(token)   ||
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
                            No se encontraron registros que coincidan con "<strong>${inputBuscar.value}</strong>".
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
                badgeTotal.textContent = `${visibles} REGISTRO(S) ENCONTRADO(S)`;
            }
        }

        const btnLimpiarEstudiante = document.getElementById('btnLimpiarEstudiante');
        const btnImprimirEstudiante = document.getElementById('btnImprimirEstudiante');

        function actualizarBotonesEstudianteTutor() {
            if (!inputBuscar) return;
            const tieneTexto = (inputBuscar.value.trim().length > 0);
            if (btnLimpiarEstudiante) btnLimpiarEstudiante.style.display = tieneTexto ? 'inline-block' : 'none';
            if (btnImprimirEstudiante) btnImprimirEstudiante.style.display = tieneTexto ? 'inline-block' : 'none';
        }

        inputBuscar.addEventListener('input', function () {
            actualizarBotonesEstudianteTutor();
            filtrarTabla();
        });

        actualizarBotonesEstudianteTutor();
        if (inputBuscar && inputBuscar.value.trim() !== '') {
            filtrarTabla();
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
});

function imprimirListadoTutores() {
    const logoSrc = window.schoolLogoBase64 || '';
    
    let filtroTexto = 'LISTADO DE TUTORES Y APODERADOS';
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
            if (tr.style.display !== 'none' && !tr.classList.contains('fila-sin-resultados') && !tr.querySelector('td[colspan]')) {
                const tds = tr.querySelectorAll('td');
                if (tds.length >= 13) {
                    filasAImprimir.push({
                        num: contador++,
                        estudiante: tds[1].textContent.trim(),
                        ciEst: tds[2].textContent.trim(),
                        papa: tds[3].textContent.trim(),
                        telPapa: tds[4].textContent.trim(),
                        ciPapa: tds[5].textContent.trim(),
                        mama: tds[6].textContent.trim(),
                        telMama: tds[7].textContent.trim(),
                        ciMama: tds[8].textContent.trim(),
                        tutor: tds[9].textContent.trim(),
                        parentesco: tds[10].textContent.trim(),
                        telTutor: tds[11].textContent.trim(),
                        ciTutor: tds[12].textContent.trim()
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
                <td style="text-align: center;">${item.ciEst}</td>
                <td style="text-align: left;">${item.papa}</td>
                <td style="text-align: center;">${item.telPapa}</td>
                <td style="text-align: center;">${item.ciPapa}</td>
                <td style="text-align: left;">${item.mama}</td>
                <td style="text-align: center;">${item.telMama}</td>
                <td style="text-align: center;">${item.ciMama}</td>
                <td style="text-align: left;">${item.tutor}</td>
                <td style="text-align: center;">${item.parentesco}</td>
                <td style="text-align: center;">${item.telTutor}</td>
                <td style="text-align: center;">${item.ciTutor}</td>
            </tr>
        `;
    });

    const htmlContent = `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Listado de Tutores y Apoderados - U.E. Prof. Jaime Alfonso Escalante Gutiérrez "A"</title>
            <style>
                @page { margin: 3mm 4mm; size: A4 landscape; }
                * { box-sizing: border-box; }
                html, body { width: 100%; height: 100%; margin: 0; padding: 2px; font-family: 'Segoe UI', Arial, sans-serif; color: #0f172a; background: #fff; font-size: 7pt; line-height: 1.1; }
                
                .header-container { display: flex; align-items: center; justify-content: space-between; border-bottom: 1.5px solid #334155; padding-bottom: 2px; margin-bottom: 3px; }
                .logo-img { height: 30px; width: auto; object-fit: contain; }
                .header-title { text-align: center; flex-grow: 1; padding: 0 6px; }
                .header-title h5 { font-size: 7pt; margin: 0; font-weight: 800; color: #334155; letter-spacing: 0.3px; }
                .header-title h3 { font-size: 9.5pt; margin: 1px 0; font-weight: 900; color: #000000; text-transform: uppercase; letter-spacing: 0.3px; }
                .header-title p { font-size: 7pt; margin: 1px 0 0 0; font-weight: 800; color: #ffffff; background: #334155; padding: 1px 6px; border-radius: 2px; display: inline-block; text-transform: uppercase; }
                .header-info { text-align: right; font-size: 6.8pt; color: #0f172a; line-height: 1.15; min-width: 90px; font-weight: 700; }

                .tabla-listado { width: 100%; border-collapse: collapse; margin-top: 2px; table-layout: fixed; }
                .tabla-listado th, .tabla-listado td { border: 1px solid #475569; padding: 1.2px 2px; font-size: 6.8pt; line-height: 1.05; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                .tabla-listado th { background-color: #334155 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; color: #ffffff !important; font-weight: 900; text-transform: uppercase; text-align: center; letter-spacing: 0.2px; }
                .tabla-listado tr:nth-child(even) { background-color: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

                .resumen-footer { margin-top: 3px; display: flex; justify-content: space-between; font-weight: 700; font-size: 6.8pt; color: #334155; }

                @media print {
                    @page { size: A4 landscape; margin: 3mm 4mm; }
                    body { padding: 0; margin: 0; }
                    .tabla-listado { page-break-inside: auto; }
                    .tabla-listado tr { page-break-inside: avoid; page-break-after: auto; }
                }
            </style>
        </head>
        <body>
            <div class="header-container">
                <div>
                    ${logoSrc ? `<img src="${logoSrc}" class="logo-img" alt="Logo Colegio">` : '<div style="font-weight:bold; color:#475569; font-size:11px;">ESCALANTE "A"</div>'}
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
                        <th style="width: 3%;">N°</th>
                        <th style="width: 16%;">ESTUDIANTE</th>
                        <th style="width: 7%;">CI EST.</th>
                        <th style="width: 12%;">PAPÁ</th>
                        <th style="width: 7%;">TEL. PAPÁ</th>
                        <th style="width: 7%;">CI PAPÁ</th>
                        <th style="width: 12%;">MAMÁ</th>
                        <th style="width: 7%;">TEL. MAMÁ</th>
                        <th style="width: 7%;">CI MAMÁ</th>
                        <th style="width: 10%;">TUTOR</th>
                        <th style="width: 6%;">PARENTESCO</th>
                        <th style="width: 7%;">TEL. TUTOR</th>
                        <th style="width: 7%;">CI TUTOR</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>

            <div class="resumen-footer">
                <span>TOTAL ESTUDIANTES / TUTORES: ${filasAImprimir.length}</span>
                <span>SANTA CRUZ - BOLIVIA</span>
            </div>
        </body>
        </html>
    `;

    const printWin = window.open('', '_blank', 'width=1150,height=800');
    if (printWin) {
        printWin.document.write(htmlContent);
        printWin.document.close();
        printWin.focus();
        setTimeout(() => { printWin.print(); }, 400);
    } else {
        let iframe = document.getElementById('iframeImpresionTutores');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'iframeImpresionTutores';
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
