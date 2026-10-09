<?php
/**
 * ============================================================
 * dashboard.php  —  Vista del Panel Principal
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Recibe del controlador:
 *   $stats               -> conteos generales
 *   $ultimosEstudiantes  -> últimos registros
 * ============================================================
 */

require_once BASE_PATH . '/View/header.php';

// Tarjetas de estadísticas: [clave, etiqueta, icono, color]
$tarjetas = [
    ['estudiantes',     'Estudiantes',     'bi-people-fill',      'azul'],
    ['docentes',        'Docentes',        'bi-person-badge',     'verde'],
    ['administrativos', 'Administrativos', 'bi-person-workspace', 'morado'],
    ['tutores',         'Padres/Tutores',         'bi-person-heart',     'cian'],
    ['cursos',          'Cursos',          'bi-collection-fill',  'naranja'],
];

if (!empty($esDocente)): ?>
    <!-- ===================== DASHBOARD DEL DOCENTE ===================== -->
    <div class="dash-header mb-4">
        <div>
            <h1 class="page-title"><i class="bi bi-person-workspace text-primary"></i> Panel del Docente</h1>
            <p class="page-subtitle" style="margin-bottom:0;">Bienvenido, <?= htmlspecialchars($_SESSION['nombreCompleto'] ?? $_SESSION['nombre']) ?>. Selecciona una curso de tu horario para registrar asistencia.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge-activo"><i class="bi bi-check-circle-fill"></i> Sesión Docente</span>
        </div>
    </div>

    <!-- Cuadrícula Horaria Semanal del Docente -->
    <?php 
    $horarioSemanalDocente = $horarioSemanalDocente ?? [];
    $periodosDocente = $periodosDocente ?? [];
    require_once BASE_PATH . '/partes/horario_docente_grid.php'; 
    ?>

<?php else: ?>
    <!-- ===================== DASHBOARD ADMINISTRATIVO / GENERAL ===================== -->
    <div class="dash-header">
        <div>
            <h1 class="page-title">Bienvenido, <?= htmlspecialchars($_SESSION['nombreCompleto'] ?? $_SESSION['nombre']) ?></h1>
            <p class="page-subtitle" style="margin-bottom:0;">Resumen general del sistema académico</p>
        </div>
        <span class="badge-activo"><i class="bi bi-check-circle-fill"></i> Sistema Activo</span>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="stats-grid">
        <?php foreach ($tarjetas as [$clave, $etiqueta, $icono, $color]): ?>
            <div class="stat-card">
                <div class="stat-icon <?= $color ?>">
                    <i class="bi <?= $icono ?>"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats[$clave] ?? 0) ?></h3>
                    <p><?= $etiqueta ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>


    <!-- Información General -->
    <div class="card-panel mb-4">
        <h2 class="panel-titulo">Información General</h2>
        <ul class="info-lista">
            <li><span>Unidad Educativa:</span> Colegio Jaime Alfonso Escalante Gutiérrez "A"</li>
            <li><span>Fecha:</span> <?= date('d/m/Y') ?></li>
            <li><span>Hora:</span> <?= date('H:i:s') ?></li>
        </ul>
    </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/View/footer.php'; ?>
