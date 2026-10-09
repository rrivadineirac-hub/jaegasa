<?php
/**
 * vasistencia.php — Vista de Asistencia General
 * Recibe: $cursos, $idCurso, $fecha, $estudiantes, $flash
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$accionGuardar = BASE_URL . 'index.php?c=asistencia&a=guardar';
$fotoDefault   = BASE_URL . 'inagenes/Estudiante/default.png';
$rutaFoto      = fn($foto) => (!empty($foto)) ? BASE_URL . 'inagenes/Estudiante/' . rawurlencode($foto) : $fotoDefault;

$rolSesionActual   = (int) ($_SESSION['idRoles'] ?? 0);
$cargoSesionActual = strtolower((string) ($_SESSION['cargo'] ?? $_SESSION['rol'] ?? ''));
$esDocente         = ($rolSesionActual === 2 || str_contains($cargoSesionActual, 'docente') || str_contains($cargoSesionActual, 'profesor'));

// ------------------------------------------------------------
// Gestión activa
// ------------------------------------------------------------
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

<!-- Carga del archivo CSS específico para Asistencia (asistencia.css) y Lector QR -->
<link rel="stylesheet" href="<?= BASE_URL ?>Css/asistencia.css?v=<?= @filemtime(BASE_PATH . '/Css/asistencia.css') ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>Css/asistencia_qr.css?v=<?= @filemtime(BASE_PATH . '/Css/asistencia_qr.css') ?>">

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
        <h1 class="page-title"><i class="bi bi-calendar-check"></i> Control de Asistencia</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Paso de lista diario general por cursos</p>
    </div>
</div>

<?php if (!empty($flash) && ($flash['tipo'] ?? '') === 'warning_centro'): ?>
    <div class="modal fade show d-block my-4" tabindex="-1" style="background: rgba(0,0,0,0.4); border-radius: 12px; z-index: 1055;">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
            <div class="modal-content shadow-lg border-0 text-center p-4" style="border-radius: 16px;">
                <div class="mb-3 text-warning">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 3.5rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Aviso de Asistencia</h5>
                <p class="fs-6 text-secondary mb-4 px-2" style="line-height: 1.5;">
                    <?= $e($flash['mensaje']) ?>
                </p>
                <div class="d-flex justify-content-center">
                    <button type="button" class="btn btn-warning px-4 py-2 text-white fw-bold shadow-sm" onclick="this.closest('.modal').remove();" style="border-radius: 8px;">
                        <i class="bi bi-check-circle-fill me-1"></i> Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Panel de Filtro de Búsqueda -->
<div class="card-panel mb-4">
    <form method="get" action="<?= BASE_URL ?>index.php" id="formFiltroAsistencia">
        <input type="hidden" name="c" value="asistencia">
        
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="form-seccion mb-0 fw-semibold text-jaegasa"><i class="bi bi-filter-square"></i> Criterios de Búsqueda</h6>
            <div class="text-end">
                <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
                <div class="text-primary fw-semibold fs-6">
                    <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualAnio ?? date('Y')) ?>
                </div>
            </div>
        </div>
        <p class="text-muted small mb-3">Seleccione el curso, paralelo, nivel y turno para cargar la planilla de asistencia. La gestión se asigna automáticamente.</p>
        
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-semibold">Curso / Grado *</label>
                <select class="form-select curso-selector" data-tipo="grado" required <?= empty($cursosUnicos) ? 'disabled' : '' ?>>
                    <option value="">Seleccione...</option>
                    <?php foreach ($cursosUnicos as $id => $nomG): ?>
                        <option value="<?= $e($id) ?>" <?= ($cursoSeleccionado && $cursoSeleccionado['cursoNombre'] === $id) ? 'selected' : '' ?>><?= $e($nomG) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold">Paralelo *</label>
                <select class="form-select curso-selector" data-tipo="paralelo" required <?= empty($paralelosUnicos) ? 'disabled' : '' ?>>
                    <option value="">Seleccione...</option>
                    <?php foreach ($paralelosUnicos as $idP => $nomP): ?>
                        <option value="<?= (int) $idP ?>" <?= ($cursoSeleccionado && (int)$cursoSeleccionado['idParalelo'] === (int)$idP) ? 'selected' : '' ?>><?= $e($nomP) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold">Nivel *</label>
                <select class="form-select curso-selector" data-tipo="nivel" required <?= empty($nivelesUnicos) ? 'disabled' : '' ?>>
                    <option value="">Seleccione...</option>
                    <?php foreach ($nivelesUnicos as $idN => $nomN): ?>
                        <option value="<?= (int) $idN ?>" <?= ($cursoSeleccionado && (int)$cursoSeleccionado['idNivel'] === (int)$idN) || (count($nivelesUnicos) === 1 && !$cursoSeleccionado) ? 'selected' : '' ?>><?= $e($nomN) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold">Turno *</label>
                <select class="form-select curso-selector" data-tipo="turno" required <?= empty($turnosUnicos) ? 'disabled' : '' ?>>
                    <option value="">Seleccione...</option>
                    <?php foreach ($turnosUnicos as $idT => $nomT): ?>
                        <option value="<?= (int) $idT ?>" <?= ($cursoSeleccionado && (int)$cursoSeleccionado['idTurno'] === (int)$idT) || (count($turnosUnicos) === 1 && !$cursoSeleccionado) ? 'selected' : '' ?>><?= $e($nomT) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Fecha *</label>
                <input type="date" name="fecha" value="<?= $e($fecha) ?>" class="form-control input-fecha-asist" required max="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <input type="hidden" name="idCurso" value="<?= $idCurso > 0 ? $idCurso : '' ?>" class="idCursoResuelto" required>
        
        <div class="alert alert-warning mt-3 mb-0 cursoNoEncontrado" hidden>
            <i class="bi bi-exclamation-triangle-fill"></i>
            No existe un curso con esa combinación de nivel, turno y paralelo en la gestión <?= $e($gestionActualAnio) ?>.
        </div>
        
        <?php if (empty($cursosGestionActual)): ?>
            <small class="text-danger d-block mt-2">No hay cursos disponibles en la gestión <?= $e($gestionActualAnio) ?>. Registre un curso primero.</small>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" id="btnCargarAsistenciaDocentes" class="btn btn-primary px-4 py-2" disabled>
                    <i class="bi bi-cloud-arrow-down-fill me-1"></i> Cargar asistencia del curso
                </button>
                <?php if (!$esDocente): ?>
                <button type="button" id="btnImprimirAsistenciaCurso" class="btn btn-success px-4 py-2 shadow-sm" onclick="imprimirListadoAsistencia()" title="Imprimir reporte oficial de asistencia del curso" <?= $idCurso > 0 ? '' : 'style="display: none;"' ?>>
                    <i class="bi bi-printer-fill me-1"></i> Imprimir Asistencia del Curso
                </button>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <button type="button" id="btnLimpiarFiltros" class="btn btn-outline-secondary px-4 py-2">
                    <i class="bi bi-eraser-fill me-1"></i> Limpiar
                </button>
                <button type="submit" class="btn btn-jaegasa px-4 py-2">
                    <i class="bi bi-arrow-clockwise"></i> Cargar Planilla de Asistencia
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Contenedor Principal: Planilla / Ayuda (Izquierda) + Lector QR (Derecha) -->
<div class="row g-4">
    <!-- Columna Izquierda (Planilla o Instrucciones) -->
    <div class="col-lg-8 col-md-12">
        <?php if ($idCurso > 0): ?>
            <!-- Tarjetas de Resumen Estadístico (Dinámicas) -->
            <div class="row g-3 mb-4 text-center">
                <div class="col-6 col-md-3">
                    <div class="card-stat-asist stat-presente">
                        <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="stat-info">
                            <span class="stat-titulo">Presentes</span>
                            <span class="stat-numero" id="stat-presentes">0</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card-stat-asist stat-retraso">
                        <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
                        <div class="stat-info">
                            <span class="stat-titulo">Retrasos</span>
                            <span class="stat-numero" id="stat-retrasos">0</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card-stat-asist stat-falta">
                        <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
                        <div class="stat-info">
                            <span class="stat-titulo">Faltas</span>
                            <span class="stat-numero" id="stat-faltas">0</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card-stat-asist stat-licencia">
                        <div class="stat-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <div class="stat-info">
                            <span class="stat-titulo">Licencias</span>
                            <span class="stat-numero" id="stat-licencias">0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de Planilla Manual -->
            <div class="card-panel">
                <form action="<?= $accionGuardar ?>" method="post" id="formAsistenciaMasiva">
                    <input type="hidden" name="idCurso" value="<?= $idCurso ?>">
                    <input type="hidden" name="fecha" value="<?= $e($fecha) ?>">
                    
                    <div class="tabla-toolbar d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3 flex-grow-1" style="max-width: 400px;">
                            <div class="buscador mb-0 w-100">
                                <i class="bi bi-search"></i>
                                <input type="text" id="buscadorEstudianteAsist" placeholder="Buscar estudiante por nombre, CI o RUDE...">
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <?php if (!$esDocente): ?>
                            <button type="button" class="btn btn-sm btn-success shadow-sm px-3 fw-semibold" onclick="imprimirListadoAsistencia()" title="Imprimir reporte de asistencia del curso">
                                <i class="bi bi-printer-fill me-1"></i> Imprimir Asistencia
                            </button>
                            <?php endif; ?>
                            <span class="fw-semibold text-muted">Encontrados: <strong class="text-jaegasa" id="total-alumnos"><?= count($estudiantes) ?></strong></span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-asistencia" id="tablaAsistenciaEstudiantes">
                            <thead>
                                <tr>
                                    <th style="width: 5%" class="text-center">#</th>
                                    <th style="width: 40%">Estudiante</th>
                                    <th style="width: 30%">Estado de Asistencia</th>
                                    <th style="width: 25%">Observación / Hora de Llegada</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($estudiantes)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="bi bi-people-fill fs-3"></i><br> No hay estudiantes inscritos activos en este curso.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($estudiantes as $i => $est): ?>
                                        <?php 
                                        $idIns = (int) $est['idInscripcion'];
                                        $estadoActual = $est['estado'] ?? null;
                                        $horaActual = $est['horaLlegada'] ? substr($est['horaLlegada'], 0, 5) : '';
                                        $obsActual = $est['observacion'] ?: '';
                                        ?>
                                        <tr class="fila-estudiante-asistencia" data-inscripcion="<?= $idIns ?>" data-rude="<?= $e($est['rude']) ?>">
                                            <td class="text-center fw-semibold text-muted"><?= $i + 1 ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?= $rutaFoto($est['foto'] ?? '') ?>" alt="" class="tabla-foto flex-shrink-0" onerror="this.src='<?= $fotoDefault ?>'">
                                                    <div class="estudiante-perfil-celda flex-grow-1">
                                                        <span class="estudiante-nombre fw-bold text-dark text-uppercase d-block mb-0"><?= $e($est['estudianteNombre']) ?></span>
                                                        <span class="estudiante-meta text-muted small d-block">
                                                            RUDE: <?= $e($est['rude'] ?: 'S/R') ?> <span class="text-secondary mx-1">|</span> CI: <?= !empty($est['ci']) ? $e($est['ci']) : 'S/C' ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="grupo-opciones-asist">
                                                    <label class="opcion-asist op-A op-deshabilitado" title="Asistió (No se puede marcar manualmente)">
                                                        <input type="radio" name="asistencias[<?= $idIns ?>][estado]" value="A" <?= $estadoActual === 'A' ? 'checked' : '' ?> class="radio-estado-asist" disabled>
                                                        <span>A</span>
                                                    </label>
                                                    <label class="opcion-asist op-R" title="Retraso">
                                                        <input type="radio" name="asistencias[<?= $idIns ?>][estado]" value="R" <?= $estadoActual === 'R' ? 'checked' : '' ?> class="radio-estado-asist">
                                                        <span>R</span>
                                                    </label>
                                                    <label class="opcion-asist op-F op-deshabilitado" title="Falta (No se puede marcar manualmente)">
                                                        <input type="radio" name="asistencias[<?= $idIns ?>][estado]" value="F" <?= $estadoActual === 'F' ? 'checked' : '' ?> class="radio-estado-asist" disabled>
                                                        <span>F</span>
                                                    </label>
                                                    <label class="opcion-asist op-L" title="Licencia">
                                                        <input type="radio" name="asistencias[<?= $idIns ?>][estado]" value="L" <?= $estadoActual === 'L' ? 'checked' : '' ?> class="radio-estado-asist">
                                                        <span>L</span>
                                                    </label>
                                                    <?php if ($estadoActual): ?>
                                                        <input type="hidden" name="asistencias[<?= $idIns ?>][estado_existente]" value="<?= $e($estadoActual) ?>">
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="inputs-opcionales-asist">
                                                    <div class="input-grupo-hora-llegada <?= $estadoActual === 'R' ? 'mostrar' : '' ?>">
                                                        <label class="small text-muted mb-1 block"><i class="bi bi-clock"></i> Hora de llegada *</label>
                                                        <input type="time" name="asistencias[<?= $idIns ?>][horaLlegada]" value="<?= $e($horaActual) ?>" class="form-control form-control-sm input-hora-llegada" <?= $estadoActual === 'R' ? 'required' : '' ?>>
                                                    </div>
                                                    
                                                    <div class="input-grupo-observacion <?= $estadoActual === 'L' ? 'mostrar' : '' ?>">
                                                        <label class="small text-muted mb-1 block"><i class="bi bi-pencil-square"></i> Detalle / Motivo</label>
                                                        <input type="text" name="asistencias[<?= $idIns ?>][observacion]" value="<?= $e($obsActual) ?>" class="form-control form-control-sm input-observacion-detalle" placeholder="Ej: Presenta justificativo médico" maxlength="150">
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($estudiantes)): ?>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-jaegasa btn-lg px-5 py-3 shadow-sm btn-guardar-asist">
                                <i class="bi bi-cloud-check-fill"></i> Guardar Control de Asistencia
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        <?php else: ?>
            <!-- Estado Inicial Sin Curso Seleccionado -->
            <div class="card-panel text-center py-5">
                <div class="py-4">
                    <i class="bi bi-qr-code-scan text-jaegasa display-3"></i>
                    <h4 class="mt-3 text-dark fw-bold">Escanear Código QR del Estudiante o Seleccionar Curso</h4>
                    <p class="text-muted max-w-md mx-auto fs-6">
                        Puede escanear directamente el código QR de un estudiante con la cámara de la derecha para registrar su asistencia y abrir automáticamente su curso, o bien seleccionar un curso en el panel de búsqueda superior.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Columna Derecha: Lector QR (Siempre Habilitado) -->
    <div class="col-lg-4 col-md-12">
        <div class="card-panel position-sticky" style="top: 24px;">
            <h6 class="form-seccion mb-3 fw-bold text-jaegasa"><i class="bi bi-camera-fill"></i> LECTOR DE CÓDIGO QR</h6>
            
            <div class="row g-2 mb-3">
                <div class="col-12">
                    <label class="form-label fw-semibold text-muted small mb-1">Dispositivo de Cámara</label>
                    <select id="selectCamaras" class="form-select form-select-sm">
                        <option value="">Cargando cámaras...</option>
                    </select>
                </div>
            </div>
            
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold text-muted small mb-1">Fecha Control</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="<?= date('d/m/Y', strtotime($fecha)) ?>" readonly>
                    <input type="hidden" id="fechaControl" value="<?= $e($fecha) ?>">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold text-muted small mb-1">Hora Límite *</label>
                    <input type="time" id="horaLimite" class="form-control form-control-sm" value="08:00">
                </div>
            </div>

            <!-- Visor del Escáner -->
            <div class="posicion-escaner">
                <div id="reader-container">
                    <div id="reader"></div>
                    <div class="laser-scanner"></div>
                </div>
                
                <div id="scanner-overlay" class="scanner-overlay" hidden>
                    <div class="overlay-content text-center">
                        <div class="overlay-icon mb-2"></div>
                        <h6 class="overlay-title mb-0" style="font-size: 0.9rem; font-weight: 700; max-width: 200px; margin: 0 auto; word-wrap: break-word;"></h6>
                    </div>
                </div>
            </div>

            <!-- Footer del Lector -->
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <span class="text-muted small" style="font-size: 0.75rem;"><i class="bi bi-info-circle"></i> Sostenga el código QR frente a la cámara.</span>
                <div class="d-flex gap-1">
                    <button type="button" id="btnIniciarEscaner" class="btn btn-sm btn-success px-2 py-1">
                        <i class="bi bi-play-fill"></i> Iniciar
                    </button>
                    <button type="button" id="btnPausarEscaner" class="btn btn-sm btn-danger px-2 py-1" disabled>
                        <i class="bi bi-stop-fill"></i> Detener
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Carga de Scripts JavaScript -->
<script src="<?= BASE_URL ?>Js/asistencia.js?v=<?= @filemtime(BASE_PATH . '/Js/asistencia.js') ?>"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="<?= BASE_URL ?>Js/asistencia_qr_manual.js?v=<?= @filemtime(BASE_PATH . '/Js/asistencia_qr_manual.js') ?>"></script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>
