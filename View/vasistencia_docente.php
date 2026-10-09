<?php
/**
 * vasistencia_docente.php — Vista Asistencia (Docente)
 * Recibe: $clasesDelDia, $idHorario, $fecha, $estudiantes, $flash
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$accionGuardar = BASE_URL . 'index.php?c=asistencia_docente&a=guardar';
$fotoDefault   = BASE_URL . 'inagenes/Estudiante/default.png';
$rutaFoto      = fn($foto) => (!empty($foto)) ? BASE_URL . 'inagenes/Estudiante/' . rawurlencode($foto) : $fotoDefault;

$horarioActivo = null;
foreach ($clasesDelDia as $c) {
    if ((int) $c['idHorario'] === $idHorario) {
        $horarioActivo = $c;
        break;
    }
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>Css/asistencia.css?v=<?= @filemtime(BASE_PATH . '/Css/asistencia.css') ?>">
<?php if ($horarioActivo): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>Css/asistencia_qr.css?v=<?= @filemtime(BASE_PATH . '/Css/asistencia_qr.css') ?>">
<?php endif; ?>

<div class="dash-header">
    <div>
        <h1 class="page-title"><i class="bi bi-clipboard2-check-fill"></i> Pasar Asistencia</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Registro de asistencia por materia, según tu horario del día</p>
    </div>
</div>



<!-- Selector de fecha y Hora actual -->
<div class="card-panel mb-3 d-flex justify-content-between align-items-end">
    <div class="d-flex align-items-end gap-3">
        <div>
            <label class="form-label fw-semibold mb-1"><i class="bi bi-calendar-event"></i> Fecha de Hoy</label>
            <input type="date" value="<?= $e($fecha) ?>" class="form-control bg-light" readonly disabled title="La fecha está fija para el día de hoy">
            <input type="hidden" name="fecha" value="<?= $e($fecha) ?>">
        </div>
    </div>
    <div class="d-flex align-items-center gap-4">
        <div class="text-end">
            <label class="form-label fw-bold mb-0 text-muted small">Gestión Escolar</label>
            <div class="text-primary fw-semibold fs-6">
                <i class="bi bi-calendar2-check"></i> <?= $e($gestionActualAnio ?? date('Y')) ?>
            </div>
        </div>
        <div class="text-end">
            <div class="fw-semibold text-muted mb-1" style="font-size:11px;">Hora Actual</div>
            <div class="h5 mb-0 text-primary fw-bold" id="relojActual" style="letter-spacing: 1px;">--:--:--</div>
        </div>
    </div>
</div>

<!-- Cuadrícula Horaria Semanal del Docente -->
<?php 
$horarioSemanalDocente = $horarioSemanalDocente ?? [];
$periodosDocente = $periodosDocente ?? [];
require_once BASE_PATH . '/partes/horario_docente_grid.php'; 
?>

<!-- Planilla de asistencia del horario seleccionado -->
<?php if ($horarioActivo): ?>
    <div id="seccionAsistencia" style="scroll-margin-top: 20px;">
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

        <!-- Contenedor de dos columnas: Planilla manual y Lector QR -->
        <div class="row g-4">
            <!-- Columna de la Planilla Manual (Izquierda) -->
            <div class="col-lg-8 col-md-12">
                <div class="card-panel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="form-seccion mb-0">
                            <i class="bi bi-people-fill"></i> <?= $e($horarioActivo['materiaNombre']) ?> — <?= $e($horarioActivo['cursoNombre']) ?> "<?= $e($horarioActivo['paraleloNombre']) ?>"
                        </h6>
                    </div>
                    
                    <form action="<?= $accionGuardar ?>" method="post" id="formAsistenciaMasiva">
                        <input type="hidden" name="idHorario" value="<?= (int) $horarioActivo['idHorario'] ?>">
                        <input type="hidden" name="fecha" value="<?= $e($fecha) ?>">
                        
                        <div class="tabla-toolbar d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3 flex-grow-1" style="max-width: 400px;">
                                <div class="buscador mb-0 w-100">
                                    <i class="bi bi-search"></i>
                                    <input type="text" id="buscadorEstudianteAsist" placeholder="Buscar estudiante por nombre, CI o RUDE...">
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <span class="fw-semibold text-muted">Encontrados: <strong class="text-jaegasa" id="total-alumnos"><?= count($estudiantes) ?></strong></span>
                                <div class="acciones-rapidas">
                                    <button type="button" class="btn btn-sm btn-outline-success" id="btnMarcarTodosAsistio">
                                        <i class="bi bi-check-all"></i> Marcar todos como "Asistió"
                                    </button>
                                </div>
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
                                            $estadoActual = $est['estadoUI'] ?? null; // Inicia vacía por defecto
                                            $horaActual = $est['horaAsistencia'] ? substr($est['horaAsistencia'], 0, 5) : '';
                                            $obsActual = $est['observacion'] ?? '';
                                            ?>
                                            <tr class="fila-estudiante-asistencia" data-inscripcion="<?= $idIns ?>" data-rude="<?= $e($est['rude']) ?>">
                                                <td class="text-center fw-semibold text-muted"><?= $i + 1 ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="<?= $rutaFoto($est['foto']) ?>" alt="" class="tabla-foto flex-shrink-0" onerror="this.src='<?= $fotoDefault ?>'">
                                                        <div class="estudiante-perfil-celda flex-grow-1">
                                                            <span class="estudiante-nombre fw-bold text-dark text-uppercase d-block mb-0"><?= $e($est['estudianteNombre']) ?></span>
                                                            <span class="estudiante-meta text-muted small d-block">
                                                                RUDE: <?= $e($est['rude'] ?: 'S/R') ?> <span class="text-secondary mx-1">|</span> CI: <?= $est['ci'] ? $e($est['ci']) : 'S/C' ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <!-- Opciones de Asistencia con Estilo Premium -->
                                                    <div class="grupo-opciones-asist">
                                                        <label class="opcion-asist op-A" title="Asistió">
                                                            <input type="radio" name="asistencias[<?= $idIns ?>][estado]" value="A" <?= $estadoActual === 'A' ? 'checked' : '' ?> class="radio-estado-asist">
                                                            <span>A</span>
                                                        </label>
                                                        <label class="opcion-asist op-R" title="Retraso">
                                                            <input type="radio" name="asistencias[<?= $idIns ?>][estado]" value="R" <?= $estadoActual === 'R' ? 'checked' : '' ?> class="radio-estado-asist">
                                                            <span>R</span>
                                                        </label>
                                                        <label class="opcion-asist op-F" title="Falta">
                                                            <input type="radio" name="asistencias[<?= $idIns ?>][estado]" value="F" <?= $estadoActual === 'F' ? 'checked' : '' ?> class="radio-estado-asist">
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
                                                    <!-- Inputs dinámicos según estado de asistencia -->
                                                    <div class="inputs-opcionales-asist">
                                                        <!-- Input hora llegada (solo visible en Retraso) -->
                                                        <div class="input-grupo-hora-llegada <?= $estadoActual === 'R' ? 'mostrar' : '' ?>">
                                                            <label class="small text-muted mb-1 block"><i class="bi bi-clock"></i> Hora de llegada *</label>
                                                            <input type="time" name="asistencias[<?= $idIns ?>][horaLlegada]" value="<?= $e($horaActual) ?>" class="form-control form-control-sm input-hora-llegada" <?= $estadoActual === 'R' ? 'required' : '' ?>>
                                                        </div>
                                                        
                                                        <!-- Input observación general (visible en Licencia y otros) -->
                                                        <div class="input-grupo-observacion <?= in_array($estadoActual, ['L', 'F']) ? 'mostrar' : '' ?>">
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
            </div>

            <!-- Columna del Lector QR (Derecha) -->
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
                            <input type="time" id="horaLimite" class="form-control form-control-sm" value="<?= $horarioActivo && !empty($horarioActivo['horaInicio']) ? date('H:i', strtotime($horarioActivo['horaInicio']) + 21 * 60) : '08:00' ?>">
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
    </div>

    <!-- Desplazamiento automático suave a la sección de asistencia -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const seccion = document.getElementById('seccionAsistencia');
            if (seccion) {
                setTimeout(function () {
                    seccion.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 120);
            }
        });
    </script>
<?php endif; ?>

<script src="<?= BASE_URL ?>Js/asistencia.js?v=<?= @filemtime(BASE_PATH . '/Js/asistencia.js') ?>"></script>

<script>
    function actualizarReloj() {
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const segundos = String(ahora.getSeconds()).padStart(2, '0');
        const reloj = document.getElementById('relojActual');
        if (reloj) {
            reloj.textContent = `${horas}:${minutos}:${segundos}`;
        }
    }
    setInterval(actualizarReloj, 1000);
    actualizarReloj();
</script>

<?php if ($horarioActivo): ?>
    <!-- Biblioteca html5-qrcode para el escaneo de cámara -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <!-- Script de control del escáner integrado en planilla manual -->
    <script src="<?= BASE_URL ?>Js/asistencia_qr_manual.js?v=<?= @filemtime(BASE_PATH . '/Js/asistencia_qr_manual.js') ?>"></script>
<?php endif; ?>

<?php require_once BASE_PATH . '/View/footer.php'; ?>