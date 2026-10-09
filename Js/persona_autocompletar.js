/**
 * ============================================================
 * persona_autocompletar.js
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Escucha la edición en tiempo real de campos CI en modales de
 * Docente y Administrativo (inputs con clase .input-persona-ci o name="ci").
 * 
 * Si el CI ya pertenece a otro registro (Docente o Administrativo):
 *   1. Muestra un mensaje de advertencia destacado en rojo:
 *      "⚠️ Atención: Este CI ya pertenece a [Nombre Completo]."
 *   2. NO rellena los campos del formulario para no mostrar datos erróneos.
 *   3. Deshabilita el botón de Guardar mientras persista el error.
 *   4. Al borrar/corregir el CI, hacer clic en Cancelar o salir del modal,
 *      se quita el mensaje y se habilita nuevamente el botón de Guardar.
 * ============================================================
 */

(function () {
    let timeouts = new WeakMap();

    function esPaginaDocenteOAdmi() {
        const url = window.location.search;
        return url.includes('c=docente') || url.includes('c=administrativo');
    }

    function obtenerControladorActual() {
        const url = window.location.search;
        if (url.includes('c=docente')) return 'docente';
        if (url.includes('c=administrativo')) return 'administrativo';
        return '';
    }

    function actualizarBotonGuardar(form) {
        if (!form) return;
        const hayError = !!form.querySelector('[data-error-ci-existente="1"]');
        const btnSave  = form.querySelector('button[type="submit"]');
        if (btnSave) {
            btnSave.disabled = hayError;
        }
    }

    function resetearEstadoError(input, form) {
        if (!input) return;
        delete input.dataset.errorCiExistente;
        input.classList.remove('is-invalid');
        const msgEl = input.parentNode.querySelector('.msg-persona-ci');
        if (msgEl) msgEl.remove();
        actualizarBotonGuardar(form || input.closest('form') || input.closest('.modal'));
    }

    function buscarPersonaPorCi(input) {
        if (!input) return;
        const ci = input.value.trim();
        const ctrl = obtenerControladorActual();
        const form = input.closest('form') || input.closest('.modal');
        if (!form || !ctrl) return;

        // Si tiene menos de 3 caracteres o se borró el campo
        if (ci.length < 3) {
            resetearEstadoError(input, form);
            return;
        }

        // Obtener el ID actual de la entidad en el modal para no marcar como duplicado a sí mismo
        const idInputName = ctrl === 'docente' ? 'idDocente' : 'idAdministrativo';
        const idInput = form.querySelector('input[name="' + idInputName + '"]');
        const idActual = idInput ? parseInt(idInput.value, 10) : 0;

        const origin = window.location.origin;
        const path = window.location.pathname;
        const folder = path.substring(0, path.lastIndexOf('/') + 1);
        const baseUrl = window.BASE_URL || (origin + folder);

        fetch(baseUrl + 'index.php?c=' + ctrl + '&a=buscar_ci&ci=' + encodeURIComponent(ci))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const entityKey = ctrl === 'docente' ? 'docente' : 'administrativo';
                const primaryKey = ctrl === 'docente' ? 'idDocente' : 'idAdministrativo';

                if (data.ok && data.encontrado && data[entityKey]) {
                    const p = data[entityKey];
                    const idEncontrado = parseInt(p[primaryKey] || 0, 10);

                    // Si el CI pertenece a OTRA persona (es un duplicado)
                    if (idEncontrado > 0 && idEncontrado !== idActual) {
                        const nombreCompleto = ((p.nombre || '') + ' ' + (p.apPaterno || '') + ' ' + (p.apMaterno || '')).trim();

                        // 1. Marcar error
                        input.dataset.errorCiExistente = '1';
                        input.classList.add('is-invalid');

                        // 2. Deshabilitar botón de guardar
                        actualizarBotonGuardar(form);

                        // 3. Mostrar mensaje en rojo
                        let msgEl = input.parentNode.querySelector('.msg-persona-ci');
                        if (!msgEl) {
                            msgEl = document.createElement('small');
                            input.parentNode.appendChild(msgEl);
                        }
                        msgEl.className = 'text-danger fw-bold d-block mt-1 msg-persona-ci';
                        msgEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Atención: Este CI ya pertenece a <strong>' + nombreCompleto + '</strong>.';

                        return;
                    }
                }

                // Si no se encuentra duplicado o es la misma persona en edición
                resetearEstadoError(input, form);
            })
            .catch(function (err) {
                console.error('Error al consultar persona por CI:', err);
            });
    }

    // Al escribir en el campo CI (con respuesta inmediata si se borra el valor)
    document.addEventListener('input', function (e) {
        const el = e.target;
        if (!esPaginaDocenteOAdmi()) return;
        if (!el || el.tagName !== 'INPUT') return;
        if (el.getAttribute('name') !== 'ci' && !el.classList.contains('input-persona-ci')) return;

        const ci = el.value.trim();

        // Al borrar el CI, limpiar inmediatamente cualquier mensaje de error y rehabilitar botón
        if (ci.length === 0) {
            if (timeouts.has(el)) {
                clearTimeout(timeouts.get(el));
            }
            resetearEstadoError(el);
            return;
        }

        if (timeouts.has(el)) {
            clearTimeout(timeouts.get(el));
        }

        const timer = setTimeout(function () {
            buscarPersonaPorCi(el);
        }, 250);

        timeouts.set(el, timer);
    }, true);

    document.addEventListener('blur', function (e) {
        const el = e.target;
        if (!esPaginaDocenteOAdmi()) return;
        if (!el || el.tagName !== 'INPUT') return;
        if (el.getAttribute('name') === 'ci' || el.classList.contains('input-persona-ci')) {
            buscarPersonaPorCi(el);
        }
    }, true);

    // Al cerrar, ocultar o abrir un modal (o hacer clic en Cancelar / data-bs-dismiss),
    // limpiar el estado de error, restaurar el CI original (en editar) y rehabilitar el botón de Guardar
    function resetearModalPersona(modal) {
        if (!modal) return;
        modal.querySelectorAll('.msg-persona-ci').forEach(function (msg) {
            msg.remove();
        });
        modal.querySelectorAll('input').forEach(function (input) {
            delete input.dataset.errorCiExistente;
            input.classList.remove('is-invalid');
            if (input.dataset.ciOriginal !== undefined) {
                input.value = input.dataset.ciOriginal;
            }
        });
        modal.querySelectorAll('button[type="submit"]').forEach(function (btn) {
            btn.disabled = false;
        });
    }

    document.addEventListener('hidden.bs.modal', function (e) {
        resetearModalPersona(e.target);
    });

    document.addEventListener('show.bs.modal', function (e) {
        const modal = e.target;
        if (modal) {
            modal.querySelectorAll('input[name="ci"], input.input-persona-ci').forEach(function (input) {
                if (input.dataset.ciOriginal === undefined && input.value.trim() !== '') {
                    input.dataset.ciOriginal = input.value;
                }
            });
            resetearModalPersona(modal);
        }
    });

    document.addEventListener('click', function (e) {
        const btnClose = e.target.closest('[data-bs-dismiss="modal"]');
        if (btnClose) {
            const modal = btnClose.closest('.modal');
            resetearModalPersona(modal);
        }
    });

})();
