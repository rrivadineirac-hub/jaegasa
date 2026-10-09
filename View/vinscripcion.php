<?php
/**
 * vinscripcion.php — Vista de Inscripciones (sin JS)
 * Recibe: $inscripciones, $estudiantes, $cursos, $flash, $buscar
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$accionGuardar  = BASE_URL . 'index.php?c=inscripcion&a=guardar';
$accionEliminar = BASE_URL . 'index.php?c=inscripcion&a=eliminar';

// (ese wizard ya pide el Curso como primer paso y crea la inscripción solo).
$urlNuevoEstudiante = BASE_URL . 'index.php?c=estudiante&nuevo=1';

// ------------------------------------------------------------
// Gestión activa e información de cursos para el selector
// ------------------------------------------------------------
$idCurso = $idCurso ?? 0;
$modoBusqueda = $modoBusqueda ?? 'estudiante';

$gestionActualAnio = (function () use ($cursos) {
    $anios = array_column($cursos, 'anio');
    return $anios ? max($anios) : (int) date('Y');
})();

// Filtrar cursos de la gestión actual
$cursosGestionActual = array_values(array_filter(
    $cursos,
    fn($c) => (string) ($c['anio'] ?? '') === (string) $gestionActualAnio
));

// Catálogos únicos
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

// Ordenar grados
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

// Encontrar el curso seleccionado actualmente, para pre-seleccionar
$cursoSeleccionado = null;
if ($idCurso > 0) {
    foreach ($cursosGestionActual as $c) {
        if ((int)$c['idCurso'] === $idCurso) {
            $cursoSeleccionado = $c;
            break;
        }
    }
}
?>

<!-- Datos de cursos de la gestión activa, para resolver idCurso en el navegador -->
<script>
    const CURSOS_DATA = <?= json_encode(array_map(fn($c) => [
        'idCurso'     => (int) $c['idCurso'],
        'cursoNombre' => $c['cursoNombre'] ?? '',
        'idNivel'     => (int) $c['idNivel'],
        'idTurno'     => (int) $c['idTurno'],
        'idParalelo'  => (int) $c['idParalelo'],
    ], $cursosGestionActual)) ?>;
</script>

<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-clipboard-check"></i> Inscripciones</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Inscripción de estudiantes a cursos</p>
    </div>
    <a class="btn btn-jaegasa" href="<?= $urlNuevoEstudiante ?>">
        <i class="bi bi-plus-lg"></i> Nueva inscripción
    </a>
</div>



<?php if (empty($cursos)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill"></i>
        No hay <b>cursos</b> registrados. Debe crear cursos antes de poder inscribir estudiantes.
    </div>
<?php endif; ?>

<div class="card-panel">
    <form method="get" action="<?= BASE_URL ?>index.php" class="d-flex flex-column gap-2 w-100" id="formBusquedaAsistencia">
        <input type="hidden" name="c" value="inscripcion">
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
                <span class="tabla-total m-0"><?= !empty($realizoBusqueda) ? count($inscripciones) . ' REGISTRO(S) ENCONTRADO(S)' : 'SIN BÚSQUEDA REALIZADA' ?></span>
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
                <a href="<?= BASE_URL ?>index.php?c=inscripcion" id="btnLimpiarEstudiante"
                   class="btn btn-sm btn-outline-secondary"
                   style="<?= empty($buscar) ? 'display: none;' : '' ?>">Limpiar</a>
            </div>
        </fieldset>

        <!-- ================================================================ -->
        <!-- BLOQUE 2: Búsqueda por curso -->
        <!-- ================================================================ -->
        <fieldset class="bloque-busqueda mb-2 <?= ($modoBusqueda ?? 'estudiante') === 'estudiante' ? 'd-none' : '' ?>" id="bloqueCurso">
            <h6 class="form-seccion mb-2 fw-semibold text-jaegasa"><i class="bi bi-filter-square"></i> BÚSQUEDA POR CURSO</h6>
            <p class="text-muted small mb-3">Seleccione el curso, paralelo, nivel y turno para consultar a los estudiantes inscritos.</p>

            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Curso / Grado *</label>
                    <select class="form-select curso-selector" data-tipo="grado" name="grado" <?= empty($cursosUnicos) ? 'disabled' : '' ?>>
                        <option value="">Seleccione...</option>
                        <?php foreach ($cursosUnicos as $id => $nomG): ?>
                            <option value="<?= $e($id) ?>" <?= ($cursoSeleccionado && $cursoSeleccionado['cursoNombre'] === $id) || (isset($_GET['grado']) && (string)$_GET['grado'] === (string)$id) ? 'selected' : '' ?>><?= $e($nomG) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Paralelo *</label>
                    <select class="form-select curso-selector" data-tipo="paralelo" name="paralelo" <?= empty($paralelosUnicos) ? 'disabled' : '' ?>>
                        <option value="">Seleccione...</option>
                        <?php foreach ($paralelosUnicos as $idP => $nomP): ?>
                            <option value="<?= (int) $idP ?>" <?= ($cursoSeleccionado && (int)$cursoSeleccionado['idParalelo'] === (int)$idP) || (isset($_GET['paralelo']) && (string)$_GET['paralelo'] === (string)$idP) ? 'selected' : '' ?>><?= $e($nomP) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Nivel *</label>
                    <select class="form-select curso-selector" data-tipo="nivel" name="nivel" <?= empty($nivelesUnicos) ? 'disabled' : '' ?>>
                        <option value="">Seleccione...</option>
                        <?php foreach ($nivelesUnicos as $idN => $nomN): ?>
                            <option value="<?= (int) $idN ?>" <?= ($cursoSeleccionado && (int)$cursoSeleccionado['idNivel'] === (int)$idN) || (isset($_GET['nivel']) && (string)$_GET['nivel'] === (string)$idN) || (count($nivelesUnicos) === 1 && !isset($_GET['nivel'])) ? 'selected' : '' ?>><?= $e($nomN) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Turno *</label>
                    <select class="form-select curso-selector" data-tipo="turno" name="turno" <?= empty($turnosUnicos) ? 'disabled' : '' ?>>
                        <option value="">Seleccione...</option>
                        <?php foreach ($turnosUnicos as $idT => $nomT): ?>
                            <option value="<?= (int) $idT ?>" <?= ($cursoSeleccionado && (int)$cursoSeleccionado['idTurno'] === (int)$idT) || (isset($_GET['turno']) && (string)$_GET['turno'] === (string)$idT) || (count($turnosUnicos) === 1 && !isset($_GET['turno'])) ? 'selected' : '' ?>><?= $e($nomT) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-jaegasa flex-grow-1">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <a href="<?= BASE_URL ?>index.php?c=inscripcion&modoBusqueda=curso" id="btnLimpiarCurso"
                       class="btn btn-sm btn-outline-secondary"
                       style="<?= empty($realizoBusqueda) || ($modoBusqueda ?? '') !== 'curso' ? 'display: none;' : '' ?>">Limpiar</a>
                </div>
            </div>

            <input type="hidden" name="idCurso" id="idCursoResuelto" value="<?= $idCurso > 0 ? $idCurso : '' ?>" class="idCursoResuelto">

            <div class="alert alert-warning mt-3 mb-0 cursoNoEncontrado" hidden>
                <i class="bi bi-exclamation-triangle-fill"></i>
                No existe un curso con esa combinación de nivel, turno y paralelo en la gestión <?= $e($gestionActualAnio) ?>.
            </div>

            <?php if (empty($cursosGestionActual)): ?>
                <small class="text-danger d-block mt-2">No hay cursos disponibles en la gestión <?= $e($gestionActualAnio) ?>. Registre un curso primero.</small>
            <?php endif; ?>
        </fieldset>
    </form>

    <div class="table-responsive mt-4">
        <table class="table table-hover align-middle" id="tablaAdmin">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Nombre del Estudiante</th>
                    <th>RUDE</th>
                    <th>C.I.</th>
                    <th>Curso</th>
                    <th>Registrado por</th>
                    <th>Fecha</th>
                    <th class="text-end" style="width: 100px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inscripciones)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> No hay inscripciones registradas.</td></tr>
                <?php else: ?>
                    <tr id="filaNoBusqueda" <?= !empty($realizoBusqueda) ? 'style="display: none;"' : '' ?>>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-search fs-1 d-block text-secondary mb-2 opacity-50"></i>
                            <h6 class="fw-bold text-dark">No se han realizado búsquedas</h6>
                            <p class="small text-muted mb-0">Seleccione un filtro (Curso, Paralelo, Nivel o Turno) o ingrese un nombre para consultar a los estudiantes inscritos.</p>
                        </td>
                    </tr>
                    <?php foreach ($inscripciones as $i => $a): ?>
                        <?php $idIns = (int) $a['idInscripcion']; ?>
                        <tr class="fila-estudiante-item"
                            data-paterno="<?= $e($a['apPaterno'] ?? '') ?>"
                            data-materno="<?= $e($a['apMaterno'] ?? '') ?>"
                            data-nombre="<?= $e($a['nombre'] ?? '') ?>"
                            data-estudiante="<?= $e($a['estudiante'] ?? '') ?>"
                            data-rude="<?= $e($a['rude'] ?? '') ?>"
                            data-ci="<?= $e($a['estudianteCi'] ?? '') ?>"
                            <?= empty($realizoBusqueda) ? 'style="display: none;"' : '' ?>>
                            <td><?= $i + 1 ?></td>
                            <td class="fw-semibold text-start"><?= $e($a['estudiante']) ?></td>
                            <td><?= $e($a['rude'] ?: '—') ?></td>
                            <td><?= $e($a['estudianteCi'] ?: '—') ?></td>
                            <td><span class="badge-cat"><?= $e($a['curso']) ?></span></td>
                            <td><?= $e($a['registrado'] ?: '—') ?></td>
                            <td><?= $e($a['fechaRegistro']) ?></td>
                            <td class="text-end text-nowrap">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <?php
                                        $padreArr = (!empty($a['padre']) && is_array($a['padre'])) ? [
                                            'nombre'    => trim(($a['padre']['apPaterno'] ?? '') . ' ' . ($a['padre']['apMaterno'] ?? '') . ' ' . ($a['padre']['nombre'] ?? '')),
                                            'ci'        => $a['padre']['ci'] ?? '',
                                            'ocupacion' => $a['padre']['ocupacion'] ?? '',
                                            'telefono'  => $a['padre']['telefono'] ?? ''
                                        ] : null;

                                        $madreArr = (!empty($a['madre']) && is_array($a['madre'])) ? [
                                            'nombre'    => trim(($a['madre']['apPaterno'] ?? '') . ' ' . ($a['madre']['apMaterno'] ?? '') . ' ' . ($a['madre']['nombre'] ?? '')),
                                            'ci'        => $a['madre']['ci'] ?? '',
                                            'ocupacion' => $a['madre']['ocupacion'] ?? '',
                                            'telefono'  => $a['madre']['telefono'] ?? ''
                                        ] : null;

                                        $tutorArr = (!empty($a['tutorOtro']) && is_array($a['tutorOtro'])) ? [
                                            'nombre'     => trim(($a['tutorOtro']['apPaterno'] ?? '') . ' ' . ($a['tutorOtro']['apMaterno'] ?? '') . ' ' . ($a['tutorOtro']['nombre'] ?? '')),
                                            'ci'         => $a['tutorOtro']['ci'] ?? '',
                                            'parentesco' => ($a['tutorOtro']['relParentesco'] ?? 'Tutor') . (!empty($a['tutorOtro']['ocupacion']) ? ' - ' . $a['tutorOtro']['ocupacion'] : ''),
                                            'telefono'   => $a['tutorOtro']['telefono'] ?? ''
                                        ] : (!empty($a['tutorNombre']) ? [
                                            'nombre'     => $a['tutorNombre'],
                                            'ci'         => $a['tutorCi'] ?? '',
                                            'parentesco' => $a['tutorParentesco'] ?? 'Tutor',
                                            'telefono'   => $a['tutorTelefono'] ?? ''
                                        ] : null);

                                        $jsonImp = htmlspecialchars(json_encode([
                                            'nro'          => $i + 1,
                                            'paterno'      => $a['apPaterno'] ?? '',
                                            'materno'      => $a['apMaterno'] ?? '',
                                            'nombre'       => $a['nombre'] ?? '',
                                            'estudiante'   => $a['estudiante'] ?? '',
                                            'rude'         => $a['rude'] ?: '—',
                                            'ci'           => $a['estudianteCi'] ?: '—',
                                            'genero'       => $a['genero'] ?? '',
                                            'fechaNac'     => $a['fechaNacimiento'] ?? '',
                                            'depNac'       => $a['depNac'] ?? '',
                                            'provNac'      => $a['provNac'] ?? '',
                                            'nacionalidad' => $a['nacionalidad'] ?? 'Boliviana',
                                            'barrio'       => $a['barrio'] ?? '',
                                            'localidad'    => $a['localidad'] ?? '',
                                            'calle'        => trim(($a['calle'] ?? '') . ' ' . ($a['numeroDir'] ?? '')),
                                            'depDir'       => $a['departamentoDir'] ?? 'Santa Cruz',
                                            'padre'        => $padreArr,
                                            'madre'        => $madreArr,
                                            'tutor'        => $tutorArr,
                                            'discapacidades' => $a['discapacidades'] ?? [],
                                            'dificultades'   => $a['dificultades']   ?? [],
                                            'condiciones'    => $a['condiciones']    ?? [],
                                            'curso'        => $a['curso'] ?? '',
                                            'cursoGrado'   => $a['cursoGrado'] ?? '',
                                            'nivel'        => $a['nivelNombre'] ?? '',
                                            'paralelo'     => $a['paraleloNombre'] ?? '',
                                            'turno'        => $a['turnoNombre'] ?? '',
                                            'gestion'      => $a['gestionAnio'] ?? date('Y'),
                                            'registrado'   => $a['registrado'] ?: '—',
                                            'fecha'        => $a['fechaRegistro'] ?? ''
                                        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-success shadow-sm rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                                            style="width: 32px; height: 32px;"
                                            data-json="<?= $jsonImp ?>"
                                            onclick="imprimirComprobanteInscripcion(this)"
                                            title="Imprimir Formulario RUDE / Comprobante">
                                        <i class="bi bi-printer-fill fs-6"></i>
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


<script src="<?= BASE_URL ?>Js/inscripcion.js?v=<?= @filemtime(BASE_PATH . '/Js/inscripcion.js') ?>"></script>
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
                    if (badgeTotal) badgeTotal.textContent = `${visibles} REGISTRO(S) ENCONTRADO(S)`;
                    return;
                }
            }

            if (filaNoBusqueda) filaNoBusqueda.style.display = 'none';

            filas.forEach(fila => {
                const paterno     = normalizarTexto(fila.dataset.paterno || '');
                const materno     = normalizarTexto(fila.dataset.materno || '');
                const nombre      = normalizarTexto(fila.dataset.nombre || '');
                const estudiante  = normalizarTexto(fila.dataset.estudiante || '');
                const rude        = normalizarTexto(fila.dataset.rude || '');
                const ci          = normalizarTexto(fila.dataset.ci || '');

                const fullPaterno = paterno || materno || nombre ? [paterno, materno, nombre].filter(Boolean).join(' ') : estudiante;
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
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-search fs-3 d-block mb-1"></i>
                            No se encontraron inscripciones que coincidan con "<strong>${inputBuscar.value}</strong>".
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

        function actualizarBotonLimpiarEstudiante() {
            if (!inputBuscar || !btnLimpiarEstudiante) return;
            if (inputBuscar.value.trim().length > 0) {
                btnLimpiarEstudiante.style.display = 'inline-block';
            } else {
                btnLimpiarEstudiante.style.display = 'none';
            }
        }

        inputBuscar.addEventListener('input', function () {
            actualizarBotonLimpiarEstudiante();
            filtrarTabla();
        });

        if (btnLimpiarEstudiante) {
            btnLimpiarEstudiante.addEventListener('click', function (e) {
                if (inputBuscar.value.trim() !== '') {
                    inputBuscar.value = '';
                    actualizarBotonLimpiarEstudiante();
                    filtrarTabla();
                    if (window.location.search.includes('q=')) {
                        e.preventDefault();
                        window.location.href = '<?= BASE_URL ?>index.php?c=inscripcion';
                    } else {
                        e.preventDefault();
                    }
                }
            });
        }

        actualizarBotonLimpiarEstudiante();
        filtrarTabla();
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

function imprimirComprobanteInscripcion(element) {
    let data = {};
    if (typeof element === 'string') {
        try { data = JSON.parse(element); } catch(e) { data = {}; }
    } else if (element && element.getAttribute && element.getAttribute('data-json')) {
        try { data = JSON.parse(element.getAttribute('data-json')); } catch(e) { data = {}; }
    } else if (element && typeof element === 'object') {
        data = element;
    }

    // Generar cuadrícula de dígitos RUDE (16 casillas)
    const rudePad = (data.rude || '').padEnd(16, ' ');
    let rudeBoxes = '';
    for (let k = 0; k < 16; k++) {
        const char = rudePad[k] !== ' ' ? rudePad[k] : '&nbsp;';
        rudeBoxes += `<div class="rude-char">${char}</div>`;
    }

    const discList = (data.discapacidades && data.discapacidades.length > 0) ? data.discapacidades.join(', ') : 'Ninguna';
    const hasDisc = (data.discapacidades && data.discapacidades.length > 0);
    
    const hasDif = (data.dificultades && data.dificultades.length > 0);
    let difList = 'Ninguna';
    let diagStr = 'No [X]';
    if (hasDif) {
        difList = data.dificultades.map(d => typeof d === 'object' ? (d.tipoDificultad || d) : d).join(', ');
        const tieneDiag = data.dificultades.some(d => typeof d === 'object' && d.diagnostico == 1);
        if (tieneDiag) diagStr = 'Sí [X]';
    }

    const hasCond = (data.condiciones && data.condiciones.length > 0);
    const condList = hasCond ? data.condiciones.join(', ') : 'Ninguna';

    const htmlContent = `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Formulario RUDE - ${data.estudiante || ''}</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; color: #111827; background: #fff; font-size: 13px; }
                .titulo-head { text-align: center; border-bottom: 2px solid #1D8AB5; padding-bottom: 8px; margin-bottom: 12px; }
                .titulo-head h6 { font-size: 11px; margin: 0; font-weight: 700; color: #4b5563; }
                .titulo-head h4 { font-size: 16px; margin: 2px 0; font-weight: 800; color: #1D8AB5; }
                .titulo-head p { font-size: 9px; margin: 0; color: #6b7280; }
                
                .rude-container { display: flex; align-items: center; gap: 4px; margin-bottom: 14px; }
                .rude-label { font-weight: 700; font-size: 11px; margin-right: 8px; }
                .rude-char { width: 22px; height: 26px; border: 1.5px solid #111827; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; background: #f9fafb; }

                .seccion-box { border: 1.5px solid #d1d5db; border-radius: 4px; margin-bottom: 12px; overflow: hidden; }
                .seccion-header { background-color: #f3f4f6; padding: 5px 10px; font-weight: 700; font-size: 11px; color: #1D8AB5; border-bottom: 1.5px solid #d1d5db; text-transform: uppercase; }
                
                .tabla-rude { width: 100%; border-collapse: collapse; }
                .tabla-rude th, .tabla-rude td { border: 1px solid #e5e7eb; padding: 6px 10px; font-size: 11px; }
                .tabla-rude th { background-color: #f9fafb; font-weight: 700; color: #374151; font-size: 10px; text-transform: uppercase; }

                .firmas { margin-top: 50px; display: flex; justify-content: space-between; text-align: center; }
                .firma-linea { width: 42%; border-top: 1.5px dashed #374151; padding-top: 6px; font-weight: 700; font-size: 11px; color: #374151; }

                @media print {
                    body { padding: 0; }
                    @page { margin: 10mm; size: portrait; }
                }
            </style>
        </head>
        <body>
            <div class="titulo-head">
                <h6>ESTADO PLURINACIONAL DE BOLIVIA - MINISTERIO DE EDUCACIÓN</h6>
                <h4>FORMULARIO RUDE - REGISTRO ÚNICO DE ESTUDIANTES</h4>
                <p>LA INFORMACIÓN RECABADA EN EL RUDE ES CONSIDERADA COMO UNA DECLARACIÓN JURADA</p>
            </div>

            <div class="rude-container">
                <span class="rude-label">(*) CÓDIGO RUDE DEL ESTUDIANTE:</span>
                ${rudeBoxes}
            </div>

            <!-- 1. DATOS DE LA UNIDAD EDUCATIVA -->
            <div class="seccion-box">
                <div class="seccion-header">1. DATOS DE LA UNIDAD EDUCATIVA (*) CÓDIGO SIE: 80122379</div>
                <table class="tabla-rude">
                    <tr>
                        <th style="width: 60%;">1.1 NOMBRE DE LA UNIDAD EDUCATIVA</th>
                        <th style="width: 40%;">1.2 LOCALIDAD / DIRECCIÓN</th>
                    </tr>
                    <tr>
                        <td class="fw-bold">UNIDAD EDUCATIVA PROF. JAIME ALFONSO ESCALANTE GUTIÉRREZ "A"</td>
                        <td>SANTA CRUZ - BOLIVIA</td>
                    </tr>
                </table>
            </div>

            <!-- 2. DATOS DE LA O EL ESTUDIANTE -->
            <div class="seccion-box">
                <div class="seccion-header">2. DATOS DE LA O EL ESTUDIANTE / PARTICIPANTE</div>
                <table class="tabla-rude">
                    <tr>
                        <th style="width: 33%;">2.1 APELLIDO PATERNO</th>
                        <th style="width: 33%;">APELLIDO MATERNO</th>
                        <th style="width: 34%;">NOMBRE(S)</th>
                    </tr>
                    <tr>
                        <td>${data.paterno || '—'}</td>
                        <td>${data.materno || '—'}</td>
                        <td class="fw-bold">${data.nombre || data.estudiante || '—'}</td>
                    </tr>
                </table>
                <table class="tabla-rude" style="border-top: none;">
                    <tr>
                        <th style="width: 20%;">2.2 C.I.</th>
                        <th style="width: 18%;">2.3 FECHA NAC.</th>
                        <th style="width: 18%;">2.4 SEXO</th>
                        <th style="width: 22%;">2.5 ESTADO CIVIL</th>
                        <th style="width: 22%;">2.6 CERT. NACIMIENTO</th>
                    </tr>
                    <tr>
                        <td>${data.ci || '—'}</td>
                        <td>${data.fechaNac || '—'}</td>
                        <td>${data.genero === 'M' ? 'Masculino [X]' : (data.genero === 'F' ? 'Femenino [X]' : 'F [ ] M [ ]')}</td>
                        <td>Soltero/a [X] Otro [ ]</td>
                        <td>Ofic: __ Lib: __ Part: __ Folio: __</td>
                    </tr>
                </table>
                <table class="tabla-rude" style="border-top: none;">
                    <tr>
                        <th>2.7 LUGAR DE NACIMIENTO Y NACIONALIDAD</th>
                    </tr>
                    <tr>
                        <td>País: ${data.nacionalidad || 'Bolivia'} &nbsp;|&nbsp; Depto: ${data.depNac || 'Santa Cruz'} &nbsp;|&nbsp; Provincia: ${data.provNac || 'Andrés Ibáñez'}</td>
                    </tr>
                </table>
            </div>

            <!-- 3. DISCAPACIDAD Y DIFICULTAD DE APRENDIZAJE -->
            <div class="seccion-box">
                <div class="seccion-header">3. DATOS SOBRE DISCAPACIDAD, DIFICULTAD DE APRENDIZAJE Y CONDICIÓN MÉDICA</div>
                <table class="tabla-rude">
                    <tr>
                        <td><strong>3.1 ¿Presenta alguna discapacidad?:</strong> &nbsp; Sí [${hasDisc ? 'X' : ' '}] &nbsp; No [${hasDisc ? ' ' : 'X'}] &nbsp;|&nbsp; <strong>Tipos / Grados:</strong> ${discList}</td>
                    </tr>
                    <tr>
                        <td><strong>3.2 Dificultad de Aprendizaje:</strong> &nbsp; Sí [${hasDif ? 'X' : ' '}] &nbsp; No [${hasDif ? ' ' : 'X'}] &nbsp;|&nbsp; <strong>Cuál(es):</strong> ${difList} &nbsp;|&nbsp; <strong>Diagnóstico Médico:</strong> ${diagStr}</td>
                    </tr>
                    <tr>
                        <td><strong>3.3 Condición Médica:</strong> &nbsp; Sí [${hasCond ? 'X' : ' '}] &nbsp; No [${hasCond ? ' ' : 'X'}] &nbsp;|&nbsp; <strong>Cuál(es):</strong> ${condList}</td>
                    </tr>
                    <tr>
                        <td><strong>3.4 ¿Tiene diagnóstico TEA?:</strong> &nbsp; Sí [ ] &nbsp; No [X] &nbsp;|&nbsp; Tipo 1 [ ] &nbsp; Tipo 2 [ ] &nbsp; Tipo 3 [ ]</td>
                    </tr>
                </table>
            </div>

            <!-- 4. ESTUDIANTES EXTRANJEROS -->
            <div class="seccion-box">
                <div class="seccion-header">4. ESTUDIANTES / PARTICIPANTES EXTRANJEROS</div>
                <table class="tabla-rude">
                    <tr>
                        <td>Documento presentado: CI Extranjero [ ] &nbsp; CI Diplomático [ ] &nbsp; Cert. Nacimiento [ ] &nbsp; DNI [ ] &nbsp; Pasaporte [ ] &nbsp; Dec. Jurada [ ] &nbsp;|&nbsp; N°: ____________</td>
                    </tr>
                </table>
            </div>

            <!-- 4. DATOS DEL PADRE, MADRE O TUTOR(A) -->
            <div class="seccion-box">
                <div class="seccion-header">4. DATOS DEL PADRE, MADRE O TUTOR(A)</div>
                <table class="tabla-rude">
                    <tr>
                        <th style="width: 15%;">TIPO</th>
                        <th style="width: 40%;">APELLIDOS Y NOMBRES</th>
                        <th style="width: 15%;">C.I.</th>
                        <th style="width: 15%;">OCUPACIÓN</th>
                        <th style="width: 15%;">TELÉFONO</th>
                    </tr>
                    <tr>
                        <td class="fw-bold">4.1 PADRE</td>
                        <td>${data.padre ? data.padre.nombre : '—'}</td>
                        <td>${data.padre ? data.padre.ci : '—'}</td>
                        <td>${data.padre ? data.padre.ocupacion : '—'}</td>
                        <td>${data.padre ? data.padre.telefono : '—'}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">4.2 MADRE</td>
                        <td>${data.madre ? data.madre.nombre : '—'}</td>
                        <td>${data.madre ? data.madre.ci : '—'}</td>
                        <td>${data.madre ? data.madre.ocupacion : '—'}</td>
                        <td>${data.madre ? data.madre.telefono : '—'}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">4.3 TUTOR(A)</td>
                        <td>${data.tutor ? data.tutor.nombre : '—'}</td>
                        <td>${data.tutor ? data.tutor.ci : '—'}</td>
                        <td>${data.tutor ? data.tutor.parentesco : '—'}</td>
                        <td>${data.tutor ? data.tutor.telefono : '—'}</td>
                    </tr>
                </table>
            </div>

            <!-- 5. DIRECCIÓN ACTUAL -->
            <div class="seccion-box">
                <div class="seccion-header">5. DIRECCIÓN ACTUAL DE LA O EL ESTUDIANTE</div>
                <table class="tabla-rude">
                    <tr>
                        <th style="width: 20%;">NACIONALIDAD</th>
                        <th style="width: 25%;">DEPARTAMENTO</th>
                        <th style="width: 25%;">PROVINCIA</th>
                        <th style="width: 15%;">ZONA / BARRIO</th>
                        <th style="width: 15%;">CALLE Y NRO</th>
                    </tr>
                    <tr>
                        <td>${data.nacionalidad || 'Boliviana'}</td>
                        <td>${data.depDir || 'Santa Cruz'}</td>
                        <td>${data.provNac || 'Andrés Ibáñez'}</td>
                        <td>${data.barrio || '—'}</td>
                        <td>${data.calle || '—'}</td>
                    </tr>
                </table>
            </div>

            <!-- 6. EDUCACIÓN DIVERSA -->
            <div class="seccion-box">
                <div class="seccion-header">6. EDUCACIÓN DIVERSA</div>
                <table class="tabla-rude">
                    <tr>
                        <td>Contexto diverso: Soldados/Marineros [ ] &nbsp; Encierro [ ] &nbsp; Trabajadoras Hogar [ ] &nbsp; Otros [ ] &nbsp;|&nbsp; Especificar: ____________________</td>
                    </tr>
                </table>
            </div>

            <!-- 7. ASPECTOS SOCIOECONÓMICOS -->
            <div class="seccion-box">
                <div class="seccion-header">7. ASPECTOS SOCIOECONÓMICOS DE LA O EL ESTUDIANTE</div>
                <table class="tabla-rude">
                    <tr>
                        <td><strong>7.1 Idioma niñez:</strong> Castellano [X] &nbsp; Otro: _____ &nbsp;|&nbsp; <strong>Pertenencia cultural:</strong> Ninguno [X] &nbsp; Aymara [ ] &nbsp; Quechua [ ] &nbsp; Guaraní [ ]</td>
                    </tr>
                    <tr>
                        <td><strong>7.2 Salud:</strong> Centro de Salud en zona: Sí [X] No [ ] &nbsp;|&nbsp; Seguro: Sí [ ] No [X] &nbsp;|&nbsp; Acude a: Posta/Hosp. Público [X] &nbsp; Grupo Sangre: _____</td>
                    </tr>
                    <tr>
                        <td><strong>7.3 Ocupación:</strong> Estudiante [X] &nbsp; Trabaja: No [X] &nbsp;|&nbsp; <strong>Acceso a Internet:</strong> Celular [X] &nbsp; Vivienda [X] &nbsp;|&nbsp; Frecuencia: Diaria [X]</td>
                    </tr>
                    <tr>
                        <td><strong>7.4 Transporte:</strong> A pie [ ] &nbsp; Microbús/Trufi [X] &nbsp; Demora: 15 min &nbsp;|&nbsp; <strong>Servicios Básicos:</strong> Agua [X] &nbsp; Alcantarillado [X] &nbsp; Electricidad [X]</td>
                    </tr>
                </table>
            </div>

            <!-- 8. INSCRIPCIÓN GESTIÓN ACTUAL -->
            <div class="seccion-box">
                <div class="seccion-header">8. DATOS DE INSCRIPCIÓN DE LA GESTIÓN ACTUAL</div>
                <table class="tabla-rude">
                    <tr>
                        <th style="width: 25%;">NIVEL</th>
                        <th style="width: 25%;">CURSO / GRADO</th>
                        <th style="width: 16%;">PARALELO</th>
                        <th style="width: 17%;">TURNO</th>
                        <th style="width: 17%;">GESTIÓN</th>
                    </tr>
                    <tr>
                        <td>${data.nivel || '—'}</td>
                        <td class="fw-bold text-primary">${data.cursoGrado || data.curso || '—'}</td>
                        <td>${data.paralelo || '—'}</td>
                        <td>${data.turno || '—'}</td>
                        <td>${data.gestion || '2026'}</td>
                    </tr>
                </table>
                <table class="tabla-rude" style="border-top: none;">
                    <tr>
                        <th style="width: 60%;">REGISTRADO POR (ADMINISTRATIVO)</th>
                        <th style="width: 40%;">FECHA Y HORA DE REGISTRO</th>
                    </tr>
                    <tr>
                        <td>${data.registrado || '—'}</td>
                        <td>${data.fecha || '—'}</td>
                    </tr>
                </table>
            </div>

            <div class="firmas">
                <div class="firma-linea">Firma de la/el Estudiante o Tutor(a)</div>
                <div class="firma-linea">Sello y Firma del Directora/or de la U.E.</div>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 600);
                };
            <\/script>
        </body>
        </html>
    `;

    const ventana = window.open('', '_blank', 'width=900,height=750');
    if (ventana) {
        ventana.document.write(htmlContent);
        ventana.document.close();
    } else {
        let iframe = document.getElementById('iframe_impresion_rude');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'iframe_impresion_rude';
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
        setTimeout(function() {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }, 500);
    }
}
</script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>