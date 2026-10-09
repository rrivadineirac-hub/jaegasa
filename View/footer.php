        </main>
        <!-- Fin del contenido -->

        <footer class="app-footer">
            <small>&copy; <?= date('Y') ?> Unidad Educativa Prof. Jaime Alfonso Escalante Gutiérrez "A"— Sistema Académico JAEGASA</small>
        </footer>

    </div><!-- /app-main -->
</div><!-- /app-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>Js/curso_paralelo_filtro.js?v=<?= @filemtime(BASE_PATH . '/Js/curso_paralelo_filtro.js') ?>"></script>
<script src="<?= BASE_URL ?>Js/foto_preview.js?v=<?= @filemtime(BASE_PATH . '/Js/foto_preview.js') ?>"></script>
<script>
    // ------------------------------------------------------------
    // Control responsivo del menú lateral
    //   Escritorio (> 768px): el botón colapsa/expande el sidebar.
    //   Móvil     (<= 768px): el botón abre/cierra un drawer con backdrop.
    // ------------------------------------------------------------
    (function () {
        const sidebar    = document.getElementById('sidebar');
        const btn        = document.getElementById('btnToggle');
        const backdrop   = document.getElementById('sidebarBackdrop');
        const sidebarNav = document.querySelector('.sidebar-nav');
        const MOVIL      = () => window.matchMedia('(max-width: 768px)').matches;

        // Preservar la posición exacta de scroll del menú lateral desde Inicio hasta Importar Excel
        if (sidebarNav) {
            let isNavigating = false;

            const restoreScroll = () => {
                const saved = sessionStorage.getItem('sidebarScrollTop');
                if (saved !== null && saved !== "") {
                    sidebarNav.scrollTop = parseInt(saved, 10);
                }
            };

            restoreScroll();
            requestAnimationFrame(restoreScroll);
            setTimeout(restoreScroll, 50);
            setTimeout(restoreScroll, 150);

            window.addEventListener('load', restoreScroll);
            window.addEventListener('pageshow', restoreScroll);

            sidebarNav.addEventListener('scroll', () => {
                if (!isNavigating) {
                    sessionStorage.setItem('sidebarScrollTop', sidebarNav.scrollTop);
                }
            }, { passive: true });

            document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => {
                item.addEventListener('click', () => {
                    sessionStorage.setItem('sidebarScrollTop', sidebarNav.scrollTop);
                    isNavigating = true;
                    if (MOVIL()) cerrarDrawer();
                });
            });
        }

        function abrirDrawer() {
            sidebar?.classList.add('abierto');
            backdrop?.classList.add('visible');
        }
        function cerrarDrawer() {
            sidebar?.classList.remove('abierto');
            backdrop?.classList.remove('visible');
        }

        btn?.addEventListener('click', () => {
            if (MOVIL()) {
                sidebar.classList.contains('abierto') ? cerrarDrawer() : abrirDrawer();
            } else {
                sidebar.classList.toggle('colapsado');
            }
        });

        // Cerrar el drawer al tocar el fondo oscuro
        backdrop?.addEventListener('click', cerrarDrawer);

        // Al pasar de móvil a escritorio, limpiar el estado del drawer
        window.addEventListener('resize', () => { if (!MOVIL()) cerrarDrawer(); });
    })();

    // ------------------------------------------------------------
    // Conversión automática en tiempo real de entradas de texto a MAYÚSCULAS
    // ------------------------------------------------------------
    ['input', 'change'].forEach(function (evt) {
        document.addEventListener(evt, function (e) {
            if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) {
                const type = (e.target.type || '').toLowerCase();
                const omitidos = ['password', 'file', 'date', 'time', 'datetime-local', 'month', 'week', 'number', 'range', 'color', 'checkbox', 'radio', 'hidden', 'image'];
                if (omitidos.includes(type)) return;
                const start = e.target.selectionStart;
                const end = e.target.selectionEnd;
                const upper = e.target.value.toUpperCase();
                if (e.target.value !== upper) {
                    e.target.value = upper;
                    if (start !== null && end !== null) {
                        e.target.setSelectionRange(start, end);
                    }
                }
            }
        }, true);
    // ------------------------------------------------------------
    // Desactivar sugerencias de autocompletado del navegador ("Información guardada") en todo el proyecto
    // ------------------------------------------------------------
    (function () {
        function deshabilitarSugerencias(el) {
            if (!el) return;
            if (el.tagName === 'FORM') {
                el.setAttribute('autocomplete', 'off');
            } else if (el.tagName === 'INPUT') {
                const type = (el.type || '').toLowerCase();
                const omitidos = ['file', 'date', 'time', 'datetime-local', 'month', 'week', 'range', 'color', 'checkbox', 'radio', 'hidden', 'image', 'button', 'submit', 'reset'];
                if (!omitidos.includes(type)) {
                    el.setAttribute('autocomplete', 'off');
                    el.setAttribute('autocorrect', 'off');
                    el.setAttribute('autocapitalize', 'off');
                    el.setAttribute('spellcheck', 'false');
                    el.setAttribute('aria-autocomplete', 'none');
                }
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('form, input').forEach(deshabilitarSugerencias);
            });
        } else {
            document.querySelectorAll('form, input').forEach(deshabilitarSugerencias);
        }

        document.addEventListener('focusin', function (e) {
            if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'FORM')) {
                deshabilitarSugerencias(e.target);
            }
        }, true);
    })();
</script>
</body>
</html>

