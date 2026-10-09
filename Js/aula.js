/* ============================================================
   aula.js — Interacciones dinámicas y validación del CRUD Aula
   Sistema Académico JAEGASA
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    // Buscar todos los formularios de aula con validador
    const formularios = document.querySelectorAll('.form-aula-validador');

    formularios.forEach(function (form) {
        const inputNombre = form.querySelector('.input-nombre-aula');

        form.addEventListener('submit', function (event) {
            let esValido = true;

            // Validar nombre
            if (inputNombre) {
                const nombreValor = inputNombre.value.trim();
                if (nombreValor === '' || nombreValor.length > 30) {
                    inputNombre.classList.add('is-invalid');
                    esValido = false;
                } else {
                    inputNombre.classList.remove('is-invalid');
                }
            }

            if (!esValido) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        // Limpiar el estado inválido al escribir
        if (inputNombre) {
            inputNombre.addEventListener('input', function () {
                if (this.value.trim() !== '' && this.value.length <= 30) {
                    this.classList.remove('is-invalid');
                }
            });
        }
    });

    // Limpieza de inputs al cerrar modal de creación
    const modalNuevo = document.getElementById('modalNuevo');
    if (modalNuevo) {
        modalNuevo.addEventListener('hidden.bs.modal', function () {
            const form = modalNuevo.querySelector('form');
            if (form) {
                form.reset();
                const inputsInvalidos = form.querySelectorAll('.is-invalid');
                inputsInvalidos.forEach(inp => inp.classList.remove('is-invalid'));
            }
        });
    }
});
