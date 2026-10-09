/**
 * asignacion_docente.js — Validación y manejo dinámico del CRUD Asignación Docente
 * Sistema Académico JAEGASA
 */

document.addEventListener('DOMContentLoaded', function () {
    const formularios = document.querySelectorAll('.form-asignacion-validador');

    formularios.forEach(function (form) {
        const selectDocente    = form.querySelector('.select-docente');
        const checkCursos      = form.querySelectorAll('.check-curso-select');
        const feedbackAsignar  = form.querySelector('.feedback-grid-asignacion');
        const wrapperGrid      = form.querySelector('.asignacion-grid-wrapper');

        // ============================================================
        // Reactividad de "Docente Primero"
        // ============================================================
        if (selectDocente && wrapperGrid) {
            const toggleGridState = function () {
                if (selectDocente.value !== '') {
                    // Habilitar la grilla de selección de cursos y materias
                    wrapperGrid.classList.remove('disabled-wrapper');
                    checkCursos.forEach(function (chk) {
                        chk.disabled = false;
                    });
                } else {
                    // Bloquear la grilla y desmarcar todos los cursos seleccionados
                    wrapperGrid.classList.add('disabled-wrapper');
                    checkCursos.forEach(function (chk) {
                        chk.disabled = true;
                        chk.checked = false;
                        
                        // Disparar evento de cambio artificial para limpiar la materias asociadas
                        chk.dispatchEvent(new Event('change'));
                    });
                }
            };

            selectDocente.addEventListener('change', toggleGridState);
            
            // Ejecutar inicialmente (útil para el modal de edición donde ya viene seleccionado)
            toggleGridState();
        }

        // ============================================================
        // Manejar cambio en checkboxes de cursos
        // ============================================================
        checkCursos.forEach(function (chk) {
            chk.addEventListener('change', function () {
                const cursoId = this.value;
                const rowContainer = this.closest('.fila-curso-opcion');
                const selectContainer = rowContainer.querySelector('.materias-select-container');
                const selects = selectContainer.querySelectorAll('.select-materia-curso');
                const btnAgregar = selectContainer.querySelector('.btn-agregar-materia');

                if (this.checked) {
                    if (rowContainer) rowContainer.classList.add('active-row');
                    
                    // Habilitar selectores de materia principales
                    selects.forEach(function (sel) {
                        sel.disabled = false;
                    });
                    if (btnAgregar) btnAgregar.disabled = false;
                } else {
                    if (rowContainer) rowContainer.classList.remove('active-row');
                    
                    // Deshabilitar, vaciar y remover filas extra de materias
                    selects.forEach(function (sel, idx) {
                        sel.disabled = true;
                        sel.value = '';
                        sel.classList.remove('is-invalid');
                    });
                    
                    if (btnAgregar) btnAgregar.disabled = true;

                    // Remover filas extra (todas las .materia-row excepto la primera)
                    const extraRows = selectContainer.querySelectorAll('.materia-row:not(:first-child)');
                    extraRows.forEach(function (row) {
                        row.remove();
                    });
                }

                // Ocultar feedback si hay al menos un curso marcado
                checkFeedbackGrid();
            });
        });

        // Evento delegado para agregar materia (+)
        form.addEventListener('click', function (event) {
            const btn = event.target.closest('.btn-agregar-materia');
            if (!btn) return;

            event.preventDefault();
            const container = btn.closest('.materias-select-container');
            if (!container) return;

            const cursoId = container.getAttribute('data-curso-id');
            const firstRow = container.querySelector('.materia-row');
            if (!firstRow) return;

            // Clonar primera fila
            const newRow = firstRow.cloneNode(true);
            const select = newRow.querySelector('.select-materia-curso');
            
            // Limpiar valores y clases del select clonado
            select.value = '';
            select.disabled = false;
            select.classList.remove('is-invalid');

            // Reemplazar botón "+" por botón "-" de quitar materia
            const btnOld = newRow.querySelector('.btn-agregar-materia');
            if (btnOld) {
                const btnRemove = document.createElement('button');
                btnRemove.type = 'button';
                btnRemove.className = 'btn btn-sm btn-outline-danger btn-remover-materia';
                btnRemove.title = 'Quitar materia';
                btnRemove.innerHTML = '<i class="bi bi-dash-lg"></i>';
                btnOld.replaceWith(btnRemove);
            }

            container.appendChild(newRow);
        });

        // Evento delegado para remover materia (-)
        form.addEventListener('click', function (event) {
            const btn = event.target.closest('.btn-remover-materia');
            if (!btn) return;

            event.preventDefault();
            const row = btn.closest('.materia-row');
            if (row) {
                row.remove();
                checkFeedbackGrid();
            }
        });

        // Evento cambio de docente
        if (selectDocente) {
            selectDocente.addEventListener('change', function () {
                if (this.value !== '') {
                    this.classList.remove('is-invalid');
                }
            });
        }

        // Evento cambio en los selectores de materias para limpiar errores
        form.addEventListener('change', function (event) {
            const select = event.target.closest('.select-materia-curso');
            if (select && select.value !== '') {
                select.classList.remove('is-invalid');
                checkFeedbackGrid();
            }
        });

        // Función para ocultar o mostrar el feedback de error general de la grilla
        function checkFeedbackGrid() {
            if (!feedbackAsignar) return;

            const algunCursoMarcado = Array.from(checkCursos).some(chk => chk.checked);
            let todasMateriasValidas = true;

            if (algunCursoMarcado) {
                const checkedCourses = form.querySelectorAll('.check-curso-select:checked');
                checkedCourses.forEach(function (chk) {
                    const row = chk.closest('.fila-curso-opcion');
                    const selects = row.querySelectorAll('.select-materia-curso');
                    selects.forEach(function (sel) {
                        if (sel.value === '') {
                            todasMateriasValidas = false;
                        }
                    });
                });
            }

            if (algunCursoMarcado && todasMateriasValidas) {
                feedbackAsignar.style.display = 'none';
            }
        }

        // ============================================================
        // Validación al enviar el formulario
        // ============================================================
        form.addEventListener('submit', function (event) {
            let esValido = true;

            // 1. Validar Docente
            if (selectDocente) {
                if (selectDocente.value === '') {
                    selectDocente.classList.add('is-invalid');
                    esValido = false;
                } else {
                    selectDocente.classList.remove('is-invalid');
                }
            }

            // 2. Validar Cursos y Materias
            const cursosMarcados = Array.from(checkCursos).filter(chk => chk.checked);

            if (cursosMarcados.length === 0) {
                esValido = false;
                if (feedbackAsignar) {
                    feedbackAsignar.style.display = 'block';
                }
            } else {
                let materiaVacia = false;
                cursosMarcados.forEach(function (chk) {
                    const row = chk.closest('.fila-curso-opcion');
                    const selects = row.querySelectorAll('.select-materia-curso');
                    selects.forEach(function (sel) {
                        if (sel.value === '') {
                            sel.classList.add('is-invalid');
                            materiaVacia = true;
                        } else {
                            sel.classList.remove('is-invalid');
                        }
                    });
                });

                if (materiaVacia) {
                    esValido = false;
                    if (feedbackAsignar) {
                        feedbackAsignar.style.display = 'block';
                    }
                } else {
                    if (feedbackAsignar) {
                        feedbackAsignar.style.display = 'none';
                    }
                }
            }

            if (!esValido) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    });

    // Limpieza al ocultar el modal de creación (modalNuevo)
    const modalNuevo = document.getElementById('modalNuevo');
    if (modalNuevo) {
        modalNuevo.addEventListener('hidden.bs.modal', function () {
            const form = modalNuevo.querySelector('form');
            if (form) {
                form.reset();

                // Volver a colocar el estado bloqueado de la grilla de cursos
                const wrapperGrid = form.querySelector('.asignacion-grid-wrapper');
                if (wrapperGrid) {
                    wrapperGrid.classList.add('disabled-wrapper');
                }
                
                const selectContainers = form.querySelectorAll('.materias-select-container');
                selectContainers.forEach(function (container) {
                    // Deshabilitar y vaciar el primer selector
                    const firstSelect = container.querySelector('.select-materia-curso');
                    if (firstSelect) {
                        firstSelect.disabled = true;
                        firstSelect.value = '';
                        firstSelect.classList.remove('is-invalid');
                    }

                    // Deshabilitar botón agregar
                    const btnAgregar = container.querySelector('.btn-agregar-materia');
                    if (btnAgregar) btnAgregar.disabled = true;

                    // Remover selectores extra creados dinámicamente
                    const extraRows = container.querySelectorAll('.materia-row:not(:first-child)');
                    extraRows.forEach(function (row) {
                        row.remove();
                    });
                });

                // Limpiar checkboxes deshabilitados
                const checks = form.querySelectorAll('.check-curso-select');
                checks.forEach(function (chk) {
                    chk.disabled = true;
                    chk.checked = false;
                });

                // Limpiar clases active-row
                const rows = form.querySelectorAll('.fila-curso-opcion');
                rows.forEach(function (row) {
                    row.classList.remove('active-row');
                });

                // Limpiar validaciones
                const invalidElements = form.querySelectorAll('.is-invalid');
                invalidElements.forEach(function (el) {
                    el.classList.remove('is-invalid');
                });

                const feedback = form.querySelector('.feedback-grid-asignacion');
                if (feedback) feedback.style.display = 'none';
            }
        });
    }

    // Limpieza al ocultar modales de edición
    const modalesEditar = document.querySelectorAll('[id^="modalEditar"]');
    modalesEditar.forEach(function (modal) {
        modal.addEventListener('hidden.bs.modal', function () {
            const form = modal.querySelector('form');
            if (form) {
                // Al cerrar el modal de edición, limpiamos solo las clases de error visuales.
                // Los datos originales se conservan listos para volver a abrirse.
                const invalidElements = form.querySelectorAll('.is-invalid');
                invalidElements.forEach(function (el) {
                    el.classList.remove('is-invalid');
                });

                const feedback = form.querySelector('.feedback-grid-asignacion');
                if (feedback) feedback.style.display = 'none';
            }
        });
    });
});
