/**
 * materia.js — Validación e interacciones dinámicas del CRUD Materia
 * Sistema Académico JAEGASA
 */

document.addEventListener('DOMContentLoaded', function () {
    const formularios = document.querySelectorAll('.form-materia-validador');

    formularios.forEach(function (form) {
        const inputNombre = form.querySelector('.input-nombre-materia');
        const inputSigla  = form.querySelector('.input-sigla-materia');

        form.addEventListener('submit', function (event) {
            let esValido = true;

            // Validar nombre
            if (inputNombre) {
                const nombreVal = inputNombre.value.trim();
                if (nombreVal === '' || nombreVal.length > 80) {
                    inputNombre.classList.add('is-invalid');
                    esValido = false;
                } else {
                    inputNombre.classList.remove('is-invalid');
                }
            }

            // Validar sigla
            if (inputSigla) {
                const siglaVal = inputSigla.value.trim();
                if (siglaVal.length > 15) {
                    inputSigla.classList.add('is-invalid');
                    esValido = false;
                } else {
                    inputSigla.classList.remove('is-invalid');
                }
            }

            if (!esValido) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        // Eventos input para remover clases invalidas al corregir
        if (inputNombre) {
            inputNombre.addEventListener('input', function () {
                if (this.value.trim() !== '' && this.value.length <= 80) {
                    this.classList.remove('is-invalid');
                }
            });
        }

        if (inputSigla) {
            inputSigla.addEventListener('input', function () {
                if (this.value.trim().length <= 15) {
                    this.classList.remove('is-invalid');
                }
            });
        }
    });

    // Limpieza de inputs al cerrar el modal de creación
    const modalNuevo = document.getElementById('modalNuevo');
    if (modalNuevo) {
        modalNuevo.addEventListener('hidden.bs.modal', function () {
            const form = modalNuevo.querySelector('form');
            if (form) {
                form.reset();
                const invalidElements = form.querySelectorAll('.is-invalid');
                invalidElements.forEach(function (el) {
                    el.classList.remove('is-invalid');
                });
            }
        });
    }
});
