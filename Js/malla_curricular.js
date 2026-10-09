/**
 * malla_curricular.js — Validación y manejo dinámico del CRUD Malla Curricular
 * Sistema Académico JAEGASA
 */

document.addEventListener('DOMContentLoaded', function () {
    const MAX_PERIODOS_CURSO = 35;
    const formularios = document.querySelectorAll('.form-malla-validador');

    // Helper para notificaciones flotantes de límite
    function mostrarToastNotificacion(mensaje, tipo = 'warning') {
        const prev = document.getElementById('toastFlotanteSystemJS');
        if (prev) prev.remove();

        const icon = tipo === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-exclamation-octagon-fill';
        const toast = document.createElement('div');
        toast.id = 'toastFlotanteSystemJS';
        toast.className = `toast-flash-derecho alert alert-${tipo} alert-dismissible fade show`;
        toast.style.zIndex = '99999';
        toast.innerHTML = `
            <div class="d-flex align-items-center gap-2 me-3">
                <i class="bi ${icon} fs-5"></i>
                <span class="fw-semibold">${mensaje}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        `;
        document.body.appendChild(toast);

        setTimeout(function () {
            if (toast && toast.parentNode) {
                toast.classList.remove('show');
                setTimeout(function () { if (toast.parentNode) toast.remove(); }, 350);
            }
        }, 4500);
    }

    // Función principal para recalcular y controlar los periodos asignados en un formulario de modal
    function actualizarControlPeriodosFormulario(form, inputActual = null) {
        const badgeContador = form.querySelector('.contador-periodos-disponibles');
        const spanRestantes = form.querySelector('.periodos-restantes');
        const textoContador = form.querySelector('.texto-contador-periodos');

        if (!badgeContador) return;

        const checkedMaterias = form.querySelectorAll('.check-materia-select:checked');
        let sumaOtrosInputs = 0;

        checkedMaterias.forEach(function (chk) {
            const row = chk.closest('.materia-item-row');
            if (!row) return;
            const input = row.querySelector('.input-carga-materia');
            if (input && input !== inputActual && !input.disabled) {
                const val = parseInt(input.value, 10);
                if (!isNaN(val) && val > 0) {
                    sumaOtrosInputs += val;
                }
            }
        });

        let valInputActual = 0;
        if (inputActual && !inputActual.disabled) {
            const valOriginal = parseInt(inputActual.value, 10);
            if (!isNaN(valOriginal) && valOriginal > 0) {
                const maxDisponibleParaInput = MAX_PERIODOS_CURSO - sumaOtrosInputs;
                if (valOriginal > maxDisponibleParaInput) {
                    const valAjustado = Math.max(0, maxDisponibleParaInput);
                    inputActual.value = valAjustado > 0 ? valAjustado : '';
                    valInputActual = valAjustado;
                    mostrarToastNotificacion('Ya llegó al máximo de periodos para este curso por semana (35 periodos)', 'warning');
                } else {
                    valInputActual = valOriginal;
                }
            }
        }

        const sumaTotal = sumaOtrosInputs + valInputActual;
        const periodosDisponibles = Math.max(0, MAX_PERIODOS_CURSO - sumaTotal);

        if (spanRestantes) {
            spanRestantes.textContent = periodosDisponibles;
        }

        if (badgeContador) {
            if (periodosDisponibles === 0) {
                badgeContador.className = 'badge bg-warning-subtle text-dark border border-warning px-3 py-2 rounded-pill fw-bold contador-periodos-disponibles';
                if (textoContador) {
                    textoContador.innerHTML = 'Límite alcanzado (<strong class="text-danger fs-6">0</strong> Periodos disponibles)';
                }
            } else {
                badgeContador.className = 'badge bg-light text-dark border px-3 py-2 rounded-pill fw-bold contador-periodos-disponibles';
                if (textoContador) {
                    textoContador.innerHTML = 'Tiene <strong class="periodos-restantes text-primary fs-6">' + periodosDisponibles + '</strong> Periodos para asignar al curso';
                }
            }
        }
    }

    formularios.forEach(function (form) {
        // Elementos para la resolución dinámica de Curso
        const selGrado = form.querySelector('[data-tipo="grado"]');
        const selParalelo = form.querySelector('[data-tipo="paralelo"]');
        const selNivel = form.querySelector('[data-tipo="nivel"]');
        const selTurno = form.querySelector('[data-tipo="turno"]');
        const selectCurso = form.querySelector('.idCursoResuelto'); // Campo hidden
        const avisoCurso = form.querySelector('.cursoNoEncontrado');

        const selectMateria = form.querySelector('.select-materia'); // Solo en modalEditar
        const inputCarga = form.querySelector('.input-carga');    // Solo en modalEditar

        // Elementos específicos del modalNuevo (procesamiento por lote)
        const wrapperMaterias = form.querySelector('.materias-selector-wrapper');
        const checkMaterias = form.querySelectorAll('.check-materia-select');
        const feedbackGrid = form.querySelector('.feedback-grid-malla');

        // Escuchar cuando el modal se despliega para sincronizar periodos
        const modalPadre = form.closest('.modal');
        if (modalPadre) {
            modalPadre.addEventListener('show.bs.modal', function () {
                actualizarControlPeriodosFormulario(form);
            });
        }

        // ============================================================
        // Resolver idCurso a partir de los selectores Grado, Paralelo, Nivel, Turno
        // ============================================================
        if (selGrado && selParalelo && selNivel && selTurno && selectCurso) {
            const resolverCurso = function () {
                const grado = selGrado.value;
                const paralelo = parseInt(selParalelo.value, 10);
                const nivel = parseInt(selNivel.value, 10);
                const turno = parseInt(selTurno.value, 10);

                let match = null;
                if (grado && paralelo && nivel && turno) {
                    if (typeof CURSOS_DATA !== 'undefined') {
                        match = CURSOS_DATA.find(function (c) {
                            return c.cursoNombre === grado
                                && c.idParalelo === paralelo
                                && c.idNivel === nivel
                                && c.idTurno === turno;
                        });
                    }
                }

                // Guardar valor en el campo oculto
                selectCurso.value = match ? match.idCurso : '';

                if (grado && paralelo && nivel && turno) {
                    if (avisoCurso) avisoCurso.hidden = !!match;
                } else {
                    if (avisoCurso) avisoCurso.hidden = true;
                }

                // Disparar evento de cambio artificial para habilitar/deshabilitar grilla de materias
                selectCurso.dispatchEvent(new Event('change'));
            };

            [selGrado, selParalelo, selNivel, selTurno].forEach(function (s) {
                s.addEventListener('change', resolverCurso);
                s.addEventListener('change', function () {
                    if (this.value !== '') this.classList.remove('is-invalid');
                });
            });

            // Resolver inicialmente
            resolverCurso();
        }

        // ============================================================
        // Manejo del flujo "Curso Primero" (Reactividad en el hidden de curso)
        // ============================================================
        if (selectCurso && wrapperMaterias) {
            selectCurso.addEventListener('change', function () {
                if (this.value !== '') {
                    // Habilitar panel y checkboxes
                    selectCurso.classList.remove('is-invalid');
                    wrapperMaterias.classList.remove('disabled-wrapper');
                    checkMaterias.forEach(function (chk) {
                        chk.disabled = false;
                    });
                } else {
                    // Bloquear panel, limpiar selecciones y cargas horarias
                    wrapperMaterias.classList.add('disabled-wrapper');
                    checkMaterias.forEach(function (chk) {
                        chk.disabled = true;
                        chk.checked = false;
                        const row = chk.closest('.materia-item-row');
                        if (row) {
                            row.classList.remove('active-item');
                            const inputCargaMat = row.querySelector('.input-carga-materia');
                            if (inputCargaMat) {
                                inputCargaMat.disabled = true;
                                inputCargaMat.value = '';
                                inputCargaMat.removeAttribute('required');
                                inputCargaMat.classList.remove('is-invalid');
                            }
                        }
                    });
                    if (feedbackGrid) {
                        feedbackGrid.style.display = 'none';
                    }
                }
                actualizarControlPeriodosFormulario(form);
            });
        }

        // ============================================================
        // Manejo de checkboxes de materias individuales
        // ============================================================
        checkMaterias.forEach(function (chk) {
            chk.addEventListener('change', function () {
                const row = this.closest('.materia-item-row');
                if (!row) return;

                const inputCargaMat = row.querySelector('.input-carga-materia');
                if (!inputCargaMat) return;

                if (this.checked) {
                    row.classList.add('active-item');
                    inputCargaMat.disabled = false;
                    inputCargaMat.required = true;
                    inputCargaMat.focus();
                } else {
                    row.classList.remove('active-item');
                    inputCargaMat.disabled = true;
                    inputCargaMat.value = '';
                    inputCargaMat.removeAttribute('required');
                    inputCargaMat.classList.remove('is-invalid');
                }

                // Validación al vuelo y actualización de periodos
                checkMallaGridFeedback();
                actualizarControlPeriodosFormulario(form);
            });
        });

        // Eventos input para inputs numéricos de carga horaria en lote
        form.addEventListener('input', function (event) {
            const target = event.target;
            if (target && target.classList.contains('input-carga-materia')) {
                actualizarControlPeriodosFormulario(form, target);
                const val = parseInt(target.value, 10);
                if (!isNaN(val) && val >= 1 && val <= 20) {
                    target.classList.remove('is-invalid');
                    checkMallaGridFeedback();
                }
            }
        });

        // Inicializar conteo de periodos del formulario
        actualizarControlPeriodosFormulario(form);

        // Helper para ocultar/mostrar retroalimentación general de la grilla
        function checkMallaGridFeedback() {
            if (!feedbackGrid) return;

            const algunCheckMarcado = Array.from(checkMaterias).some(chk => chk.checked);
            let todasMateriasValidas = true;

            if (algunCheckMarcado) {
                const checkedMats = form.querySelectorAll('.check-materia-select:checked');
                checkedMats.forEach(function (chk) {
                    const row = chk.closest('.materia-item-row');
                    const input = row.querySelector('.input-carga-materia');
                    if (input) {
                        const val = parseInt(input.value, 10);
                        if (isNaN(val) || val < 1 || val > 20) {
                            todasMateriasValidas = false;
                        }
                    }
                });
            }

            if (algunCheckMarcado && todasMateriasValidas) {
                feedbackGrid.style.display = 'none';
            }
        }

        // ============================================================
        // Validación al enviar el Formulario
        // ============================================================
        form.addEventListener('submit', function (event) {
            let esValido = true;

            // Validar Curso
            if (selectCurso) {
                if (selectCurso.value === '') {
                    if (selGrado) selGrado.classList.add('is-invalid');
                    if (selParalelo) selParalelo.classList.add('is-invalid');
                    if (selNivel) selNivel.classList.add('is-invalid');
                    if (selTurno) selTurno.classList.add('is-invalid');
                    esValido = false;
                } else {
                    if (selGrado) selGrado.classList.remove('is-invalid');
                    if (selParalelo) selParalelo.classList.remove('is-invalid');
                    if (selNivel) selNivel.classList.remove('is-invalid');
                    if (selTurno) selTurno.classList.remove('is-invalid');
                }
            }

            if (wrapperMaterias) {
                const materiasMarcadas = Array.from(checkMaterias).filter(chk => chk.checked);

                if (materiasMarcadas.length === 0) {
                    esValido = false;
                    if (feedbackGrid) {
                        feedbackGrid.style.display = 'block';
                    }
                } else {
                    let cargaInvalida = false;
                    let sumaTotalSemanales = 0;

                    materiasMarcadas.forEach(function (chk) {
                        const row = chk.closest('.materia-item-row');
                        const input = row.querySelector('.input-carga-materia');
                        if (input) {
                            const val = parseInt(input.value, 10);
                            if (isNaN(val) || val < 1 || val > 20) {
                                input.classList.add('is-invalid');
                                cargaInvalida = true;
                            } else {
                                input.classList.remove('is-invalid');
                                sumaTotalSemanales += val;
                            }
                        }
                    });

                    if (cargaInvalida) {
                        esValido = false;
                        if (feedbackGrid) {
                            feedbackGrid.style.display = 'block';
                        }
                    } else if (sumaTotalSemanales > MAX_PERIODOS_CURSO) {
                        esValido = false;
                        mostrarToastNotificacion('Ya llegó al máximo de periodos para este curso por semana (35 periodos)', 'warning');
                    } else {
                        if (feedbackGrid) {
                            feedbackGrid.style.display = 'none';
                        }
                    }
                }
            }

            if (!esValido) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    });

    // Limpieza al cerrar el modal de creación (modalNuevo)
    const modalNuevo = document.getElementById('modalNuevo');
    if (modalNuevo) {
        modalNuevo.addEventListener('hidden.bs.modal', function () {
            const form = modalNuevo.querySelector('form');
            if (form) {
                form.reset();

                const wrapper = form.querySelector('.materias-selector-wrapper');
                if (wrapper) {
                    wrapper.classList.add('disabled-wrapper');
                }

                const checks = form.querySelectorAll('.check-materia-select');
                checks.forEach(function (chk) {
                    chk.disabled = true;
                    chk.checked = false;
                    const row = chk.closest('.materia-item-row');
                    if (row) {
                        row.classList.remove('active-item');
                        const input = row.querySelector('.input-carga-materia');
                        if (input) {
                            input.disabled = true;
                            input.value = '';
                            input.removeAttribute('required');
                            input.classList.remove('is-invalid');
                        }
                    }
                });

                const invalidElements = form.querySelectorAll('.is-invalid');
                invalidElements.forEach(function (el) {
                    el.classList.remove('is-invalid');
                });

                const feedback = form.querySelector('.feedback-grid-malla');
                if (feedback) {
                    feedback.style.display = 'none';
                }

                actualizarControlPeriodosFormulario(form);
            }
        });

        modalNuevo.addEventListener('show.bs.modal', function () {
            const form = modalNuevo.querySelector('form');
            if (form) {
                form.reset();

                const selGrado = form.querySelector('[data-tipo="grado"]');
                if (selGrado) {
                    selGrado.dispatchEvent(new Event('change'));
                }

                const rows = form.querySelectorAll('.materia-item-row');
                rows.forEach(r => r.classList.remove('active-item'));

                const inputsCarga = form.querySelectorAll('.input-carga-materia');
                inputsCarga.forEach(input => {
                    input.disabled = true;
                    input.classList.remove('is-invalid');
                });

                const feedback = form.querySelector('.feedback-grid-malla');
                if (feedback) feedback.style.display = 'none';

                actualizarControlPeriodosFormulario(form);
            }
        });
    }

    // Limpieza de errores al cerrar modales de edición
    const modalesEditar = document.querySelectorAll('[id^="modalEditar"]');
    modalesEditar.forEach(function (modal) {
        modal.addEventListener('hidden.bs.modal', function () {
            const form = modal.querySelector('form');
            if (form) {
                const invalidElements = form.querySelectorAll('.is-invalid');
                invalidElements.forEach(function (el) {
                    el.classList.remove('is-invalid');
                });
            }
        });

        modal.addEventListener('show.bs.modal', function () {
            const form = modal.querySelector('form');
            if (form) {
                actualizarControlPeriodosFormulario(form);
            }
        });
    });
});
