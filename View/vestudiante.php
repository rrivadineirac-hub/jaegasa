<?php
/**
 * ============================================================
 * vestudiante.php  —  Vista del CRUD Estudiante (sin JS externo)
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Recibe del controlador:
 *   $estudiantes -> lista de estudiantes
 *   $flash       -> mensaje ['tipo','mensaje'] (opcional)
 *   $buscar      -> término de búsqueda actual
 *   $cursos      -> catálogo de cursos con, además de idCurso/descripcion:
 *                   idNivel, nivelNombre, idTurno, turnoNombre,
 *                   idParalelo, paraleloNombre, anio
 *   $gestionActual -> (opcional) año de la gestión activa. Si no llega,
 *                      se calcula como el año más alto presente en $cursos.
 * ============================================================
 */

require_once BASE_PATH . '/View/header.php';
require_once BASE_PATH . '/partes/nacionalidades.php';
require_once BASE_PATH . '/partes/bolivia.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$genero = fn($g) => $g === 'M' ? 'Masculino' : ($g === 'F' ? 'Femenino' : '—');
$fotoDefault = BASE_URL . 'inagenes/Estudiante/default.png';
$rutaFoto = fn($foto) => (!empty($foto) && $foto !== 'default.png')
    ? BASE_URL . 'inagenes/Estudiante/' . rawurlencode($foto)
    : $fotoDefault;

$accionGuardar  = BASE_URL . 'index.php?c=estudiante&a=guardar';
$accionEliminar = BASE_URL . 'index.php?c=estudiante&a=eliminar';

$modoBusqueda = $_GET['modoBusqueda'] ?? (!empty($_GET['grado']) || !empty($_GET['paralelo']) || !empty($_GET['nivel']) || !empty($_GET['turno']) ? 'curso' : 'estudiante');
$busquedaRealizada = (!empty($_GET['q']) || !empty($_GET['grado']) || !empty($_GET['paralelo']) || !empty($_GET['nivel']) || !empty($_GET['turno']));

// ------------------------------------------------------------
// Gestión activa (no se selecciona, se fija automáticamente)
// ------------------------------------------------------------
$gestionActualAnio = $gestionActual ?? (function () use ($cursos) {
    $anios = array_column($cursos, 'anio');
    return $anios ? max($anios) : (int) date('Y');
})();

// Solo se puede inscribir dentro de la gestión activa
$cursosGestionActual = array_values(array_filter(
    $cursos,
    fn($c) => (string) ($c['anio'] ?? '') === (string) $gestionActualAnio
));

// Catálogos únicos de Curso / Nivel / Turno / Paralelo para armar los <select>
$cursosUnicos = [];
$nivelesUnicos = [];
$turnosUnicos = [];
$paralelosUnicos = [];
foreach ($cursosGestionActual as $c) {
    if (!empty($c['cursoNombre'])) {
        $cursosUnicos[$c['cursoNombre']] = $c['cursoNombre'];
    }
    $nivelesUnicos[$c['idNivel']] = $c['nivelNombre'];
    $turnosUnicos[$c['idTurno']] = $c['turnoNombre'];
    $paralelosUnicos[$c['idParalelo']] = $c['paraleloNombre'];
}
// Orden específico para mostrar los grados/cursos
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
$cursoUnicos = $cursosUnicos;
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
    const CURSOS_DATA = <?= json_encode(array_map(fn($c) => [
        'idCurso'     => (int) $c['idCurso'],
        'cursoNombre' => $c['cursoNombre'] ?? '',
        'idNivel'     => (int) $c['idNivel'],
        'idTurno'     => (int) $c['idTurno'],
        'idParalelo'  => (int) $c['idParalelo'],
        'cupo'        => (int) ($c['cupo'] ?? 36),
        'inscritos'   => (int) ($c['inscritos'] ?? 0),
    ], $cursosGestionActual)) ?>;
</script>

<!-- Encabezado -->
<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-mortarboard-fill"></i> Estudiantes</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Administración de alumnos inscritos</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-jaegasa" data-bs-toggle="modal" data-bs-target="#modalNuevo">
            <i class="bi bi-plus-lg"></i> Nuevo estudiante
        </button>
    </div>
</div>



<?php if (empty($cursosGestionActual)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill"></i>
        No hay cursos registrados para la gestión <?= $e($gestionActualAnio) ?>. Para inscribir un nuevo estudiante primero debe crear al menos un curso en esta gestión.
    </div>
<?php endif; ?>

<!-- Tabla -->
<div class="card-panel">

    <div class="tabla-toolbar">
        <form method="get" action="<?= BASE_URL ?>index.php" class="d-flex flex-column gap-2 w-100" id="formBusquedaEstudiante">
            <input type="hidden" name="c" value="estudiante">
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
                    <span class="tabla-total m-0"><?= $busquedaRealizada ? count($estudiantes) . ' ESTUDIANTE(S) ENCONTRADO(S)' : 'SIN BÚSQUEDA REALIZADA' ?></span>
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
                    <a href="<?= BASE_URL ?>index.php?c=estudiante" id="btnLimpiarEstudiante"
                       class="btn btn-sm btn-outline-secondary" style="<?= empty($buscar) ? 'display: none;' : '' ?>">Limpiar</a>
                    <button type="button" id="btnImprimirEstudiante" class="btn btn-sm btn-success shadow-sm px-3" onclick="imprimirListadoEstudiantes()" title="Imprimir listado" style="<?= empty($buscar) ? 'display: none;' : '' ?>">
                        <i class="bi bi-printer-fill me-1"></i> Imprimir
                    </button>
                </div>
            </fieldset>

            <!-- ================================================================ -->
            <!-- BLOQUE 2: Búsqueda por curso -->
            <!-- ================================================================ -->
            <fieldset class="bloque-busqueda mb-2 <?= ($modoBusqueda ?? 'estudiante') === 'estudiante' ? 'd-none' : '' ?>" id="bloqueCurso">
                <h6 class="form-seccion mb-2 fw-semibold text-jaegasa"><i class="bi bi-filter-square"></i> BÚSQUEDA POR CURSO</h6>
                <p class="text-muted small mb-3">Seleccione el curso, paralelo, nivel y turno para filtrar los estudiantes del curso.</p>

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
                        <?php $hayBusquedaCurso = (!empty($_GET['grado']) || !empty($_GET['paralelo']) || !empty($_GET['nivel']) || !empty($_GET['turno']) || !empty($_GET['idCurso'])); ?>
                        <?php if ($hayBusquedaCurso): ?>
                            <a href="<?= BASE_URL ?>index.php?c=estudiante&modoBusqueda=curso" id="btnLimpiarCurso"
                               class="btn btn-sm btn-outline-secondary">Limpiar</a>
                            <button type="button" id="btnImprimirCurso" class="btn btn-sm btn-success shadow-sm px-3" onclick="imprimirListadoEstudiantes()" title="Imprimir listado">
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
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Foto</th>
                    <th>Nombre completo</th>
                    <th>RUDE</th>
                    <th>CI</th>
                    <th>Curso</th>
                    <th>Paralelo</th>
                    <th>Nivel</th>
                    <th>Turno</th>
                    <th>Tutor</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$busquedaRealizada): ?>
                    <tr>
                        <td colspan="11" class="text-center py-5">
                            <div class="py-4">
                                <div class="mb-3">
                                    <span class="d-inline-flex align-items-center justify-content-center bg-light text-secondary rounded-circle p-3 shadow-sm border" style="width: 76px; height: 76px;">
                                        <i class="bi bi-search fs-1 opacity-50"></i>
                                    </span>
                                </div>
                                <h4 class="fw-bold text-dark mb-1">No se han realizado búsquedas</h4>
                                <p class="text-muted small m-0">Seleccione un filtro (Curso, Paralelo, Nivel o Turno) o ingrese un nombre para consultar a los estudiantes inscritos.</p>
                            </div>
                        </td>
                    </tr>
                <?php elseif (empty($estudiantes)): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i> No se encontraron estudiantes que coincidan con la búsqueda.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($estudiantes as $i => $a): ?>
                        <?php
                            $idEst = (int) $a['idEstudiante'];
                            $nombreCompleto = trim($a['apPaterno'] . ' ' . $a['apMaterno'] . ' ' . $a['nombre']);
                        ?>
                        <tr data-paterno="<?= $e($a['apPaterno']) ?>"
                            data-materno="<?= $e($a['apMaterno']) ?>"
                            data-nombre="<?= $e($a['nombre']) ?>"
                            data-rude="<?= $e($a['rude']) ?>"
                            data-ci="<?= $e($a['ci']) ?>">
                            <td><?= $i + 1 ?></td>
                            <td>
                                <img src="<?= $rutaFoto($a['foto']) ?>" alt="Foto"
                                     class="tabla-foto"
                                     onerror="this.src='<?= $fotoDefault ?>'">
                            </td>
                            <td class="fw-semibold"><?= $e($nombreCompleto) ?></td>
                            <td><?= $e($a['rude'] ?: '—') ?></td>
                            <td><?= $e($a['ci'] ?: '—') ?></td>
                            <td>
                                <?php if (!empty($a['cursoNivel'])): ?>
                                    <span class="badge-cat"><?= $e(!empty($a['cursoNombre']) ? $a['cursoNombre'] : $a['cursoNivel']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Sin inscribir</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $e($a['cursoParalelo'] ?: '—') ?></td>
                            <td><?= $e($a['cursoNivel'] ?: '—') ?></td>
                            <td><?= $e($a['cursoTurno'] ?: '—') ?></td>
                            <td><?= $e($a['tutor'] ?: '—') ?></td>
                            <td class="text-end text-nowrap">
                                <div class="acciones-tabla">
                                    <button class="btn-accion btn-ver" title="Mostrar completo"
                                            data-bs-toggle="modal" data-bs-target="#modalVer<?= $idEst ?>">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                    <button class="btn-accion btn-editar" title="Editar"
                                            data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idEst ?>">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn-accion btn-eliminar" title="Eliminar"
                                            data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $idEst ?>">
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
// MODALES POR FILA (Ver, Editar, Eliminar)
// =========================================================
foreach ($estudiantes as $a):
    $idEst = (int) $a['idEstudiante'];
    $nombreCompleto = trim($a['apPaterno'] . ' ' . $a['apMaterno'] . ' ' . $a['nombre']);
    // Datos completos (dirección + tutor) para ver/editar
    $full = $modelo->obtener($idEst) ?? $a;

    // Salud y tutores actuales (para precargar el wizard de edición)
    $saludEst    = $modelo->saludDeEstudiante($idEst);
    $tutoresEst  = $modelo->tutoresDeEstudiante($idEst);
    $cursoActual = $modelo->cursoActualDe($idEst);
    $cursoDet    = $modelo->cursoActualDetalle($idEst);

    // Resolver, dentro de la gestión activa, el curso (nivel/turno/paralelo) actual del estudiante
    $cursoSelActual = null;
    foreach ($cursosGestionActual as $c) {
        if ((int) $c['idCurso'] === (int) $cursoActual) {
            $cursoSelActual = $c;
            break;
        }
    }

    $tPadre = $tMadre = $tApo = null;
    foreach ($tutoresEst as $tt) {
        $tipo = $tt['tipo'] ?? '';
        if ($tipo === 'Padre' && !$tPadre)      $tPadre = $tt;
        elseif ($tipo === 'Madre' && !$tMadre)  $tMadre = $tt;
        elseif (!$tApo)                          $tApo   = $tt;
    }
    $tieneP = (bool) $tPadre;
    $tieneM = (bool) $tMadre;
    $tieneA = (bool) $tApo;
    if     ($tieneP && $tieneM && $tieneA) $opEd = 'padre_madre_tutor';
    elseif ($tieneP && $tieneM)            $opEd = 'padre_madre';
    elseif ($tieneP)                       $opEd = 'padre';
    elseif ($tieneM)                       $opEd = 'madre';
    elseif ($tieneA)                       $opEd = 'tutor';
    else                                   $opEd = 'padre_madre';
?>

    <!-- ===== Modal VER <?= $idEst ?> ===== -->
    <div class="modal fade" id="modalVer<?= $idEst ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-vcard"></i> Detalle del estudiante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="detalle-cabecera">
                        <img src="<?= $rutaFoto($full['foto']) ?>" alt="Foto"
                             onerror="this.src='<?= $fotoDefault ?>'">
                        <div>
                            <h3><?= $e($nombreCompleto) ?></h3>
                            <span class="badge-cat">RUDE: <?= $e($full['rude'] ?: '—') ?></span>
                            <p class="detalle-usuario"><i class="bi bi-person-heart"></i> Tutor: <?= $e($full['tutorNombre'] ?? '—') ?></p>
                        </div>
                    </div>

                    <!-- ============ Salud, Cursos, Tutores de Estudiante ============ -->
                    <?php
                    $nombresDiscapacidades = [];
                    foreach ($discapacidades as $x) {
                        if (in_array((int) $x['idDiscapacidad'], $saludEst['discapacidades'], true)) {
                            $nombresDiscapacidades[] = $x['tipoDiscapacidad'];
                        }
                    }

                    $nombresDificultades = [];
                    foreach ($dificultades as $x) {
                        if (in_array((int) $x['idDificultad'], $saludEst['dificultades'], true)) {
                            $desc = $x['tipoDificultad'];
                            if (!empty($x['gado'])) {
                                $desc .= ' (' . $x['gado'] . ')';
                            }
                            $nombresDificultades[] = $desc;
                        }
                    }

                    $nombresCondiciones = [];
                    foreach ($condiciones as $x) {
                        if (in_array((int) $x['idCondicion'], $saludEst['condiciones'], true)) {
                            $nombresCondiciones[] = $x['condicion'];
                        }
                    }
                    ?>

                    <h6 class="form-seccion">Curso del estudiante</h6>
                    <div class="detalle-grid">
                        <div class="detalle-item"><span>Curso</span><b><?= $e($cursoDet['cursoNombre'] ?? 'Sin inscripción') ?></b></div>
                        <div class="detalle-item"><span>Paralelo</span><b><?= $e($cursoDet['paralelo'] ?? '—') ?></b></div>
                        <div class="detalle-item"><span>Nivel</span><b><?= $e($cursoDet['nivel'] ?? '—') ?></b></div>
                        <div class="detalle-item"><span>Turno</span><b><?= $e($cursoDet['turno'] ?? '—') ?></b></div>
 
                    </div>

                    <h6 class="form-seccion mt-4">Datos personales</h6>
                    <div class="detalle-grid">
                        <div class="detalle-item"><span>CI</span><b><?= $e($full['ci'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Género</span><b><?= $e($genero($full['genero'])) ?></b></div>
                        <div class="detalle-item"><span>Fecha de nacimiento</span><b><?= $e($full['fechaNacimiento'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Nacionalidad</span><b><?= $e($full['nacionalidad'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Departamento</span><b><?= $e($full['departamento'] ?: '—') ?></b></div>
                        <div class="detalle-item"><span>Provincia</span><b><?= $e($full['provincia'] ?: '—') ?></b></div>
                        <?php if (!empty($full['rude'])): ?>
                        <div class="detalle-item d-flex align-items-center gap-3" style="grid-column: span 2;">
                            <div>
                                <span class="d-block text-muted">RUDE</span>
                                <b class="fs-6 font-monospace">
                                    <?= $e($full['rude']) ?>
                                </b>
                            </div>
                            <?php if (!empty($full['qr'])): 
                                $qrFilePath = BASE_URL . 'imagenes/qr/' . $full['qr'];
                            ?>
                                <div class="ms-auto d-flex align-items-center gap-2">
                                    <img
                                        src="<?= $qrFilePath ?>"
                                        alt="Código QR del estudiante"
                                        class="img-thumbnail"
                                        style="width:70px;height:70px;">

                                    <a
                                        href="<?= $qrFilePath ?>"
                                        download="QR_RUDE_<?= $e($full['rude']) ?>.png"
                                        class="btn btn-outline-primary btn-sm"
                                        title="Descargar Código QR">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="ms-auto d-flex align-items-center gap-2">
                                    <span class="text-muted small fst-italic">Código Qr no asignado</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <h6 class="form-seccion mt-4">Salud y Discapacidad</h6>
                    <div class="detalle-grid">
                        <div class="detalle-item"><span>¿Tiene Discapacidad?</span><b><?= !empty($nombresDiscapacidades) ? 'Sí' : 'No' ?></b></div>
                        <div class="detalle-item"><span>Discapacidades</span><b><?= !empty($nombresDiscapacidades) ? implode(', ', array_map($e, $nombresDiscapacidades)) : 'Ninguna' ?></b></div>
                        <div class="detalle-item"><span>¿Tiene Dificultad de Aprendizaje?</span><b><?= !empty($nombresDificultades) ? 'Sí' : 'No' ?></b></div>
                        <div class="detalle-item"><span>Dificultades</span><b><?= !empty($nombresDificultades) ? implode(', ', array_map($e, $nombresDificultades)) : 'Ninguna' ?></b></div>
                        <div class="detalle-item"><span>¿Tiene Diagnóstico?</span><b><?= $saludEst['diagnostico'] ? 'Sí' : 'No' ?></b></div>
                        <div class="detalle-item"><span>Condiciones Médicas</span><b><?= !empty($nombresCondiciones) ? implode(', ', array_map($e, $nombresCondiciones)) : 'Ninguna' ?></b></div>
                    </div>

                    <h6 class="form-seccion mt-4">Tutores / Responsables</h6>
                    <?php if (empty($tutoresEst)): ?>
                        <p class="text-muted"><small>No tiene tutores registrados.</small></p>
                    <?php else: ?>
                        <?php foreach ($tutoresEst as $t): 
                            $tNombre = trim($t['apPaterno'] . ' ' . $t['apMaterno'] . ' ' . $t['nombre']);
                            
                            $tipo = $t['tipo'] ?? 'Tutor';
                            if ($tipo === 'Padre' || strcasecmp($tipo, 'padre') === 0) {
                                $relacion = 'Papá';
                            } elseif ($tipo === 'Madre' || strcasecmp($tipo, 'madre') === 0) {
                                $relacion = 'Mamá';
                            } else {
                                $relacion = !empty($t['parentesco']) ? $t['parentesco'] : 'Tutor / Apoderado';
                            }
                        ?>
                            <div class="mt-3 mb-2" style="font-size: 0.9rem; font-weight: 700; color: var(--color-texto); border-bottom: 2px solid var(--color-primario-cla); padding-bottom: 4px;">
                                <i class="bi bi-person-bounding-box text-primary me-2"></i>Tutor / Parentesco: <?= $e($relacion) ?>
                            </div>
                            <div class="detalle-grid mb-3">
                                <div class="detalle-item"><span>Nombre completo</span><b><?= $e($tNombre) ?></b></div>
                                <div class="detalle-item"><span>CI</span><b><?= $e($t['ci'] ?: '—') ?></b></div>
                                <div class="detalle-item"><span>Teléfono</span><b><?= $e($t['telefono'] ?: '—') ?></b></div>
                                <div class="detalle-item"><span>Correo electrónico</span><b><?= $e($t['correo'] ?: '—') ?></b></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    

                    <h6 class="form-seccion mt-4">Ubicación</h6>
                    <div class="detalle-grid">
                        <div class="detalle-item"><span>Barrio</span><b><?= $e($full['barrio'] ?? '—') ?></b></div>
                        <div class="detalle-item"><span>Calle</span><b><?= $e($full['calle'] ?? '—') ?></b></div>
                        <div class="detalle-item"><span>Número</span><b><?= $e($full['numeroDir'] ?? '—') ?></b></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-jaegasa" data-bs-dismiss="modal"
                            data-bs-toggle="modal" data-bs-target="#modalEditar<?= $idEst ?>">
                        <i class="bi bi-pencil-fill"></i> Editar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Modal EDITAR <?= $idEst ?> (Wizard 3 pasos) ===== -->
    <div class="modal fade" id="modalEditar<?= $idEst ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="<?= $accionGuardar ?>" method="post" enctype="multipart/form-data" autocomplete="off" class="wizard-form">
                    <div class="modal-header d-block">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="modal-title mb-0">Editar estudiante</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="wiz-stepper">
                            <div class="wiz-dot activo"><span>1</span> Datos</div>
                            <div class="wiz-dot"><span>2</span> Salud</div>
                            <div class="wiz-dot"><span>3</span> Tutor</div>
                        </div>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idEstudiante" value="<?= $idEst ?>">

                        <!-- PASO 1: Datos + Ubicación -->
                        <div class="wiz-step" data-step="1">
                            <div class="foto-uploader">
                                <img id="previewFotoEditar<?= $idEst ?>" src="<?= $rutaFoto($full['foto']) ?>" alt="Foto actual"
                                     onerror="this.src='<?= $fotoDefault ?>'">
                                <div class="foto-uploader-info">
                                    <label class="form-label mb-1">Cambiar foto</label>
                                    <input type="file" class="form-control form-control-sm" name="foto"
                                           accept="image/png, image/jpeg, image/webp"
                                           onchange="previsualizarFoto(this, 'previewFotoEditar<?= $idEst ?>')">
                                    <small class="text-muted">Dejar vacío para conservar la actual.</small>
                                </div>
                            </div>

                            <h6 class="form-seccion">Curso al que se inscribe</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Curso *</label>
                                    <select class="form-select curso-selector" data-tipo="grado" required <?= empty($cursosUnicos) ? 'disabled' : '' ?>>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($cursosUnicos as $id => $nomN): ?>
                                            <option value="<?= $e($id) ?>" <?= ($cursoSelActual && $cursoSelActual['cursoNombre'] === $nomN) ? 'selected' : '' ?>><?= $e($nomN) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Paralelo *</label>
                                    <select class="form-select curso-selector" data-tipo="paralelo" required <?= empty($paralelosUnicos) ? 'disabled' : '' ?>>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($paralelosUnicos as $idP => $nomP): ?>
                                            <option value="<?= (int) $idP ?>" <?= ($cursoSelActual && (int) $cursoSelActual['idParalelo'] === (int) $idP) ? 'selected' : '' ?>><?= $e($nomP) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Nivel *</label>
                                    <select class="form-select curso-selector" data-tipo="nivel" required <?= empty($nivelesUnicos) ? 'disabled' : '' ?>>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($nivelesUnicos as $idN => $nomN): ?>
                                            <option value="<?= (int) $idN ?>" <?= ($cursoSelActual && (int) $cursoSelActual['idNivel'] === (int) $idN) ? 'selected' : '' ?>><?= $e($nomN) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Turno *</label>
                                    <select class="form-select curso-selector" data-tipo="turno" required <?= empty($turnosUnicos) ? 'disabled' : '' ?>>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($turnosUnicos as $idT => $nomT): ?>
                                            <option value="<?= (int) $idT ?>" <?= ($cursoSelActual && (int) $cursoSelActual['idTurno'] === (int) $idT) ? 'selected' : '' ?>><?= $e($nomT) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                
                            </div>

                            <h6 class="form-seccion">Datos personales</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">RUDE *</label>
                                    <input type="text" class="form-control input-rude-est" name="rude" value="<?= $e($full['rude']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">CI *</label>
                                    <input type="text" class="form-control input-ci-est" name="ci" value="<?= $e($full['ci']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Apellido paterno *</label>
                                    <input type="text" class="form-control" name="apPaterno" value="<?= $e($full['apPaterno']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Apellido materno *</label>
                                    <input type="text" class="form-control" name="apMaterno" value="<?= $e($full['apMaterno']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nombre(s) *</label>
                                    <input type="text" class="form-control" name="nombre" value="<?= $e($full['nombre']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Género</label>
                                    <select class="form-select" name="genero">
                                        <option value="">Seleccione...</option>
                                        <option value="M" <?= $full['genero'] === 'M' ? 'selected' : '' ?>>Masculino</option>
                                        <option value="F" <?= $full['genero'] === 'F' ? 'selected' : '' ?>>Femenino</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Fecha de nacimiento</label>
                                    <input type="date" class="form-control" name="fechaNac" value="<?= $e($full['fechaNacimiento']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Nacionalidad</label>
                                    <select class="form-select" name="nacionalidad">
                                        <?= opcionesNacionalidades($full['nacionalidad'] ?? 'Boliviana') ?>
                                    </select>
                                </div>
                                <div class="col-md-3 ubicacion-bolivia">
                                    <label class="form-label">Departamento</label>
                                    <select class="form-select" name="departamento">
                                        <?= opcionesDepartamentos($full['departamento'] ?? '') ?>
                                    </select>
                                </div>
                                <div class="col-md-3 ubicacion-bolivia">
                                    <label class="form-label">Provincia</label>
                                    <select class="form-select" name="provincia" data-actual="<?= $e($full['provincia']) ?>">
                                        <option value="">Seleccione...</option>
                                    </select>
                                </div>
                            </div>

                            <input type="hidden" name="idCurso" value="<?= (int) $cursoActual ?>" class="idCursoResuelto" required>
                            <div class="alert alert-warning mt-2 cursoNoEncontrado" hidden>
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                No existe un curso con esa combinación de nivel, turno y paralelo en la gestión <?= $e($gestionActualAnio) ?>.
                            </div>

                            <!-- ============ Ubicación ============ -->
                            <h6 class="form-seccion mt-4">Ubicación</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Barrio</label>
                                    <input type="text" class="form-control" name="barrio" value="<?= $e($full['barrio'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Calle</label>
                                    <input type="text" class="form-control" name="calle" value="<?= $e($full['calle'] ?? '') ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Número</label>
                                    <input type="text" class="form-control" name="numero" value="<?= $e($full['numeroDir'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- PASO 2: Salud -->
                        <div class="wiz-step" data-step="2" hidden>
                            <p class="text-muted">Indique en cada caso si aplica. Al elegir "Sí" se muestran las opciones.</p>

                            <!-- Discapacidad -->
                            <div class="salud-seccion">
                                <div class="d-flex justify-content-between align-items-center form-seccion">
                                    <span>Discapacidad</span>
                                    <div class="salud-toggle">
                                        <div class="form-check form-check-inline"><label class="form-check-label"><input class="form-check-input salud-si" type="radio" name="tiene_discapacidad" value="1" <?= !empty($saludEst['discapacidades']) ? 'checked' : '' ?>> Sí</label></div>
                                        <div class="form-check form-check-inline"><label class="form-check-label"><input class="form-check-input" type="radio" name="tiene_discapacidad" value="0" <?= empty($saludEst['discapacidades']) ? 'checked' : '' ?>> No</label></div>
                                    </div>
                                </div>
                                <div class="salud-grupo">
                                    <?php if (empty($discapacidades)): ?>
                                        <p class="text-muted"><small>No hay discapacidades en el catálogo.</small></p>
                                    <?php else: ?>
                                        <div class="row g-2">
                                            <?php foreach ($discapacidades as $x): ?>
                                                <div class="col-md-6"><div class="form-check">
                                                    <label class="form-check-label">
                                                        <input class="form-check-input" type="checkbox" name="discapacidades[]"
                                                               value="<?= (int) $x['idDiscapacidad'] ?>"
                                                               <?= in_array((int) $x['idDiscapacidad'], $saludEst['discapacidades'], true) ? 'checked' : '' ?>>
                                                        <?= $e($x['tipoDiscapacidad']) ?>
                                                    </label>
                                                </div></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <label class="form-label mb-1">Otros (discapacidad no listada)</label>
                                        <input type="text" class="form-control form-control-sm" name="discapacidad_otro" placeholder="Escriba aquí si no está en la lista">
                                    </div>
                                </div>
                            </div>

                            <!-- Dificultad de aprendizaje -->
                            <div class="salud-seccion mt-4">
                                <div class="d-flex justify-content-between align-items-center form-seccion">
                                    <span>Dificultad de aprendizaje</span>
                                    <div class="salud-toggle">
                                        <div class="form-check form-check-inline"><label class="form-check-label"><input class="form-check-input salud-si" type="radio" name="tiene_dificultad" value="1" <?= !empty($saludEst['dificultades']) ? 'checked' : '' ?>> Sí</label></div>
                                        <div class="form-check form-check-inline"><label class="form-check-label"><input class="form-check-input" type="radio" name="tiene_dificultad" value="0" <?= empty($saludEst['dificultades']) ? 'checked' : '' ?>> No</label></div>
                                    </div>
                                </div>
                                <div class="salud-grupo">
                                    <?php if (empty($dificultades)): ?>
                                        <p class="text-muted"><small>No hay dificultades en el catálogo.</small></p>
                                    <?php else: ?>
                                        <div class="row g-2">
                                            <?php foreach ($dificultades as $x): ?>
                                                <div class="col-md-6"><div class="form-check">
                                                    <label class="form-check-label">
                                                        <input class="form-check-input" type="checkbox" name="dificultades[]"
                                                               value="<?= (int) $x['idDificultad'] ?>"
                                                               <?= in_array((int) $x['idDificultad'], $saludEst['dificultades'], true) ? 'checked' : '' ?>>
                                                        <?= $e($x['tipoDificultad']) ?><?= !empty($x['gado']) ? ' (' . $e($x['gado']) . ')' : '' ?>
                                                    </label>
                                                </div></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <label class="form-label mb-1">Otros (dificultad no listada)</label>
                                        <input type="text" class="form-control form-control-sm" name="dificultad_otro" placeholder="Escriba aquí si no está en la lista">
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label mb-1 d-block">¿Tiene diagnóstico?</label>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input class="form-check-input" type="radio" name="dificultad_diagnostico" value="1" <?= !empty($saludEst['diagnostico']) ? 'checked' : '' ?>>
                                                Sí
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input class="form-check-input" type="radio" name="dificultad_diagnostico" value="0" <?= empty($saludEst['diagnostico']) ? 'checked' : '' ?>>
                                                No
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Condición médica -->
                            <div class="salud-seccion mt-4">
                                <div class="d-flex justify-content-between align-items-center form-seccion">
                                    <span>Condición médica</span>
                                    <div class="salud-toggle">
                                        <div class="form-check form-check-inline"><label class="form-check-label"><input class="form-check-input salud-si" type="radio" name="tiene_condicion" value="1" <?= !empty($saludEst['condiciones']) ? 'checked' : '' ?>> Sí</label></div>
                                        <div class="form-check form-check-inline"><label class="form-check-label"><input class="form-check-input" type="radio" name="tiene_condicion" value="0" <?= empty($saludEst['condiciones']) ? 'checked' : '' ?>> No</label></div>
                                    </div>
                                </div>
                                <div class="salud-grupo">
                                    <?php if (empty($condiciones)): ?>
                                        <p class="text-muted"><small>No hay condiciones médicas en el catálogo.</small></p>
                                    <?php else: ?>
                                        <div class="row g-2">
                                            <?php foreach ($condiciones as $x): ?>
                                                <div class="col-md-6"><div class="form-check">
                                                    <label class="form-check-label">
                                                        <input class="form-check-input" type="checkbox" name="condiciones[]"
                                                               value="<?= (int) $x['idCondicion'] ?>"
                                                               <?= in_array((int) $x['idCondicion'], $saludEst['condiciones'], true) ? 'checked' : '' ?>>
                                                        <?= $e($x['condicion']) ?>
                                                    </label>
                                                </div></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- PASO 3: Tutor -->
                        <div class="wiz-step" data-step="3" hidden>
                            <div class="mb-3">
                                <label class="form-label">¿Con quién vive / quién es responsable? *</label>
                                <select class="form-select" name="tutorOpcion">
                                    <option value="padre"             <?= $opEd === 'padre' ? 'selected' : '' ?>>Solo Papá</option>
                                    <option value="madre"             <?= $opEd === 'madre' ? 'selected' : '' ?>>Solo Mamá</option>
                                    <option value="padre_madre"       <?= $opEd === 'padre_madre' ? 'selected' : '' ?>>Papá y Mamá</option>
                                    <option value="padre_madre_tutor" <?= $opEd === 'padre_madre_tutor' ? 'selected' : '' ?>>Papá, Mamá y Tutor</option>
                                    <option value="tutor"             <?= $opEd === 'tutor' ? 'selected' : '' ?>>Solo Tutor</option>
                                </select>
                            </div>

                            <div data-bloque="padre">
                                <h6 class="form-seccion"><i class="bi bi-person-fill"></i> Datos del Papá</h6>
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label">CI</label><input type="text" class="form-control" name="padre_ci" value="<?= $e($tPadre['ci'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Apellido paterno</label><input type="text" class="form-control" name="padre_apPaterno" value="<?= $e($tPadre['apPaterno'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Apellido materno</label><input type="text" class="form-control" name="padre_apMaterno" value="<?= $e($tPadre['apMaterno'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Nombre(s)</label><input type="text" class="form-control" name="padre_nombre" value="<?= $e($tPadre['nombre'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Teléfono</label><input type="text" class="form-control" name="padre_telefono" value="<?= $e($tPadre['telefono'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Correo</label><input type="email" class="form-control" name="padre_correo" value="<?= $e($tPadre['correo'] ?? '') ?>"></div>
                                </div>
                            </div>

                            <div data-bloque="madre" class="mt-3">
                                <h6 class="form-seccion"><i class="bi bi-person-fill"></i> Datos de la Mamá</h6>
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label">CI</label><input type="text" class="form-control" name="madre_ci" value="<?= $e($tMadre['ci'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Apellido paterno</label><input type="text" class="form-control" name="madre_apPaterno" value="<?= $e($tMadre['apPaterno'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Apellido materno</label><input type="text" class="form-control" name="madre_apMaterno" value="<?= $e($tMadre['apMaterno'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Nombre(s)</label><input type="text" class="form-control" name="madre_nombre" value="<?= $e($tMadre['nombre'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Teléfono</label><input type="text" class="form-control" name="madre_telefono" value="<?= $e($tMadre['telefono'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Correo</label><input type="email" class="form-control" name="madre_correo" value="<?= $e($tMadre['correo'] ?? '') ?>"></div>
                                </div>
                            </div>

                            <div data-bloque="apo" class="mt-3">
                                <h6 class="form-seccion"><i class="bi bi-person-heart"></i> Datos del Tutor / Apoderado</h6>
                                <div class="row g-3">
                                    <div class="col-md-3"><label class="form-label">CI</label><input type="text" class="form-control" name="apo_ci" value="<?= $e($tApo['ci'] ?? '') ?>"></div>
                                    <div class="col-md-3"><label class="form-label">Apellido paterno</label><input type="text" class="form-control" name="apo_apPaterno" value="<?= $e($tApo['apPaterno'] ?? '') ?>"></div>
                                    <div class="col-md-3"><label class="form-label">Apellido materno</label><input type="text" class="form-control" name="apo_apMaterno" value="<?= $e($tApo['apMaterno'] ?? '') ?>"></div>
                                    <div class="col-md-3"><label class="form-label">Nombre(s)</label><input type="text" class="form-control" name="apo_nombre" value="<?= $e($tApo['nombre'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Teléfono</label><input type="text" class="form-control" name="apo_telefono" value="<?= $e($tApo['telefono'] ?? '') ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Parentesco</label><input type="text" class="form-control" name="apo_parentesco" value="<?= $e($tApo['parentesco'] ?? '') ?>" placeholder="Tío, Abuela..."></div>
                                    <div class="col-md-4"><label class="form-label">Correo</label><input type="email" class="form-control" name="apo_correo" value="<?= $e($tApo['correo'] ?? '') ?>"></div>
                                </div>
                            </div>

                            <small class="text-muted d-block mt-2">Si un tutor ya existe (por CI), se vincula automáticamente en lugar de duplicarlo.</small>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between align-items-center">
                        <div class="text-start">
                            <label class="form-label fw-bold mb-0 text-muted small" style="font-size: 11px;">Gestión Escolar</label>
                            <div class="text-primary fw-semibold fs-6">
                                <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualAnio ?? $gestionActual ?? date('Y')) ?>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-outline-secondary wiz-prev" disabled><i class="bi bi-arrow-left"></i> Atrás</button>
                            <button type="button" class="btn btn-jaegasa wiz-next">Siguiente <i class="bi bi-arrow-right"></i></button>
                            <button type="submit" class="btn btn-jaegasa wiz-save" style="display:none;"><i class="bi bi-check-lg"></i> Guardar cambios</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== Modal ELIMINAR <?= $idEst ?> ===== -->
    <div class="modal fade" id="modalEliminar<?= $idEst ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $accionEliminar ?>" method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Eliminar estudiante</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body text-center">
                        <input type="hidden" name="idEstudiante" value="<?= $idEst ?>">
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

<!-- ===================== Modal NUEVO (Wizard 4 pasos: Curso -> Datos -> Salud -> Tutor) ===================== -->
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form action="<?= $accionGuardar ?>" method="post" enctype="multipart/form-data" autocomplete="off" id="formNuevoEstudiante" class="wizard-form">
                <div class="modal-header d-block">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="modal-title mb-0">Inscripción de Estudiante</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <!-- Indicador de pasos -->
                    <div class="wiz-stepper" id="wizDots">
                        <div class="wiz-dot activo"><span>1</span> Curso</div>
                        <div class="wiz-dot"><span>2</span> Datos</div>
                        <div class="wiz-dot"><span>3</span> Salud</div>
                        <div class="wiz-dot"><span>4</span> Tutor</div>
                    </div>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="idEstudiante" value="0">

                    <!-- ============ PASO 0 (1): Curso ============ -->
                    <div class="wiz-step" data-step="0">
                        <h6 class="form-seccion">Curso al que se inscribe el Estudiante</h6>
                        <p class="text-muted">Seleccione curso, paralelo, nivel y turno. La gestión académica se asigna automáticamente.</p>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Curso *</label>
                                <select class="form-select curso-selector" data-tipo="grado" required <?= empty($cursoUnicos) ? 'disabled' : '' ?>>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($cursosUnicos as $id => $nomN): ?>
                                        <option value="<?= $e($id) ?>"><?= $e($nomN) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Paralelo *</label>
                                <select class="form-select curso-selector" data-tipo="paralelo" required <?= empty($paralelosUnicos) ? 'disabled' : '' ?>>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($paralelosUnicos as $idP => $nomP): ?>
                                        <option value="<?= (int) $idP ?>"><?= $e($nomP) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nivel *</label>
                                <select class="form-select curso-selector" data-tipo="nivel" required <?= empty($nivelesUnicos) ? 'disabled' : '' ?>>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($nivelesUnicos as $idN => $nomN): ?>
                                        <option value="<?= (int) $idN ?>"><?= $e($nomN) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Turno *</label>
                                <select class="form-select curso-selector" data-tipo="turno" required <?= empty($turnosUnicos) ? 'disabled' : '' ?>>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($turnosUnicos as $idT => $nomT): ?>
                                        <option value="<?= (int) $idT ?>"><?= $e($nomT) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                        </div>
                        <input type="hidden" name="idCurso" value="" class="idCursoResuelto" required>
                        <div class="alert alert-warning mt-2 cursoNoEncontrado" hidden>
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            No existe un curso con esa combinación de nivel, turno y paralelo en la gestión <?= $e($gestionActualAnio) ?>.
                        </div>
                        <div class="alert mt-2 infoCupoCurso" hidden></div>
                        <?php if (empty($cursosGestionActual)): ?>
                            <small class="text-danger d-block mt-2">No hay cursos disponibles en la gestión <?= $e($gestionActualAnio) ?>. Registre un curso primero.</small>
                        <?php endif; ?>
                    </div>

                    <!-- ============ PASO 1 (2): Datos + Ubicación ============ -->
                    <div class="wiz-step" data-step="1" hidden>
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

                        <h6 class="form-seccion">Datos personales</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">RUDE *</label>
                                <input type="text" class="form-control input-rude-est" name="rude" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CI *</label>
                                <input type="text" class="form-control input-ci-est" name="ci" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Apellido paterno *</label>
                                <input type="text" class="form-control" name="apPaterno" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Apellido materno *</label>
                                <input type="text" class="form-control" name="apMaterno" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nombre(s) *</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Género</label>
                                <select class="form-select" name="genero">
                                    <option value="">Seleccione...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha de nacimiento</label>
                                <input type="date" class="form-control" name="fechaNac">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nacionalidad</label>
                                <select class="form-select" name="nacionalidad">
                                    <?= opcionesNacionalidades('Boliviana') ?>
                                </select>
                            </div>

                            <div class="col-md-3 ubicacion-bolivia">
                                <label class="form-label">Departamento</label>
                                <select class="form-select" name="departamento">
                                    <?= opcionesDepartamentos() ?>
                                </select>
                            </div>
                            <div class="col-md-3 ubicacion-bolivia">
                                <label class="form-label">Provincia</label>
                                <select class="form-select" name="provincia" data-actual="">
                                    <option value="">Seleccione...</option>
                                </select>
                            </div>
                 
                        </div>

                        <h6 class="form-seccion mt-4">Ubicación</h6>
                        <div class="row g-3">
                           
                            <div class="col-md-4">
                                <label class="form-label">Barrio</label>
                                <input type="text" class="form-control" name="barrio">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Calle</label>
                                <input type="text" class="form-control" name="calle">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Número</label>
                                <input type="text" class="form-control" name="numero">
                            </div>

                        </div>
                    </div>

                    <!-- ============ PASO 2 (3): Salud ============ -->
                    <div class="wiz-step" data-step="2" hidden>
                        <p class="text-muted">Indique en cada caso si aplica. Al elegir "Sí" se muestran las opciones.</p>

                        <!-- Discapacidad -->
                        <div class="salud-seccion">
                            <div class="d-flex justify-content-between align-items-center form-seccion">
                                <span>Discapacidad</span>
                                <div class="salud-toggle">
                                    <div class="form-check form-check-inline"><input class="form-check-input salud-si" type="radio" name="tiene_discapacidad" id="tDisSi" value="1"><label class="form-check-label" for="tDisSi">Sí</label></div>
                                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="tiene_discapacidad" id="tDisNo" value="0" checked><label class="form-check-label" for="tDisNo">No</label></div>
                                </div>
                            </div>
                            <div class="salud-grupo">
                                <?php if (empty($discapacidades)): ?>
                                    <p class="text-muted"><small>No hay discapacidades registradas en el catálogo.</small></p>
                                <?php else: ?>
                                    <div class="row g-2">
                                        <?php foreach ($discapacidades as $x): ?>
                                            <div class="col-md-6"><div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="discapacidades[]"
                                                       value="<?= (int) $x['idDiscapacidad'] ?>" id="dis<?= (int) $x['idDiscapacidad'] ?>">
                                                <label class="form-check-label" for="dis<?= (int) $x['idDiscapacidad'] ?>"><?= $e($x['tipoDiscapacidad']) ?></label>
                                            </div></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <label class="form-label mb-1">Otros (discapacidad no listada)</label>
                                    <input type="text" class="form-control form-control-sm" name="discapacidad_otro" placeholder="Escriba aquí si no está en la lista">
                                </div>
                            </div>
                        </div>

                        <!-- Dificultad de aprendizaje -->
                        <div class="salud-seccion mt-4">
                            <div class="d-flex justify-content-between align-items-center form-seccion">
                                <span>Dificultad de aprendizaje</span>
                                <div class="salud-toggle">
                                    <div class="form-check form-check-inline"><input class="form-check-input salud-si" type="radio" name="tiene_dificultad" id="tDifSi" value="1"><label class="form-check-label" for="tDifSi">Sí</label></div>
                                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="tiene_dificultad" id="tDifNo" value="0" checked><label class="form-check-label" for="tDifNo">No</label></div>
                                </div>
                            </div>
                            <div class="salud-grupo">
                                <?php if (empty($dificultades)): ?>
                                    <p class="text-muted"><small>No hay dificultades registradas en el catálogo.</small></p>
                                <?php else: ?>
                                    <div class="row g-2">
                                        <?php foreach ($dificultades as $x): ?>
                                            <div class="col-md-6"><div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="dificultades[]"
                                                       value="<?= (int) $x['idDificultad'] ?>" id="dif<?= (int) $x['idDificultad'] ?>">
                                                <label class="form-check-label" for="dif<?= (int) $x['idDificultad'] ?>"><?= $e($x['tipoDificultad']) ?><?= !empty($x['gado']) ? ' (' . $e($x['gado']) . ')' : '' ?></label>
                                            </div></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <label class="form-label mb-1">Otros (dificultad no listada)</label>
                                    <input type="text" class="form-control form-control-sm" name="dificultad_otro" placeholder="Escriba aquí si no está en la lista">
                                </div>
                                <div class="mt-2">
                                    <label class="form-label mb-1 d-block">¿Tiene diagnóstico?</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="dificultad_diagnostico" id="difDiagSiNuevo" value="1">
                                        <label class="form-check-label" for="difDiagSiNuevo">Sí</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="dificultad_diagnostico" id="difDiagNoNuevo" value="0" checked>
                                        <label class="form-check-label" for="difDiagNoNuevo">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Condición médica -->
                        <div class="salud-seccion mt-4">
                            <div class="d-flex justify-content-between align-items-center form-seccion">
                                <span>Condición médica</span>
                                <div class="salud-toggle">
                                    <div class="form-check form-check-inline"><input class="form-check-input salud-si" type="radio" name="tiene_condicion" id="tConSi" value="1"><label class="form-check-label" for="tConSi">Sí</label></div>
                                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="tiene_condicion" id="tConNo" value="0" checked><label class="form-check-label" for="tConNo">No</label></div>
                                </div>
                            </div>
                            <div class="salud-grupo">
                                <?php if (empty($condiciones)): ?>
                                    <p class="text-muted"><small>No hay condiciones médicas registradas en el catálogo.</small></p>
                                <?php else: ?>
                                    <div class="row g-2">
                                        <?php foreach ($condiciones as $x): ?>
                                            <div class="col-md-6"><div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="condiciones[]"
                                                       value="<?= (int) $x['idCondicion'] ?>" id="con<?= (int) $x['idCondicion'] ?>">
                                                <label class="form-check-label" for="con<?= (int) $x['idCondicion'] ?>"><?= $e($x['condicion']) ?></label>
                                            </div></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ============ PASO 3 (4): Tutor ============ -->
                    <div class="wiz-step" data-step="3" hidden>
                        <div class="mb-3">
                            <label class="form-label">¿Con quién vive / quién es responsable? *</label>
                            <select class="form-select" name="tutorOpcion" id="tutorOpcion">
                                <option value="padre">Solo Papá</option>
                                <option value="madre">Solo Mamá</option>
                                <option value="padre_madre" selected>Papá y Mamá</option>
                                <option value="padre_madre_tutor">Papá, Mamá y Tutor</option>
                                <option value="tutor">Solo Tutor</option>
                            </select>
                        </div>

                        <!-- Bloque Padre -->
                        <div data-bloque="padre">
                            <h6 class="form-seccion"><i class="bi bi-person-fill"></i> Datos del Papá</h6>
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">CI</label><input type="text" class="form-control" name="padre_ci"></div>
                                <div class="col-md-4"><label class="form-label">Apellido paterno</label><input type="text" class="form-control" name="padre_apPaterno"></div>
                                <div class="col-md-4"><label class="form-label">Apellido materno</label><input type="text" class="form-control" name="padre_apMaterno"></div>
                                <div class="col-md-4"><label class="form-label">Nombre(s)</label><input type="text" class="form-control" name="padre_nombre"></div>
                                <div class="col-md-4"><label class="form-label">Teléfono</label><input type="text" class="form-control" name="padre_telefono"></div>
                                <div class="col-md-4"><label class="form-label">Correo</label><input type="email" class="form-control" name="padre_correo"></div>
                            </div>
                        </div>

                        <!-- Bloque Madre -->
                        <div data-bloque="madre" class="mt-3">
                            <h6 class="form-seccion"><i class="bi bi-person-fill"></i> Datos de la Mamá</h6>
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">CI</label><input type="text" class="form-control" name="madre_ci"></div>
                                <div class="col-md-4"><label class="form-label">Apellido paterno</label><input type="text" class="form-control" name="madre_apPaterno"></div>
                                <div class="col-md-4"><label class="form-label">Apellido materno</label><input type="text" class="form-control" name="madre_apMaterno"></div>
                                <div class="col-md-4"><label class="form-label">Nombre(s)</label><input type="text" class="form-control" name="madre_nombre"></div>
                                <div class="col-md-4"><label class="form-label">Teléfono</label><input type="text" class="form-control" name="madre_telefono"></div>
                                <div class="col-md-4"><label class="form-label">Correo</label><input type="email" class="form-control" name="madre_correo"></div>
                            </div>
                        </div>

                        <!-- Bloque Tutor / Apoderado -->
                        <div data-bloque="apo" class="mt-3" hidden>
                            <h6 class="form-seccion"><i class="bi bi-person-heart"></i> Datos del Tutor / Apoderado</h6>
                            <div class="row g-3">
                                <div class="col-md-3"><label class="form-label">CI</label><input type="text" class="form-control" name="apo_ci"></div>
                                <div class="col-md-3"><label class="form-label">Apellido paterno</label><input type="text" class="form-control" name="apo_apPaterno"></div>
                                <div class="col-md-3"><label class="form-label">Apellido materno</label><input type="text" class="form-control" name="apo_apMaterno"></div>
                                <div class="col-md-3"><label class="form-label">Nombre(s)</label><input type="text" class="form-control" name="apo_nombre"></div>
                                <div class="col-md-4"><label class="form-label">Teléfono</label><input type="text" class="form-control" name="apo_telefono"></div>
                                <div class="col-md-4"><label class="form-label">Parentesco</label><input type="text" class="form-control" name="apo_parentesco" placeholder="Tío, Abuela..."></div>
                                <div class="col-md-4"><label class="form-label">Correo</label><input type="email" class="form-control" name="apo_correo"></div>
                            </div>
                        </div>

                        <small class="text-muted d-block mt-2">Si un tutor ya existe (por CI), se vincula automáticamente en lugar de duplicarlo.</small>
                    </div>
                </div>
                <div class="modal-footer justify-content-between align-items-center">
                    <div class="text-start">
                        <label class="form-label fw-bold mb-0 text-muted small" style="font-size: 11px;">Gestión Escolar</label>
                        <div class="text-primary fw-semibold fs-6">
                            <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualAnio ?? $gestionActual ?? date('Y')) ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-outline-secondary wiz-prev" disabled><i class="bi bi-arrow-left"></i> Atrás</button>
                        <button type="button" class="btn btn-jaegasa wiz-next">Siguiente <i class="bi bi-arrow-right"></i></button>
                        <button type="submit" class="btn btn-jaegasa wiz-save" style="display:none;"><i class="bi bi-check-lg"></i> Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .wiz-stepper { display:flex; gap:.5rem; margin-top:1rem; }
    .wiz-dot { flex:1; display:flex; align-items:center; gap:.4rem; font-size:.85rem; color:#9aa0a6;
               padding:.35rem .5rem; border-bottom:3px solid #e6e6e6; font-weight:600; }
    .wiz-dot span { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px;
                    border-radius:50%; background:#e6e6e6; color:#666; font-size:.8rem; }
    .wiz-dot.activo { color:#e8760c; border-bottom-color:#e8760c; }
    .wiz-dot.activo span { background:#e8760c; color:#fff; }
    .salud-toggle { font-size:.9rem; font-weight:500; }
    .salud-toggle .form-check { margin-bottom:0; }
    .salud-grupo { padding:.5rem 0 .25rem .25rem; }
</style>

<script>
document.querySelectorAll('.wizard-form').forEach(function (form) {
    const steps   = Array.from(form.querySelectorAll('.wiz-step'));
    const dots    = Array.from(form.querySelectorAll('.wiz-dot'));
    const btnPrev = form.querySelector('.wiz-prev');
    const btnNext = form.querySelector('.wiz-next');
    const btnSave = form.querySelector('.wiz-save');
    if (!steps.length || !btnNext) return;
    let actual = 0;

    function mostrar(i) {
        steps.forEach((s, idx) => { s.hidden = (idx !== i); });
        dots.forEach((d, idx) => { d.classList.toggle('activo', idx <= i); });
        btnPrev.disabled = (i === 0);
        btnNext.style.display = (i === steps.length - 1) ? 'none' : '';
        btnSave.style.display = (i === steps.length - 1) ? '' : 'none';
        const hayErrorTutor = !!form.querySelector('[data-error-rol-tutor="1"]');
        if (btnNext) btnNext.disabled = hayErrorTutor;
        if (btnSave) btnSave.disabled = hayErrorTutor;
        actual = i;
    }

    btnNext.addEventListener('click', function () {
        const campos = steps[actual].querySelectorAll('input, select, textarea');
        for (const el of campos) {
            if (!el.checkValidity()) { el.reportValidity(); return; }
        }
        if (actual < steps.length - 1) mostrar(actual + 1);
    });

    btnPrev.addEventListener('click', function () {
        if (actual > 0) mostrar(actual - 1);
    });

    // Al abrir o cerrar el modal contenedor, reiniciar estado de error y habilitar botones
    const modalEl = form.closest('.modal');
    if (modalEl) {
        const resetModalErrorState = function () {
            form.querySelectorAll('[data-error-rol-tutor]').forEach(el => {
                delete el.dataset.errorRolTutor;
                el.classList.remove('is-invalid');
                el.classList.remove('is-valid');
            });
            form.querySelectorAll('.msg-tutor-ci').forEach(el => el.remove());
            if (btnNext) btnNext.disabled = false;
            if (btnSave) btnSave.disabled = false;
        };

        modalEl.addEventListener('show.bs.modal', function () {
            resetModalErrorState();
            mostrar(0);
        });

        modalEl.addEventListener('hidden.bs.modal', resetModalErrorState);
    }

    // Mostrar/ocultar bloques de tutor según la opción
    const opcion = form.querySelector('[name="tutorOpcion"]');
    const bPadre = form.querySelector('[data-bloque="padre"]');
    const bMadre = form.querySelector('[data-bloque="madre"]');
    const bApo   = form.querySelector('[data-bloque="apo"]');

    if (opcion && bPadre && bMadre && bApo) {
        const toggleTutores = function () {
            const v = opcion.value;
            bPadre.hidden = !(v === 'padre' || v === 'padre_madre' || v === 'padre_madre_tutor');
            bMadre.hidden = !(v === 'madre' || v === 'padre_madre' || v === 'padre_madre_tutor');
            bApo.hidden   = !(v === 'tutor' || v === 'padre_madre_tutor');
        };
        opcion.addEventListener('change', toggleTutores);
        toggleTutores();
    }

    // Mostrar/ocultar las opciones de cada sección de salud según Sí/No.
    // Al elegir "No" se deshabilitan sus campos para que no se envíen.
    form.querySelectorAll('.salud-seccion').forEach(function (sec) {
        const grupo  = sec.querySelector('.salud-grupo');
        const radios = sec.querySelectorAll('.salud-toggle input[type="radio"]');
        if (!grupo || !radios.length) return;

        const aplicar = function () {
            const marcado = sec.querySelector('.salud-toggle input[type="radio"]:checked');
            const si = (marcado && marcado.value === '1');
            grupo.hidden = !si;
            grupo.querySelectorAll('input, select, textarea').forEach(function (el) {
                el.disabled = !si;
            });
        };
        radios.forEach(function (r) { r.addEventListener('change', aplicar); });
        aplicar();
    });

    // Resolver el idCurso real a partir de Nivel + Turno + Paralelo + Grado (gestión fija/activa)
    const selGrado    = form.querySelector('[data-tipo="grado"]');
    const selNivel    = form.querySelector('[data-tipo="nivel"]');
    const selTurno    = form.querySelector('[data-tipo="turno"]');
    const selParalelo = form.querySelector('[data-tipo="paralelo"]');
    const hiddenCurso = form.querySelector('.idCursoResuelto');
    const avisoCurso  = form.querySelector('.cursoNoEncontrado');

    if (selNivel && selTurno && selParalelo && hiddenCurso) {
        if (selGrado) {
            const resolverConGrado = function () {
                if (typeof actualizarOpcionesCurso === 'function') {
                    actualizarOpcionesCurso(form);
                }
                const cursoNombre = selGrado.value;
                const idNivel = parseInt(selNivel.value, 10);
                const idTurno = parseInt(selTurno.value, 10);
                const idParalelo = parseInt(selParalelo.value, 10);
                const infoCupo = form.querySelector('.infoCupoCurso');
                const btnNext  = form.querySelector('.wiz-next');

                let match = null;
                if (cursoNombre && idNivel && idTurno && idParalelo) {
                    match = CURSOS_DATA.find(function (c) {
                        return c.cursoNombre === cursoNombre 
                            && c.idNivel === idNivel 
                            && c.idTurno === idTurno 
                            && c.idParalelo === idParalelo;
                    });
                }

                hiddenCurso.value = match ? match.idCurso : '';

                // Mostrar advertencia o info de cupo si se seleccionaron los 4 campos
                if (cursoNombre && idNivel && idTurno && idParalelo) {
                    if (avisoCurso) avisoCurso.hidden = !!match;
                    if (match) {
                        const cupo = match.cupo || 36;
                        const inscritos = match.inscritos || 0;
                        const disponibles = Math.max(0, cupo - inscritos);
                        if (infoCupo) {
                            infoCupo.hidden = false;
                            if (disponibles > 0) {
                                infoCupo.className = 'alert alert-info mt-2 infoCupoCurso d-flex align-items-center gap-2 mb-0 py-2';
                                infoCupo.innerHTML = '<i class="bi bi-info-circle-fill text-info fs-5"></i> <div><strong>Cupos Disponibles:</strong> <span class="badge bg-success fs-6 ms-1 me-1">' + disponibles + ' / ' + cupo + '</span> (Estudiantes inscritos: <b>' + inscritos + '</b>)</div>';
                                if (btnNext) btnNext.disabled = false;
                            } else {
                                infoCupo.className = 'alert alert-danger mt-2 infoCupoCurso d-flex align-items-center gap-2 mb-0 py-2';
                                infoCupo.innerHTML = '<i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i> <div><strong>¡Sin cupo disponible!</strong> Este curso ha alcanzado su límite máximo de <span class="badge bg-danger fs-6 ms-1 me-1">' + inscritos + ' / ' + cupo + '</span> estudiantes. No es posible inscribir más alumnos.</div>';
                                hiddenCurso.value = '';
                                if (btnNext) btnNext.disabled = true;
                            }
                        }
                    } else {
                        if (infoCupo) infoCupo.hidden = true;
                        if (btnNext) btnNext.disabled = false;
                    }
                } else {
                    if (avisoCurso) avisoCurso.hidden = true;
                    if (infoCupo) infoCupo.hidden = true;
                    if (btnNext) btnNext.disabled = false;
                }
            };

            [selGrado, selNivel, selTurno, selParalelo].forEach(function (s) {
                s.addEventListener('change', resolverConGrado);
            });
            resolverConGrado();
        } else {
            const resolverSinGrado = function () {
                const idNivel = parseInt(selNivel.value, 10);
                const idTurno = parseInt(selTurno.value, 10);
                const idParalelo = parseInt(selParalelo.value, 10);
                const infoCupo = form.querySelector('.infoCupoCurso');

                let match = null;
                if (idNivel && idTurno && idParalelo) {
                    match = CURSOS_DATA.find(function (c) {
                        return c.idNivel === idNivel 
                            && c.idTurno === idTurno 
                            && c.idParalelo === idParalelo;
                    });
                }

                hiddenCurso.value = match ? match.idCurso : '';
                if (avisoCurso) avisoCurso.hidden = !!match;

                if (idNivel && idTurno && idParalelo && match && infoCupo) {
                    const cupo = match.cupo || 36;
                    const inscritos = match.inscritos || 0;
                    const disponibles = Math.max(0, cupo - inscritos);
                    infoCupo.hidden = false;
                    if (disponibles > 0) {
                        infoCupo.className = 'alert alert-info mt-2 infoCupoCurso d-flex align-items-center gap-2 mb-0 py-2';
                        infoCupo.innerHTML = '<i class="bi bi-info-circle-fill text-info fs-5"></i> <div><strong>Cupos Disponibles:</strong> <span class="badge bg-success fs-6 ms-1 me-1">' + disponibles + ' / ' + cupo + '</span> (Inscritos: <b>' + inscritos + '</b>)</div>';
                    } else {
                        infoCupo.className = 'alert alert-danger mt-2 infoCupoCurso d-flex align-items-center gap-2 mb-0 py-2';
                        infoCupo.innerHTML = '<i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i> <div><strong>¡Sin cupo disponible!</strong> (' + inscritos + ' / ' + cupo + ')</div>';
                        hiddenCurso.value = '';
                    }
                } else if (infoCupo) {
                    infoCupo.hidden = true;
                }
            };

            [selNivel, selTurno, selParalelo].forEach(function (s) {
                s.addEventListener('change', resolverSinGrado);
            });
            resolverSinGrado();
        }
    }

    mostrar(0);

});

// Abrir modal automáticamente si viene el parámetro ?nuevo=1 en la URL
window.addEventListener('load', function () {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('nuevo') === '1') {
        const btnNuevo = document.querySelector('[data-bs-target="#modalNuevo"]');
        if (btnNuevo) {
            btnNuevo.click();
        }
    }
});

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

// Verificar en tiempo real si RUDE o CI ya pertenecen a otro estudiante
document.addEventListener('DOMContentLoaded', function () {
    let timeoutsEst = new WeakMap();

    function verificarDuplicadoEstudiante(input) {
        const form = input.closest('form');
        if (!form) return;

        const rudeInput  = form.querySelector('.input-rude-est');
        const ciInput    = form.querySelector('.input-ci-est');
        const idEstInput = form.querySelector('input[name="idEstudiante"]');
        const idEst      = idEstInput ? idEstInput.value : 0;

        const rudeVal = rudeInput ? rudeInput.value.trim() : '';
        const ciVal   = ciInput ? ciInput.value.trim() : '';

        let msgEl = input.parentNode.querySelector('.msg-est-duplicado');

        if (rudeVal === '' && ciVal === '') {
            if (msgEl) msgEl.remove();
            input.classList.remove('is-invalid');
            return;
        }

        const origin = window.location.origin;
        const path = window.location.pathname;
        const folder = path.substring(0, path.lastIndexOf('/') + 1);
        const baseUrl = window.BASE_URL || (origin + folder);

        fetch(baseUrl + 'index.php?c=estudiante&a=verificar_estudiante&rude=' + encodeURIComponent(rudeVal) + '&ci=' + encodeURIComponent(ciVal) + '&id=' + idEst)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.ok && data.duplicado) {
                    input.classList.add('is-invalid');
                    if (!msgEl) {
                        msgEl = document.createElement('small');
                        msgEl.className = 'text-danger fw-bold d-block mt-1 msg-est-duplicado';
                        input.parentNode.appendChild(msgEl);
                    }
                    msgEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> ' + (data.mensaje || 'El estudiante ya está inscrito.');
                } else {
                    input.classList.remove('is-invalid');
                    if (msgEl) msgEl.remove();
                }
            })
            .catch(function (err) {
                console.error('Error al verificar estudiante:', err);
            });
    }

    document.addEventListener('input', function (e) {
        if (!e.target || e.target.tagName !== 'INPUT') return;
        if (e.target.classList.contains('input-rude-est') || e.target.classList.contains('input-ci-est')) {
            const input = e.target;
            if (timeoutsEst.has(input)) clearTimeout(timeoutsEst.get(input));
            const timer = setTimeout(function () { verificarDuplicadoEstudiante(input); }, 300);
            timeoutsEst.set(input, timer);
        }
    }, true);

    document.addEventListener('blur', function (e) {
        if (!e.target || e.target.tagName !== 'INPUT') return;
        if (e.target.classList.contains('input-rude-est') || e.target.classList.contains('input-ci-est')) {
            verificarDuplicadoEstudiante(e.target);
        }
    }, true);

    // ------------------------------------------------------------
    // Búsqueda en tiempo real (filtrado instantáneo por nombre/apellidos/CI/RUDE)
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

        function filtrarEstudiantes() {
            if (!tbody) return;
            const query   = normalizarTexto(inputBuscar.value);
            const tokens  = query.split(/\s+/).filter(t => t.length > 0);
            const filas   = tbody.querySelectorAll('tr:not(.fila-sin-resultados)');
            let visibles  = 0;

            filas.forEach(fila => {
                if (fila.querySelector('td[colspan]')) {
                    const text = fila.textContent.toLowerCase();
                    if (text.includes('no hay estudiantes')) return;
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
                        <td colspan="11" class="text-center text-muted py-4">
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
                badgeTotal.textContent = `${visibles} ESTUDIANTE(S) INSCRITO(S)`;
            }
        }

        const btnLimpiarEst = document.getElementById('btnLimpiarEstudiante');
        const btnImprimirEst = document.getElementById('btnImprimirEstudiante');

        function actualizarVisibilidadBotonesEstudiante() {
            const query = inputBuscar.value.trim();
            const visible = (query !== '');
            if (btnLimpiarEst) btnLimpiarEst.style.display = visible ? 'inline-block' : 'none';
            if (btnImprimirEst) btnImprimirEst.style.display = visible ? 'inline-block' : 'none';
        }

        inputBuscar.addEventListener('input', function () {
            actualizarVisibilidadBotonesEstudiante();
            filtrarEstudiantes();
        });

        actualizarVisibilidadBotonesEstudiante();
        if (inputBuscar.value.trim() !== '') {
            filtrarEstudiantes();
        }
    }

    const modoRadios = document.querySelectorAll('input[name="modoBusquedaRadio"]');
    modoRadios.forEach(r => r.addEventListener('change', function () {
        const modo = this.value;
        window.location.href = `index.php?c=estudiante&modoBusqueda=${modo}`;
    }));
});

function imprimirListadoEstudiantes() {
    const logoSrc = window.schoolLogoBase64 || '';
    
    // Obtener información del filtro actual
    let filtroTexto = 'LISTADO GENERAL DE ESTUDIANTES';
    const selGrado = document.querySelector('select[name="grado"]');
    const selParalelo = document.querySelector('select[name="paralelo"]');
    const selNivel = document.querySelector('select[name="nivel"]');
    const selTurno = document.querySelector('select[name="turno"]');
    
    let partesFiltro = [];
    if (selGrado && selGrado.selectedIndex > 0 && selGrado.options[selGrado.selectedIndex].value !== '') {
        partesFiltro.push('CURSO: ' + selGrado.options[selGrado.selectedIndex].text.toUpperCase());
    }
    if (selParalelo && selParalelo.selectedIndex > 0 && selParalelo.options[selParalelo.selectedIndex].value !== '') {
        partesFiltro.push('PARALELO: ' + selParalelo.options[selParalelo.selectedIndex].text.toUpperCase());
    }
    if (selNivel && selNivel.selectedIndex > 0 && selNivel.options[selNivel.selectedIndex].value !== '') {
        partesFiltro.push('NIVEL: ' + selNivel.options[selNivel.selectedIndex].text.toUpperCase());
    }
    if (selTurno && selTurno.selectedIndex > 0 && selTurno.options[selTurno.selectedIndex].value !== '') {
        partesFiltro.push('TURNO: ' + selTurno.options[selTurno.selectedIndex].text.toUpperCase());
    }

    if (partesFiltro.length > 0) {
        filtroTexto = partesFiltro.join(' &nbsp;|&nbsp; ');
    } else {
        const inputQ = document.querySelector('input[name="q"]');
        if (inputQ && inputQ.value.trim() !== '') {
            filtroTexto = 'FILTRO DE BÚSQUEDA: "' + inputQ.value.trim().toUpperCase() + '"';
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
                const paterno = tr.dataset.paterno || '';
                const materno = tr.dataset.materno || '';
                const nombre  = tr.dataset.nombre || '';
                const rude    = tr.dataset.rude || (tr.querySelectorAll('td')[3] ? tr.querySelectorAll('td')[3].textContent.trim() : '—');
                
                filasAImprimir.push({
                    num: contador++,
                    rude: rude || '—',
                    paterno: paterno || '—',
                    materno: materno || '—',
                    nombre: nombre || '—'
                });
            }
        });
    }

    if (filasAImprimir.length === 0) {
        alert('No hay estudiantes visibles en la lista para imprimir.');
        return;
    }

    let rowsHtml = '';
    filasAImprimir.forEach(st => {
        rowsHtml += `
            <tr>
                <td style="text-align: center; font-weight: bold;">${st.num}</td>
                <td style="font-family: monospace; font-weight: 700; text-align: center;">${st.rude}</td>
                <td style="font-weight: 600; text-align: left;">${st.paterno}</td>
                <td style="font-weight: 600; text-align: left;">${st.materno}</td>
                <td style="font-weight: 600; text-align: left;">${st.nombre}</td>
            </tr>
        `;
    });

    const htmlContent = `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Listado Oficial de Estudiantes - U.E. Prof. Jaime Alfonso Escalante Gutiérrez "A"</title>
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
                .tabla-listado th, .tabla-listado td { border: 1px solid #475569; padding: 4.5px 6px; font-size: 9.5px; }
                .tabla-listado th { background-color: #334155; color: #ffffff; font-weight: 900; text-transform: uppercase; font-size: 10px; text-align: center; letter-spacing: 0.5px; border: 1.5px solid #1e293b; }
                .tabla-listado tr:nth-child(even) { background-color: #f1f5f9; }

                .resumen-footer { margin-top: 8px; display: flex; justify-content: space-between; font-weight: 700; font-size: 9px; color: #334155; }
                
                .firmas { margin-top: 25px; display: flex; justify-content: space-around; text-align: center; page-break-inside: avoid; }
                .firma-box { width: 38%; border-top: 1.5px dashed #475569; padding-top: 4px; font-weight: 700; font-size: 9.5px; color: #334155; }

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
                        <th style="width: 25%;">CÓDIGO RUDE</th>
                        <th style="width: 25%;">APELLIDO PATERNO</th>
                        <th style="width: 25%;">APELLIDO MATERNO</th>
                        <th style="width: 20%;">NOMBRES</th>
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
        setTimeout(() => {
            printWin.print();
        }, 400);
    } else {
        let iframe = document.getElementById('iframeImpresionListado');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'iframeImpresionListado';
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

<script src="<?= BASE_URL ?>Js/tutor_autocompletar.js?v=<?= @filemtime(BASE_PATH . '/Js/tutor_autocompletar.js') ?>"></script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>