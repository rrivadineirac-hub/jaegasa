/* ============================================================
   asistencia.js — Lógica interactiva para Asistencia
   Sistema Académico JAEGASA
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    const tabla = document.getElementById('tablaAsistenciaEstudiantes');
    
    // Solo registrar lógica de planilla si la tabla existe en el DOM
    if (tabla) {
        const filas = tabla.querySelectorAll('.fila-estudiante-asistencia');
        const btnMarcarTodos = document.getElementById('btnMarcarTodosAsistio');

        // 1. Recalcular estadísticas al cargar la página
        recalcularEstadisticas();

        // 2. Manejar eventos de cambio y doble clic en las opciones de asistencia
        filas.forEach(function (fila) {
            const radios = fila.querySelectorAll('.radio-estado-asist');
            const opcionesLabel = fila.querySelectorAll('.opcion-asist');
            const grupoHora = fila.querySelector('.input-grupo-hora-llegada');
            const inputHora = fila.querySelector('.input-hora-llegada');
            const grupoObs = fila.querySelector('.input-grupo-observacion');

            radios.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    const estado = this.value;

                    // Ocultar todos los inputs opcionales por defecto
                    if (grupoHora) grupoHora.classList.remove('mostrar');
                    if (inputHora) inputHora.removeAttribute('required');
                    if (grupoObs) grupoObs.classList.remove('mostrar');

                    // Mostrar condicionalmente
                    if (estado === 'R') {
                        if (grupoHora) grupoHora.classList.add('mostrar');
                        if (inputHora) {
                            inputHora.setAttribute('required', 'required');
                            // Si no tiene valor, asignar la hora actual
                            if (!inputHora.value) {
                                const ahora = new Date();
                                const hrs = String(ahora.getHours()).padStart(2, '0');
                                const mins = String(ahora.getMinutes()).padStart(2, '0');
                                inputHora.value = `${hrs}:${mins}`;
                            }
                        }
                    } else if (estado === 'L' || estado === 'F') {
                        if (grupoObs) grupoObs.classList.add('mostrar');
                    }

                    // Recalcular estadísticas tras el cambio
                    recalcularEstadisticas();
                });
            });

            // Permitir desmarcar al hacer doble clic sobre la opción seleccionada
            opcionesLabel.forEach(function (label) {
                label.addEventListener('dblclick', function (e) {
                    e.preventDefault();
                    const radio = label.querySelector('.radio-estado-asist');
                    if (radio) {
                        radio.checked = false;

                        // Deshabilitar estado existente si hubiere
                        const hiddenExistente = fila.querySelector('input[name$="[estado_existente]"]');
                        if (hiddenExistente) {
                            hiddenExistente.disabled = true;
                        }

                        // Ocultar inputs opcionales de la fila
                        if (grupoHora) grupoHora.classList.remove('mostrar');
                        if (inputHora) inputHora.removeAttribute('required');
                        if (grupoObs) grupoObs.classList.remove('mostrar');

                        // Recalcular estadísticas
                        recalcularEstadisticas();
                    }
                });
            });
        });

        // 3. Botón para marcar todos como "Asistió"
        if (btnMarcarTodos) {
            btnMarcarTodos.addEventListener('click', function () {
                filas.forEach(function (fila) {
                    const radioA = fila.querySelector('.radio-estado-asist[value="A"]');
                    if (radioA && !radioA.checked) {
                        radioA.checked = true;
                        radioA.dispatchEvent(new Event('change'));
                    }
                });
            });
        }



        // Habilitar temporalmente los radio botones marcados pero deshabilitados al enviar el formulario
        const formAsistencia = document.getElementById('formAsistenciaMasiva');
        if (formAsistencia) {
            formAsistencia.addEventListener('submit', function () {
                formAsistencia.querySelectorAll('.radio-estado-asist:checked:disabled').forEach(function (radio) {
                    radio.disabled = false;
                });
            });
        }

        // 4. Función de recálculo estadístico
        function recalcularEstadisticas() {
            let presentes = 0;
            let retrasos = 0;
            let faltas = 0;
            let licencias = 0;

            filas.forEach(function (fila) {
                const radioChecked = fila.querySelector('.radio-estado-asist:checked');
                if (radioChecked) {
                    const valor = radioChecked.value;
                    if (valor === 'A') {
                        presentes++;
                    } else if (valor === 'R') {
                        retrasos++;
                    } else if (valor === 'F') {
                        faltas++;
                    } else if (valor === 'L') {
                        licencias++;
                    }
                }
            });

            const txtPresentes = document.getElementById('stat-presentes');
            const txtRetrasos = document.getElementById('stat-retrasos');
            const txtFaltas = document.getElementById('stat-faltas');
            const txtLicencias = document.getElementById('stat-licencias');

            if (txtPresentes) txtPresentes.textContent = presentes;
            if (txtRetrasos) txtRetrasos.textContent = retrasos;
            if (txtFaltas) txtFaltas.textContent = faltas;
            if (txtLicencias) txtLicencias.textContent = licencias;
        }

        // 5. Filtro instantáneo de la tabla de estudiantes
        const txtBuscarEst = document.getElementById('buscadorEstudianteAsist');
        if (txtBuscarEst) {
            function normAsist(str) {
                if (!str) return '';
                return str
                    .replace(/Ñ/g, '__ENNE__')
                    .replace(/ñ/g, '__ENNE__')
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "")
                    .replace(/__ENNE__/g, 'ñ')
                    .toLowerCase()
                    .trim();
            }

            txtBuscarEst.addEventListener('input', function () {
                const query  = normAsist(this.value);
                const tokens = query.split(/\s+/).filter(Boolean);
                let visibles = 0;

                filas.forEach(function (fila) {
                    const nombreEl = fila.querySelector('.estudiante-nombre');
                    const metaEl   = fila.querySelector('.estudiante-meta');

                    const nombreText = nombreEl ? normAsist(nombreEl.textContent) : '';
                    const metaText   = metaEl ? normAsist(metaEl.textContent) : '';

                    if (tokens.length === 0) {
                        fila.style.display = '';
                        visibles++;
                        return;
                    }

                    const palabras = (nombreText + ' ' + metaText).split(/\s+/).filter(Boolean);
                    const coincide = tokens.every(token => {
                        return nombreText.startsWith(token) ||
                               metaText.startsWith(token)   ||
                               palabras.some(p => p.startsWith(token));
                    });

                    if (coincide) {
                        fila.style.display = '';
                        visibles++;
                    } else {
                        fila.style.display = 'none';
                    }
                });

                const totalAlumnos = document.getElementById('total-alumnos');
                if (totalAlumnos) {
                    totalAlumnos.textContent = visibles;
                }
            });
        }
    }

    // ============================================================
    // Resolver idCurso a partir de los selectores Grado, Paralelo, Nivel, Turno
    // (Esta lógica se ejecuta SIEMPRE, exista o no la tabla de alumnos)
    // ============================================================
    const formFiltro = document.getElementById('formFiltroAsistencia');
    if (formFiltro) {
        const selGrado    = formFiltro.querySelector('[data-tipo="grado"]');
        const selParalelo = formFiltro.querySelector('[data-tipo="paralelo"]');
        const selNivel    = formFiltro.querySelector('[data-tipo="nivel"]');
        const selTurno    = formFiltro.querySelector('[data-tipo="turno"]');
        const hiddenCurso = formFiltro.querySelector('.idCursoResuelto');
        const avisoCurso  = formFiltro.querySelector('.cursoNoEncontrado');

        if (selGrado && selParalelo && selNivel && selTurno && hiddenCurso) {
            const btnCargarDocentes = document.getElementById('btnCargarAsistenciaDocentes');

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

                hiddenCurso.value = match ? match.idCurso : '';

                // Habilitar el botón "Cargar asistencia del curso" solo cuando los 4 campos estén seleccionados y sea un curso válido
                if (btnCargarDocentes) {
                    btnCargarDocentes.disabled = !(grado && paralelo && nivel && turno && match);
                }

                if (grado && paralelo && nivel && turno) {
                    if (avisoCurso) avisoCurso.hidden = !!match;
                } else {
                    if (avisoCurso) avisoCurso.hidden = true;
                }
            };

            // Escuchar cambios en los 4 selectores
            [selGrado, selParalelo, selNivel, selTurno].forEach(function (s) {
                s.addEventListener('change', resolverCurso);
            });

            // Resolver inicialmente (útil si hay valores pre-seleccionados al cargar)
            resolverCurso();
        }

        // Botón "Limpiar" de los Criterios de Búsqueda
        const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
        if (btnLimpiarFiltros) {
            btnLimpiarFiltros.addEventListener('click', function () {
                const selGrado    = formFiltro.querySelector('[data-tipo="grado"]');
                const selParalelo = formFiltro.querySelector('[data-tipo="paralelo"]');
                const selNivel    = formFiltro.querySelector('[data-tipo="nivel"]');
                const selTurno    = formFiltro.querySelector('[data-tipo="turno"]');
                const hiddenCurso = formFiltro.querySelector('.idCursoResuelto');
                const avisoCurso  = formFiltro.querySelector('.cursoNoEncontrado');
                const inputFecha  = formFiltro.querySelector('.input-fecha-asist');

                // 1. Limpiar curso/grado y paralelo siempre
                if (selGrado) selGrado.value = '';
                if (selParalelo) selParalelo.value = '';

                // 2. En Nivel: verificar si hay más de 1 opción para limpiarlo
                if (selNivel) {
                    const opcionesNivel = Array.from(selNivel.options).filter(function (opt) {
                        return opt.value !== '';
                    });
                    if (opcionesNivel.length > 1) {
                        selNivel.value = '';
                    } else if (opcionesNivel.length === 1) {
                        selNivel.value = opcionesNivel[0].value;
                    }
                }

                // 3. En Turno: verificar si hay más de 1 opción para limpiarlo
                if (selTurno) {
                    const opcionesTurno = Array.from(selTurno.options).filter(function (opt) {
                        return opt.value !== '';
                    });
                    if (opcionesTurno.length > 1) {
                        selTurno.value = '';
                    } else if (opcionesTurno.length === 1) {
                        selTurno.value = opcionesTurno[0].value;
                    }
                }

                if (hiddenCurso) hiddenCurso.value = '';
                if (avisoCurso) avisoCurso.hidden = true;
                if (inputFecha) {
                    const hoy = new Date().toISOString().split('T')[0];
                    inputFecha.value = hoy;
                }

                // Redirigir asegurando mantenerse en el módulo de Asistencia (c=asistencia)
                window.location.href = 'index.php?c=asistencia';
            });
        }
    }

    // ============================================================
    // Botón "Cargar asistencia del curso" (desde docentes)
    // ============================================================
    const btnCargarDocentes = document.getElementById('btnCargarAsistenciaDocentes');
    if (btnCargarDocentes && formFiltro) {
        btnCargarDocentes.addEventListener('click', function () {
            const hiddenCurso = formFiltro.querySelector('.idCursoResuelto');
            if (!hiddenCurso || !hiddenCurso.value || parseInt(hiddenCurso.value, 10) <= 0) {
                alert('Por favor seleccione un Curso, Paralelo, Nivel y Turno válidos antes de cargar la asistencia del curso.');
                return;
            }

            let inputAccion = formFiltro.querySelector('input[name="a"]');
            if (!inputAccion) {
                inputAccion = document.createElement('input');
                inputAccion.type = 'hidden';
                inputAccion.name = 'a';
                formFiltro.appendChild(inputAccion);
            }
            inputAccion.value = 'cargarDesdeDocentes';
            formFiltro.submit();
        });
    }
});

// ============================================================
// Función Global para Imprimir el Reporte de Asistencia del Curso
// ============================================================
function imprimirListadoAsistencia() {
    const tabla = document.getElementById('tablaAsistenciaEstudiantes');
    if (!tabla) {
        alert('Por favor cargue primero la planilla de asistencia de un curso.');
        return;
    }

    const filas = tabla.querySelectorAll('.fila-estudiante-asistencia');
    if (!filas || filas.length === 0) {
        alert('No hay estudiantes en la lista de asistencia para imprimir.');
        return;
    }

    // Extraer metadatos del formulario de filtro
    const selGrado    = document.querySelector('[data-tipo="grado"]');
    const selParalelo = document.querySelector('[data-tipo="paralelo"]');
    const selNivel    = document.querySelector('[data-tipo="nivel"]');
    const selTurno    = document.querySelector('[data-tipo="turno"]');
    const inputFecha  = document.querySelector('.input-fecha-asist');

    const txtGrado    = selGrado && selGrado.selectedIndex >= 0 ? selGrado.options[selGrado.selectedIndex].text : '';
    const txtParalelo = selParalelo && selParalelo.selectedIndex >= 0 ? selParalelo.options[selParalelo.selectedIndex].text : '';
    const txtNivel    = selNivel && selNivel.selectedIndex >= 0 ? selNivel.options[selNivel.selectedIndex].text : '';
    const txtTurno    = selTurno && selTurno.selectedIndex >= 0 ? selTurno.options[selTurno.selectedIndex].text : '';

    let valFecha = inputFecha ? inputFecha.value : '';
    let fechaFormateada = valFecha;
    if (valFecha) {
        const partes = valFecha.split('-');
        if (partes.length === 3) {
            fechaFormateada = `${partes[2]}/${partes[1]}/${partes[0]}`;
        }
    }

    const cursoCompleto = `${txtGrado} "${txtParalelo}" - ${txtNivel} (${txtTurno})`;

    let contPresentes = 0, contRetrasos = 0, contFaltas = 0, contLicencias = 0, contSinMarcar = 0;
    let rowsHtml = '';
    let num = 1;

    filas.forEach(function (fila) {
        // Ignorar si está oculta por el buscador en vivo
        if (fila.style.display === 'none') return;

        const nombreEl = fila.querySelector('.estudiante-nombre');
        const metaEl   = fila.querySelector('.estudiante-meta');
        const radioChecked = fila.querySelector('.radio-estado-asist:checked');
        const inputHora = fila.querySelector('.input-hora-llegada');
        const inputObs  = fila.querySelector('.input-observacion-detalle');

        const nombreStr = nombreEl ? nombreEl.textContent.trim() : '';
        const metaStr   = metaEl ? metaEl.textContent.trim() : '';

        let estadoStr = 'SIN MARCAR';
        let estadoBadgeClass = 'badge-sinmarcar';
        let detalleStr = '-';

        if (radioChecked) {
            const val = radioChecked.value;
            if (val === 'A') {
                estadoStr = 'PRESENTE';
                estadoBadgeClass = 'badge-presente';
                contPresentes++;
            } else if (val === 'R') {
                estadoStr = 'RETRASO';
                estadoBadgeClass = 'badge-retraso';
                contRetrasos++;
                const horaVal = inputHora ? inputHora.value.trim() : '';
                if (horaVal) detalleStr = `Ingreso: ${horaVal}`;
            } else if (val === 'F') {
                estadoStr = 'FALTA';
                estadoBadgeClass = 'badge-falta';
                contFaltas++;
                const obsVal = inputObs ? inputObs.value.trim() : '';
                if (obsVal) detalleStr = obsVal;
            } else if (val === 'L') {
                estadoStr = 'LICENCIA';
                estadoBadgeClass = 'badge-licencia';
                contLicencias++;
                const obsVal = inputObs ? inputObs.value.trim() : '';
                if (obsVal) detalleStr = obsVal;
            }
        } else {
            contSinMarcar++;
        }

        rowsHtml += `
            <tr>
                <td style="text-align: center; font-weight: 600;">${num++}</td>
                <td style="font-weight: 700; text-transform: uppercase;">${nombreStr}</td>
                <td style="font-size: 8.5pt; color: #475569;">${metaStr}</td>
                <td style="text-align: center;"><span class="badge-est ${estadoBadgeClass}">${estadoStr}</span></td>
                <td style="font-size: 8.5pt; color: #334155;">${detalleStr}</td>
            </tr>
        `;
    });

    const totalAlumnos = num - 1;
    const logoSrc = window.schoolLogoBase64 || '';
    const fechaHoy = new Date().toLocaleDateString('es-BO');
    const horaHoy  = new Date().toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' });

    const htmlContent = `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Parte Diario de Asistencia - ${cursoCompleto}</title>
            <style>
                @page { size: letter; margin: 12mm 15mm; }
                body { font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; margin: 0; padding: 0; font-size: 9.5pt; }
                .encabezado { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #0284c7; padding-bottom: 8px; margin-bottom: 12px; }
                .logo-col { width: 70px; }
                .logo-col img { max-width: 65px; height: auto; }
                .titulo-col { text-align: center; flex-grow: 1; }
                .titulo-col h2 { margin: 0; font-size: 12pt; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
                .titulo-col h3 { margin: 3px 0 0 0; font-size: 10.5pt; color: #0284c7; font-weight: 700; }
                .meta-col { font-size: 8pt; text-align: right; color: #475569; }
                .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; margin-bottom: 12px; font-size: 8.5pt; }
                .info-item span.lbl { color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 7.5pt; display: block; }
                .info-item span.val { color: #0f172a; font-weight: 700; }
                
                .stats-bar { display: flex; gap: 8px; margin-bottom: 12px; }
                .stat-box { flex: 1; padding: 6px 10px; border-radius: 6px; text-align: center; font-size: 8.5pt; border: 1px solid #cbd5e1; }
                .stat-box.p { background: #f0fdf4; border-color: #bbf7d0; color: #16a34a; }
                .stat-box.r { background: #fffbeb; border-color: #fef08a; color: #d97706; }
                .stat-box.f { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
                .stat-box.l { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }
                .stat-box.s { background: #f8fafc; border-color: #e2e8f0; color: #64748b; }

                table.tabla-asistencia { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
                table.tabla-asistencia th { background-color: #0284c7; color: #ffffff; font-size: 8.5pt; font-weight: 700; text-transform: uppercase; padding: 6px 8px; border: 1px solid #0284c7; text-align: left; }
                table.tabla-asistencia td { padding: 5px 8px; border: 1px solid #cbd5e1; vertical-align: middle; }
                table.tabla-asistencia tr:nth-child(even) { background-color: #f8fafc; }
                
                .badge-est { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }
                .badge-presente { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
                .badge-retraso { background: #fef3c7; color: #b45309; border: 1px solid #fde047; }
                .badge-falta { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
                .badge-licencia { background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; }
                .badge-sinmarcar { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }

                .firmas-container { display: flex; justify-content: space-around; margin-top: 40px; page-break-inside: avoid; }
                .firma-box { text-align: center; width: 220px; border-top: 1px solid #475569; padding-top: 5px; font-size: 8.5pt; color: #334155; }

                .footer-nota { font-size: 7.5pt; color: #64748b; text-align: justify; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 5px; }
            </style>
        </head>
        <body>
            <div class="encabezado">
                <div class="logo-col">
                    ${logoSrc ? `<img src="${logoSrc}" alt="Logo">` : ''}
                </div>
                <div class="titulo-col">
                    <h2>UNIDAD EDUCATIVA PROF. JAIME ALFONSO ESCALANTE GUTIÉRREZ "A"</h2>
                    <h3>PARTE DIARIO OFICIAL DE ASISTENCIA ESCOLAR</h3>
                </div>
                <div class="meta-col">
                    <strong>FECHA IMPRESIÓN:</strong> ${fechaHoy}<br>
                    <strong>HORA:</strong> ${horaHoy}<br>
                    <strong>CÓDIGO SIE:</strong> 80122379
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item"><span class="lbl">Curso / Grado</span><span class="val">${txtGrado} "${txtParalelo}"</span></div>
                <div class="info-item"><span class="lbl">Nivel & Turno</span><span class="val">${txtNivel} (${txtTurno})</span></div>
                <div class="info-item"><span class="lbl">Fecha del Parte</span><span class="val">${fechaFormateada}</span></div>
                <div class="info-item"><span class="lbl">Total Alumnos</span><span class="val">${totalAlumnos} Alumnos</span></div>
            </div>

            <div class="stats-bar">
                <div class="stat-box p"><strong>${contPresentes}</strong> Presentes</div>
                <div class="stat-box r"><strong>${contRetrasos}</strong> Retrasos</div>
                <div class="stat-box f"><strong>${contFaltas}</strong> Faltas</div>
                <div class="stat-box l"><strong>${contLicencias}</strong> Licencias</div>
                <div class="stat-box s"><strong>${contSinMarcar}</strong> Sin Marcar</div>
            </div>

            <table class="tabla-asistencia">
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">N°</th>
                        <th style="width: 38%;">Estudiante</th>
                        <th style="width: 22%;">RUDE / C.I.</th>
                        <th style="width: 15%; text-align: center;">Estado</th>
                        <th style="width: 20%;">Detalle / Hora Llegada</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>

            <div class="firmas-container">
                <div class="firma-box">
                    <strong>Prof. Responsable / Asistente</strong><br>
                    Control de Asistencia
                </div>
                <div class="firma-box">
                    <strong>Dirección / Administración</strong><br>
                    Sello y Firma Institucional
                </div>
            </div>

            <div class="footer-nota">
                * Documento oficial emitido por el Sistema Académico JAEGASA. Cualquier alteración invalida la autenticidad del presente informe de asistencia.
            </div>
        </body>
        </html>
    `;

    const printWin = window.open('', '_blank', 'width=1050,height=800');
    if (printWin) {
        printWin.document.write(htmlContent);
        printWin.document.close();
        printWin.focus();
        setTimeout(() => { printWin.print(); }, 400);
    } else {
        let iframe = document.getElementById('iframeImpresionAsistencia');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'iframeImpresionAsistencia';
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            document.body.appendChild(iframe);
        }
        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write(htmlContent);
        doc.close();
        setTimeout(() => {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }, 400);
    }
}
