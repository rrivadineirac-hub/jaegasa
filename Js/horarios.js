/* ============================================================
   horarios.js — Validación y lógica del CRUD Horarios
   Sistema Académico JAEGASA
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    const formularios = document.querySelectorAll('.form-horario-validador');

    formularios.forEach(function (form) {
        const inputInicio = form.querySelector('input[name="horaInicio"]');
        const inputFin = form.querySelector('input[name="horaFin"]');

        form.addEventListener('submit', function (event) {
            let esValido = true;

            // Limpiar errores previos
            limpiarErrores(form);

            if (inputInicio && inputFin) {
                const hi = inputInicio.value;
                const hf = inputFin.value;

                if (hi && hf) {
                    // Comparación de cadenas en formato 24h 'HH:MM' o 'HH:MM:SS'
                    if (hf <= hi) {
                        inputFin.classList.add('is-invalid-time');
                        mostrarError(inputFin, 'La hora de fin debe ser posterior a la hora de inicio.');
                        esValido = false;
                    }
                }
            }

            if (!esValido) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        // Limpiar errores al cambiar los valores de las horas
        if (inputInicio && inputFin) {
            [inputInicio, inputFin].forEach(input => {
                input.addEventListener('change', () => {
                    inputInicio.classList.remove('is-invalid-time');
                    inputFin.classList.remove('is-invalid-time');
                    limpiarErrores(form);
                });
            });
        }
    });

    function mostrarError(elemento, mensaje) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'time-error-message';
        errorDiv.innerHTML = `<i class="bi bi-exclamation-circle-fill"></i> ${mensaje}`;
        elemento.parentNode.appendChild(errorDiv);
    }

    function limpiarErrores(form) {
        const errores = form.querySelectorAll('.time-error-message');
        errores.forEach(err => err.remove());
    }
});
