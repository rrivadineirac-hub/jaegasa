/**
 * ============================================================
 * tutor_autocompletar.js
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Escucha la escritura y edición en tiempo real de campos CI de tutor
 * (padre_ci, madre_ci, apo_ci, ci) tanto en modal NUEVO como EDITAR,
 * consulta vía AJAX si ya existe en la tabla Tutor, autocompleta los datos
 * y verifica si el rol encontrado (Papá / Mamá / Apoderado) coincide
 * con el bloque correspondiente.
 * 
 * Si hay incongruencia de rol (p.ej. CI de Mamá en el bloque de Papá o viceversa):
 *   1. Muestra un mensaje de advertencia destacado.
 *   2. Limpia los campos del bloque para que no se muestren datos erróneos.
 *   3. Deshabilita el botón de Guardar y Siguiente mientras persista el error.
 *   4. Al corregir/borrar el CI, se rehabilita el botón.
 * ============================================================
 */

(function () {
    let timeouts = new WeakMap();

    function esCampoCiTutor(el) {
        if (!el || el.tagName !== 'INPUT') return false;
        const name = el.getAttribute('name') || '';
        if (name === 'padre_ci' || name === 'madre_ci' || name === 'apo_ci') return true;
        if (name === 'ci' && (window.location.search.includes('c=tutor') || !document.querySelector('.wizard-form'))) return true;
        return false;
    }

    function determinarRolTutor(tutor) {
        const p  = (tutor.parentesco || '').toLowerCase().trim();
        const tp = (tutor.tipo || '').toLowerCase().trim();

        if (p.includes('padre') || p.includes('papá') || p.includes('papa') ||
            tp.includes('padre') || tp.includes('papá') || tp.includes('papa')) {
            return 'padre';
        }
        if (p.includes('madre') || p.includes('mamá') || p.includes('mama') ||
            tp.includes('madre') || tp.includes('mamá') || tp.includes('mama')) {
            return 'madre';
        }
        return 'apo';
    }

    function actualizarEstadoBotones(input) {
        const form = input.closest('form') || input.closest('.modal');
        if (!form) return;

        const hayError = !!form.querySelector('[data-error-rol-tutor="1"]');
        const botones = form.querySelectorAll('.wiz-save, .wiz-next, button[type="submit"]');

        botones.forEach(function (btn) {
            btn.disabled = hayError;
        });
    }

    function buscarTutor(input) {
        if (!input) return;
        const ci = input.value.trim();
        const nameAttr = input.getAttribute('name') || '';

        // Determina el prefijo ('padre_', 'madre_', 'apo_' o '')
        let prefix = '';
        if (nameAttr.includes('_ci')) {
            prefix = nameAttr.replace('_ci', '') + '_';
        }

        const bloque = input.closest('[data-bloque]') || input.closest('.wiz-step') || input.closest('.modal-body') || input.closest('form');
        if (!bloque) return;

        let msgEl = input.parentNode.querySelector('.msg-tutor-ci');
        const sufijos = ['apPaterno', 'apMaterno', 'nombre', 'telefono', 'correo', 'parentesco', 'tipo', 'profesion', 'ocupacion', 'direccion'];

        // Función para limpiar campos del bloque
        const limpiarCampos = function (soloAutocompletados) {
            sufijos.forEach(function (sufijo) {
                const target = bloque.querySelector('[name="' + prefix + sufijo + '"]');
                if (target) {
                    if (!soloAutocompletados || target.dataset.fueAutocompletado === '1') {
                        target.value = '';
                        delete target.dataset.fueAutocompletado;
                        target.classList.remove('is-valid');
                    }
                }
            });
        };

        const resetErrorState = function () {
            delete input.dataset.errorRolTutor;
            input.classList.remove('is-invalid');
            actualizarEstadoBotones(input);
        };

        // Si tiene menos de 3 caracteres o se borra el CI
        if (ci.length < 3) {
            if (msgEl) msgEl.remove();
            resetErrorState();
            limpiarCampos(ci.length === 0 ? false : true);
            return;
        }

        // Determinar URL relativa
        const origin = window.location.origin;
        const path = window.location.pathname;
        const folder = path.substring(0, path.lastIndexOf('/') + 1);
        const baseUrl = window.BASE_URL || (origin + folder);

        fetch(baseUrl + 'index.php?c=tutor&a=buscar_ci&ci=' + encodeURIComponent(ci))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.ok && data.encontrado && data.tutor) {
                    const t = data.tutor;
                    const rolDetectado = determinarRolTutor(t);
                    const nombreCompletoTutor = (t.nombre + ' ' + (t.apPaterno || '')).trim();

                    let esError = false;
                    let mensajeBadge = '';
                    let claseBadge = 'text-success';

                    if (prefix === 'padre_' && rolDetectado === 'madre') {
                        esError = true;
                        claseBadge = 'text-danger';
                        mensajeBadge = '<i class="bi bi-exclamation-triangle-fill"></i> Atención: Este CI pertenece a una <strong>Mamá</strong> (' + nombreCompletoTutor + ').';
                    } else if (prefix === 'madre_' && rolDetectado === 'padre') {
                        esError = true;
                        claseBadge = 'text-danger';
                        mensajeBadge = '<i class="bi bi-exclamation-triangle-fill"></i> Atención: Este CI pertenece a un <strong>Papá</strong> (' + nombreCompletoTutor + ').';
                    }

                    if (esError) {
                        // En caso de error de incongruencia de rol:
                        // 1. Marcar error en el input
                        input.dataset.errorRolTutor = '1';
                        input.classList.add('is-invalid');

                        // 2. Limpiar campos para NO mostrar datos erróneos
                        limpiarCampos(false);

                        // 3. Deshabilitar los botones de envío/siguiente
                        actualizarEstadoBotones(input);

                        // 4. Mostrar mensaje de error en rojo
                        if (!msgEl) {
                            msgEl = document.createElement('small');
                            input.parentNode.appendChild(msgEl);
                        }
                        msgEl.className = claseBadge + ' fw-bold d-block mt-1 msg-tutor-ci';
                        msgEl.innerHTML = mensajeBadge;
                        return;
                    }

                    // Si NO es error, resetear estado de error y re-evaluar botones
                    resetErrorState();

                    const setVal = function (sufijo, val) {
                        const target = bloque.querySelector('[name="' + prefix + sufijo + '"]');
                        if (target) {
                            target.value = val || '';
                            target.dataset.fueAutocompletado = '1';
                            target.classList.add('is-valid');
                            setTimeout(function () { target.classList.remove('is-valid'); }, 2000);
                        }
                    };

                    setVal('apPaterno', t.apPaterno);
                    setVal('apMaterno', t.apMaterno);
                    setVal('nombre',    t.nombre);
                    setVal('telefono',  t.telefono);
                    setVal('correo',    t.correo);
                    setVal('parentesco',t.parentesco);
                    setVal('tipo',      t.tipo);
                    setVal('profesion', t.profesion);
                    setVal('ocupacion', t.ocupacion);
                    setVal('direccion', t.direccion);

                    // Mensajes informativos
                    if (prefix === 'padre_') {
                        if (rolDetectado === 'apo') {
                            claseBadge = 'text-info';
                            const rel = t.parentesco || t.tipo || 'Tutor';
                            mensajeBadge = '<i class="bi bi-info-circle-fill"></i> Este CI está registrado como <strong>' + rel + '</strong> (' + nombreCompletoTutor + ').';
                        } else {
                            claseBadge = 'text-success';
                            mensajeBadge = '<i class="bi bi-check-circle-fill"></i> Tutor registrado encontrado (Papá).';
                        }
                    } else if (prefix === 'madre_') {
                        if (rolDetectado === 'apo') {
                            claseBadge = 'text-info';
                            const rel = t.parentesco || t.tipo || 'Tutor';
                            mensajeBadge = '<i class="bi bi-info-circle-fill"></i> Este CI está registrado como <strong>' + rel + '</strong> (' + nombreCompletoTutor + ').';
                        } else {
                            claseBadge = 'text-success';
                            mensajeBadge = '<i class="bi bi-check-circle-fill"></i> Tutor registrado encontrado (Mamá).';
                        }
                    } else if (prefix === 'apo_') {
                        if (rolDetectado === 'padre') {
                            claseBadge = 'text-info';
                            mensajeBadge = '<i class="bi bi-info-circle-fill"></i> Este CI pertenece al <strong>Papá</strong> (' + nombreCompletoTutor + ').';
                        } else if (rolDetectado === 'madre') {
                            claseBadge = 'text-info';
                            mensajeBadge = '<i class="bi bi-info-circle-fill"></i> Este CI pertenece a la <strong>Mamá</strong> (' + nombreCompletoTutor + ').';
                        } else {
                            claseBadge = 'text-success';
                            const rel = t.parentesco || t.tipo || 'Apoderado';
                            mensajeBadge = '<i class="bi bi-check-circle-fill"></i> Tutor registrado encontrado (' + rel + ').';
                        }
                    } else {
                        claseBadge = 'text-success';
                        mensajeBadge = '<i class="bi bi-check-circle-fill"></i> Tutor registrado encontrado.';
                    }

                    if (!msgEl) {
                        msgEl = document.createElement('small');
                        input.parentNode.appendChild(msgEl);
                    }
                    msgEl.className = claseBadge + ' fw-bold d-block mt-1 msg-tutor-ci';
                    msgEl.innerHTML = mensajeBadge;

                } else {
                    if (msgEl) msgEl.remove();
                    resetErrorState();
                    limpiarCampos(true);
                }
            })
            .catch(function (err) {
                console.error('Error al consultar tutor por CI:', err);
            });
    }

    // Delegación global de eventos de escritura ('input', 'blur' y 'change')
    document.addEventListener('input', function (e) {
        if (!esCampoCiTutor(e.target)) return;

        const input = e.target;
        const ci = input.value.trim();

        // Al borrar el CI, limpiar inmediatamente cualquier mensaje de error y rehabilitar botones
        if (ci.length === 0) {
            if (timeouts.has(input)) {
                clearTimeout(timeouts.get(input));
            }
            let msgEl = input.parentNode.querySelector('.msg-tutor-ci');
            if (msgEl) msgEl.remove();
            delete input.dataset.errorRolTutor;
            input.classList.remove('is-invalid');
            actualizarEstadoBotones(input);
            return;
        }

        if (timeouts.has(input)) {
            clearTimeout(timeouts.get(input));
        }

        const timer = setTimeout(function () {
            buscarTutor(input);
        }, 250);

        timeouts.set(input, timer);
    }, true);

    document.addEventListener('blur', function (e) {
        if (esCampoCiTutor(e.target)) {
            buscarTutor(e.target);
        }
    }, true);

    document.addEventListener('change', function (e) {
        if (esCampoCiTutor(e.target)) {
            buscarTutor(e.target);
        }
    }, true);

    // Al cerrar, ocultar o abrir un modal (o hacer clic en Cancelar / data-bs-dismiss),
    // limpiar cualquier error de rol de tutor, restaurar CI original (en edición) y rehabilitar los botones
    function resetearModal(modal) {
        if (!modal) return;
        modal.querySelectorAll('.msg-tutor-ci').forEach(function (msg) {
            msg.remove();
        });
        modal.querySelectorAll('input').forEach(function (input) {
            delete input.dataset.errorRolTutor;
            input.classList.remove('is-invalid');
            input.classList.remove('is-valid');
            if (input.dataset.ciOriginal !== undefined) {
                input.value = input.dataset.ciOriginal;
            }
        });
        modal.querySelectorAll('.wiz-save, .wiz-next, button[type="submit"]').forEach(function (btn) {
            btn.disabled = false;
        });
    }

    document.addEventListener('hidden.bs.modal', function (e) {
        resetearModal(e.target);
    });

    document.addEventListener('show.bs.modal', function (e) {
        const modal = e.target;
        if (modal) {
            modal.querySelectorAll('input[name="ci"], input[name="padre_ci"], input[name="madre_ci"], input[name="apo_ci"]').forEach(function (input) {
                if (input.dataset.ciOriginal === undefined && input.value.trim() !== '') {
                    input.dataset.ciOriginal = input.value;
                }
            });
            resetearModal(modal);
        }
    });

    document.addEventListener('click', function (e) {
        const btnClose = e.target.closest('[data-bs-dismiss="modal"]');
        if (btnClose) {
            const modal = btnClose.closest('.modal');
            resetearModal(modal);
        }
    });
})();
