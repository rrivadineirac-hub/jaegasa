<?php
/**
 * ============================================================
 * menuLateral.php  —  Barra lateral (menú de navegación)
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Parte reutilizable incluida por header.php. Muestra el perfil
 * del usuario y las opciones del menú según su rol.
 *
 * Variables esperadas (definidas en header.php):
 *   $rol           -> idRoles del usuario en sesión
 *   $rolNombre     -> nombre del rol
 *   $paginaActiva  -> controlador activo (para resaltar el ítem)
 * ============================================================
 */

// Menú por rol. Cada ítem: [etiqueta, controlador, icono, roles permitidos]
// roles = arreglo de idRoles que pueden ver la opción (vacío = todos).
$menu = [
    ['Inicio',         'dashboard',      'bi-speedometer2',     []],
    ['Inscripciones',  'inscripcion',    'bi-clipboard-check',  [1]],
    ['Asistencia',     'asistencia',     'bi-calendar-check',   [1]],
    ['Docentes',       'docente',        'bi-person-badge',     [1]],
    ['Títulos',        'titulo',         'bi-award-fill',       [1]],
    ['Asig. Docentes',   'asignacion_docente', 'bi-person-badge-fill', [1]],
    ['Especialidades', 'especialidad',   'bi-mortarboard',      [1]],
    ['Administrativos','administrativo', 'bi-person-workspace', [1]],
    ['Categorías',     'categoria',      'bi-tags-fill',        [1]],
    ['Estudiantes',    'estudiante',     'bi-people-fill',      [1]],
    ['Tutores',        'tutor',          'bi-person-heart',     [1]],
    ['Direcciones',    'direccion',      'bi-geo-alt-fill',     [1]],
    ['Dificultades',   'dificultad',     'bi-puzzle-fill',      [1]],
    ['Discapacidades', 'discapacidad',   'bi-universal-access', [1]],
    ['Cond. Médicas',  'condicion',      'bi-heart-pulse-fill', [1]],
    ['Credenciales / QR', 'qr',          'bi-qr-code',          [1]],
    ['Cursos',         'curso',          'bi-collection-fill',  [1]],
    ['Paralelos',      'paralelo',       'bi-diagram-3-fill',   [1]],
    ['Niveles',        'nivel',          'bi-layers-fill',      [1]],
    ['Turnos',         'turno',          'bi-clock-fill',       [1]],
    ['Materias',         'materia',          'bi-book-half',        [1]],
    ['Malla Curricular', 'malla_curricular', 'bi-journal-bookmark-fill', [1]], 
    ['Aulas',          'aula',           'bi-door-closed-fill', [1]],
    ['Horarios',       'horario',        'bi-calendar-week',    [1]],
    ['Asistencia Docente', 'asistencia_docente', 'bi-calendar-check',   [2]],
    ['Gestiones',      'gestion',        'bi-calendar3',        [1]],
    ['Trimestres',     'trimestre',      'bi-calendar3-range',  [1]],
    ['Importar Excel', 'importar_estudiantes', 'bi-file-earmark-excel-fill', [1]],
];
?>

<?php
$savedScrollCookie = isset($_COOKIE['sidebarScrollTop']) ? (int)$_COOKIE['sidebarScrollTop'] : null;
?>
<!-- Backdrop del menú (solo visible en móvil) -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- ===================== Menú lateral ===================== -->
<aside class="sidebar" id="sidebar">

    <!-- Identidad institucional -->
    <div class="sidebar-perfil">
        <div class="perfil-logo">
            <img src="<?= BASE_URL ?>inagenes/logos/Logo.png" alt="Logo del colegio"
                 onerror="this.onerror=null; this.src='<?= BASE_URL ?>inagenes/logos/Logo.jpeg';">
            <span class="perfil-online" title="En línea"></span>
        </div>
        <div class="perfil-usuario">Prof. Jaime Alfonso Escalante Gutierres "A"</div>
        <div class="perfil-rol">Ciencia &bull; Honor &bull; Disciplina</div>
    </div>

    <div class="sidebar-titulo">Menú Principal</div>

    <nav class="sidebar-nav" id="sidebarNav">
        <?php foreach ($menu as [$etiqueta, $ctrl, $icono, $rolesPermitidos]): ?>
            <?php if (empty($rolesPermitidos) || in_array($rol, $rolesPermitidos, true)): ?>
                <a href="<?= BASE_URL ?>index.php?c=<?= $ctrl ?>"
                   class="nav-item <?= $paginaActiva === $ctrl ? 'activo' : '' ?>">
                    <i class="bi <?= $icono ?>"></i>
                    <span><?= $etiqueta ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <script>
        (function() {
            var nav = document.getElementById('sidebarNav');
            if (nav) {
                var saved = sessionStorage.getItem('sidebarScrollTop');
                if (saved !== null && saved !== "") {
                    nav.scrollTop = parseInt(saved, 10);
                }
            }
        })();
    </script>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>index.php?c=login&a=salir" class="nav-item salir">
            <i class="bi bi-box-arrow-right"></i>
            <span>Cerrar sesión</span>
        </a>
    </div>
</aside>
