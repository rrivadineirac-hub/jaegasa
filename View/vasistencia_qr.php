<?php
/**
 * vasistencia_qr.php — Vista del Escáner de Asistencia por Código QR
 * Sistema Académico JAEGASA
 */

require_once BASE_PATH . '/View/header.php';

$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>

<!-- Carga del archivo CSS específico para el Escáner QR -->
<link rel="stylesheet" href="<?= BASE_URL ?>Css/asistencia_qr.css?v=<?= @filemtime(BASE_PATH . '/Css/asistencia_qr.css') ?>">

<div class="dash-header">
    <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-3">
        <div>
            <h1 class="page-title"><i class="bi bi-qr-code-scan"></i> Registro por Código QR</h1>
            <p class="page-subtitle" style="margin-bottom:0;">Control de asistencia diaria automatizado mediante escáner</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>index.php?c=asistencia" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Planilla Manual
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Columna Izquierda: Escáner de Cámara -->
    <div class="col-lg-6 col-md-12">
        <div class="card-panel shadow-sm mb-4">
            <h5 class="form-seccion mb-3 fw-bold text-jaegasa"><i class="bi bi-camera-fill"></i> Lector de Código QR</h5>
            
            <!-- Configuraciones del Escáner -->
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-sm-12">
                    <label class="form-label fw-semibold">Dispositivo de Cámara</label>
                    <select id="selectCamaras" class="form-select">
                        <option value="environment" selected>Cámara Principal / Trasera (Auto)</option>
                        <option value="user">Cámara Frontal (Auto)</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold">Fecha Control</label>
                    <input type="date" id="fechaControl" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold" title="Estudiantes que escaneen después de esta hora se marcarán como Retraso">Hora Límite *</label>
                    <input type="time" id="horaLimite" class="form-control" value="08:00">
                </div>
            </div>

            <!-- Área del Video del Escáner -->
            <div class="posicion-escaner">
                <div id="reader-container">
                    <div id="reader"></div>
                    <div class="laser-scanner"></div>
                </div>
                
                <div id="scanner-overlay" class="scanner-overlay" hidden>
                    <div class="overlay-content text-center">
                        <div class="overlay-icon mb-2"></div>
                        <h5 class="overlay-title mb-0"></h5>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted small"><i class="bi bi-info-circle"></i> Sostenga el código QR del estudiante frente a la cámara.</span>
                <div>
                    <button id="btnIniciarEscaner" class="btn btn-sm btn-success me-2">
                        <i class="bi bi-play-fill"></i> Iniciar
                    </button>
                    <button id="btnPausarEscaner" class="btn btn-sm btn-danger" disabled>
                        <i class="bi bi-stop-fill"></i> Detener
                    </button>
                </div>
            </div>
        </div>

        <!-- Registro Manual Contingente -->
        <div class="card-panel shadow-sm">
            <h6 class="form-seccion mb-2 fw-semibold text-secondary"><i class="bi bi-keyboard"></i> Entrada Manual Contingente</h6>
            <p class="text-muted small mb-3">Si la cámara no logra leer la credencial, escriba el código RUDE aquí:</p>
            <form id="formManualRude" class="row g-2 align-items-center">
                <div class="col">
                    <input type="text" id="manualRudeInput" class="form-control text-uppercase" placeholder="Ej: 80560234202302A" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-jaegasa">
                        <i class="bi bi-check-circle"></i> Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Columna Derecha: Tarjeta del Estudiante y Registro Reciente -->
    <div class="col-lg-6 col-md-12">
        <!-- Último Estudiante Escaneado -->
        <div class="card-panel shadow-sm mb-4 text-center">
            <h5 class="form-seccion mb-3 fw-bold text-jaegasa text-start"><i class="bi bi-person-badge-fill"></i> Estudiante Escaneado</h5>
            
            <div id="tarjetaEstudiante" class="tarjeta-estudiante-contenedor">
                <div class="tarjeta-placeholder py-5">
                    <i class="bi bi-qr-code text-muted display-4"></i>
                    <h5 class="text-secondary mt-3">Esperando lectura...</h5>
                    <p class="text-muted small px-4">Cuando escanee un código QR válido, la credencial escolar del estudiante se cargará aquí automáticamente.</p>
                </div>
            </div>
        </div>

        <!-- Historial de la Sesión Actual -->
        <div class="card-panel shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="form-seccion mb-0 fw-bold text-jaegasa"><i class="bi bi-list-stars"></i> Escaneos de la Sesión</h5>
                <span class="badge bg-jaegasa px-2 py-1 fs-6" id="contadorSesion">0</span>
            </div>
            
            <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                <table class="table table-hover align-middle" id="tablaHistorialSesion">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Foto</th>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="historialSesionBody">
                        <tr id="historialVacio">
                            <td colspan="5" class="text-center text-muted py-4">
                                No se han registrado lecturas en esta sesión.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Biblioteca html5-qrcode cargada desde CDN -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<!-- Script interactivo específico del Escáner QR -->
<script src="<?= BASE_URL ?>Js/asistencia_qr.js?v=<?= @filemtime(BASE_PATH . '/Js/asistencia_qr.js') ?>"></script>

<?php require_once BASE_PATH . '/View/footer.php'; ?>
