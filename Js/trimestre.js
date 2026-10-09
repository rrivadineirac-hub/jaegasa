/* ============================================================
   trimestre.js — Interacciones dinámicas y validación del CRUD Trimestre
   Sistema Académico JAEGASA
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    // Buscar todos los formularios de trimestre con validador
    const formularios = document.querySelectorAll('.form-trimestre-validador');

    formularios.forEach(function (form) {
        const inputInicio  = form.querySelector('.input-fecha-inicio');
        const inputFin     = form.querySelector('.input-fecha-fin');
        const inputNombre  = form.querySelector('.input-nombre-trimestre');
        const duracionInfo = form.querySelector('.duracion-preview');

        // Función para calcular la diferencia de días entre fechas
        function calcularDuracion() {
            if (!inputInicio || !inputFin || !duracionInfo) return;
            const valInicio = inputInicio.value;
            const valFin    = inputFin.value;

            if (valInicio && valFin) {
                const d1 = new Date(valInicio);
                const d2 = new Date(valFin);

                if (d1 > d2) {
                    duracionInfo.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> La fecha de inicio no puede ser mayor a la fecha de fin.</span>';
                    inputFin.classList.add('is-invalid');
                } else {
                    const diffTime = Math.abs(d2 - d1);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                    duracionInfo.innerHTML = `<span class="text-success"><i class="bi bi-calendar-check-fill"></i> Duración estimada: <strong>${diffDays} días</strong> (aprox. ${Math.round(diffDays / 7)} semanas).</span>`;
                    inputFin.classList.remove('is-invalid');
                }
            } else {
                duracionInfo.innerHTML = '';
            }
        }

        if (inputInicio && inputFin) {
            inputInicio.addEventListener('change', calcularDuracion);
            inputFin.addEventListener('change', calcularDuracion);
            // Ejecutar al cargar por si hay valores preestablecidos
            calcularDuracion();
        }

        form.addEventListener('submit', function (event) {
            let esValido = true;

            // Validar nombre
            if (inputNombre) {
                const nombreVal = inputNombre.value.trim();
                if (nombreVal === '' || nombreVal.length > 50) {
                    inputNombre.classList.add('is-invalid');
                    esValido = false;
                } else {
                    inputNombre.classList.remove('is-invalid');
                }
            }

            // Validar fechas
            if (inputInicio && inputFin && inputInicio.value && inputFin.value) {
                if (inputInicio.value > inputFin.value) {
                    inputFin.classList.add('is-invalid');
                    esValido = false;
                } else {
                    inputFin.classList.remove('is-invalid');
                }
            }

            if (!esValido) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    });

    // Resetear modales al cerrarse
    const modales = document.querySelectorAll('.modal');
    modales.forEach(function (modal) {
        modal.addEventListener('hidden.bs.modal', function () {
            const form = modal.querySelector('form');
            if (form && modal.id === 'modalNuevo') {
                form.reset();
                const inputsInvalidos = form.querySelectorAll('.is-invalid');
                inputsInvalidos.forEach(inp => inp.classList.remove('is-invalid'));
                const duracionInfo = form.querySelector('.duracion-preview');
                if (duracionInfo) duracionInfo.innerHTML = '';
            }
        });
    });
});
