/* ============================================================
   importar_estudiantes.js  —  Lógica de Importación Excel por Curso JAEGASA
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    const selectCurso      = document.getElementById('selectCurso');
    const dropzone         = document.getElementById('dropzone');
    const inputExcel       = document.getElementById('inputExcel');
    const sectionPreview   = document.getElementById('sectionPreview');
    const btnEjecutar      = document.getElementById('btnEjecutarImportacion');
    const tbodyPreview     = document.getElementById('tbodyPreview');
    const tbodyInscritos   = document.getElementById('tbodyInscritos');
    const inputBuscarCurso = document.getElementById('inputBuscarCurso');
    const labelCursoNombre = document.getElementById('labelCursoNombre');
    const badgeCountCurso  = document.getElementById('badgeCountCurso');

    // Métricas UI
    const metricTotal       = document.getElementById('metricTotal');
    const metricValidos     = document.getElementById('metricValidos');
    const metricDuplicados  = document.getElementById('metricDuplicados');
    const metricIncompletos = document.getElementById('metricIncompletos');

    let filasParsed = [];
    let idCursoActual = selectCurso ? parseInt(selectCurso.value, 10) || 0 : 0;

    // Selectores individuales de curso
    const selNivel            = document.getElementById('selNivel');
    const selGrado            = document.getElementById('selGrado');
    const selParalelo         = document.getElementById('selParalelo');
    const selTurno            = document.getElementById('selTurno');
    const selGestion          = document.getElementById('selGestion');
    const nombreCursoResuelto = document.getElementById('nombreCursoResuelto');
    const badgeEstadoResuelto = document.getElementById('badgeEstadoResuelto');

    // Helper para mostrar modal de alerta/notificación estilizado
    function mostrarAlertaModal(mensaje, titulo = 'Atención', esError = false) {
        const modalEl = document.getElementById('modalAlertaImportacion');
        if (!modalEl) { alert(mensaje); return; }
        
        const header = document.getElementById('modalAlertaHeader');
        const tit = document.getElementById('modalAlertaTitulo');
        const msg = document.getElementById('modalAlertaMensaje');
        const iconCont = document.getElementById('modalAlertaIconContainer');
        
        if (msg) msg.textContent = mensaje;
        if (tit) tit.innerHTML = esError 
            ? '<i class="bi bi-x-circle-fill fs-4 me-1 text-white"></i> Error' 
            : '<i class="bi bi-exclamation-triangle-fill fs-4 me-1"></i> ' + titulo;
        
        if (header) {
            header.className = esError 
                ? 'modal-header bg-danger text-white py-3 px-4' 
                : 'modal-header bg-warning text-dark py-3 px-4';
        }
        
        if (iconCont) {
            iconCont.innerHTML = esError
                ? '<span class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle p-3 mb-2" style="width: 70px; height: 70px;"><i class="bi bi-x-octagon-fill fs-1"></i></span>'
                : '<span class="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-circle p-3 mb-2" style="width: 70px; height: 70px;"><i class="bi bi-exclamation-circle-fill fs-1"></i></span>';
        }
        
        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    }

    // ---------------------------------------------------------
    // 1. Selector en Cascada: Curso -> Paralelo -> Nivel -> Turno
    // ---------------------------------------------------------
    function actualizarCadenaSelectores(origen = 'inicial') {
        if (typeof CURSOS_CATALOGO === 'undefined' || !Array.isArray(CURSOS_CATALOGO) || CURSOS_CATALOGO.length === 0) return;

        const grado = selGrado ? selGrado.value.trim() : '';

        if (!grado) {
            if (selParalelo) selParalelo.innerHTML = '<option value="">Seleccione...</option>';
            if (selNivel)    selNivel.innerHTML    = '<option value="">Seleccione...</option>';
            if (selTurno)    selTurno.innerHTML    = '<option value="">Seleccione...</option>';
            resolverCursoFinal();
            return;
        }

        // 1. Obtener paralelos disponibles para el Grado seleccionado
        const cursosDelGrado = CURSOS_CATALOGO.filter(c => c.cursoNombre.toLowerCase() === grado.toLowerCase());
        
        const mapParalelos = {};
        cursosDelGrado.forEach(c => {
            if (c.idParalelo && c.paraleloNombre) {
                mapParalelos[c.idParalelo] = c.paraleloNombre;
            }
        });

        const idParaleloPrevio = selParalelo ? parseInt(selParalelo.value, 10) || 0 : 0;
        let idParaleloSeleccionado = 0;

        if (selParalelo) {
            selParalelo.innerHTML = '<option value="">Seleccione...</option>';
            const paralelosKeys = Object.keys(mapParalelos);
            paralelosKeys.forEach(idP => {
                const opt = document.createElement('option');
                opt.value = idP;
                opt.textContent = mapParalelos[idP];
                selParalelo.appendChild(opt);
            });

            if (idParaleloPrevio && mapParalelos[idParaleloPrevio]) {
                selParalelo.value = idParaleloPrevio;
                idParaleloSeleccionado = idParaleloPrevio;
            } else if (paralelosKeys.length === 1) {
                selParalelo.value = paralelosKeys[0];
                idParaleloSeleccionado = parseInt(paralelosKeys[0], 10);
            }
        }

        // 2. Obtener Niveles disponibles para Grado + Paralelo
        const cursosDelParalelo = cursosDelGrado.filter(c => idParaleloSeleccionado === 0 || c.idParalelo === idParaleloSeleccionado);
        const mapNiveles = {};
        cursosDelParalelo.forEach(c => {
            if (c.idNivel && c.nivelNombre) {
                mapNiveles[c.idNivel] = c.nivelNombre;
            }
        });

        const idNivelPrevio = selNivel ? parseInt(selNivel.value, 10) || 0 : 0;
        let idNivelSeleccionado = 0;

        if (selNivel) {
            selNivel.innerHTML = '<option value="">Seleccione...</option>';
            const nivelesKeys = Object.keys(mapNiveles);
            nivelesKeys.forEach(idN => {
                const opt = document.createElement('option');
                opt.value = idN;
                opt.textContent = mapNiveles[idN];
                selNivel.appendChild(opt);
            });

            if (idNivelPrevio && mapNiveles[idNivelPrevio]) {
                selNivel.value = idNivelPrevio;
                idNivelSeleccionado = idNivelPrevio;
            } else if (nivelesKeys.length === 1 && idParaleloSeleccionado > 0) {
                selNivel.value = nivelesKeys[0];
                idNivelSeleccionado = parseInt(nivelesKeys[0], 10);
            }
        }

        // 3. Obtener Turnos disponibles para Grado + Paralelo + Nivel
        const cursosDelNivel = cursosDelParalelo.filter(c => idNivelSeleccionado === 0 || c.idNivel === idNivelSeleccionado);
        const mapTurnos = {};
        cursosDelNivel.forEach(c => {
            if (c.idTurno && c.turnoNombre) {
                mapTurnos[c.idTurno] = c.turnoNombre;
            }
        });

        const idTurnoPrevio = selTurno ? parseInt(selTurno.value, 10) || 0 : 0;

        if (selTurno) {
            selTurno.innerHTML = '<option value="">Seleccione...</option>';
            const turnosKeys = Object.keys(mapTurnos);
            turnosKeys.forEach(idT => {
                const opt = document.createElement('option');
                opt.value = idT;
                opt.textContent = mapTurnos[idT];
                selTurno.appendChild(opt);
            });

            if (idTurnoPrevio && mapTurnos[idTurnoPrevio]) {
                selTurno.value = idTurnoPrevio;
            } else if (turnosKeys.length === 1 && idNivelSeleccionado > 0) {
                selTurno.value = turnosKeys[0];
            }
        }

        // 4. Resolver curso final
        resolverCursoFinal();
    }

    function resolverCursoFinal() {
        const idNivel    = selNivel ? parseInt(selNivel.value, 10) || 0 : 0;
        const grado      = selGrado ? selGrado.value.trim() : '';
        const idParalelo = selParalelo ? parseInt(selParalelo.value, 10) || 0 : 0;
        const idTurno    = selTurno ? parseInt(selTurno.value, 10) || 0 : 0;

        if (!grado || idParalelo <= 0 || idNivel <= 0 || idTurno <= 0) {
            idCursoActual = 0;
            if (selectCurso) selectCurso.value = 0;
            if (nombreCursoResuelto) nombreCursoResuelto.textContent = 'Seleccione Curso, Paralelo, Nivel y Turno...';
            if (labelCursoNombre) labelCursoNombre.textContent = 'Seleccione un curso de destino';
            if (badgeEstadoResuelto) {
                badgeEstadoResuelto.className = 'badge bg-secondary';
                badgeEstadoResuelto.textContent = 'Incompleto';
            }
            if (badgeCountCurso) badgeCountCurso.textContent = '0 estudiante(s)';
            if (tbodyInscritos) {
                tbodyInscritos.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-info-circle fs-3 d-block mb-1"></i> Seleccione todos los datos del curso para consultar a los estudiantes inscritos.</td></tr>`;
            }
            return;
        }

        const coincidencia = CURSOS_CATALOGO.find(c => {
            return c.idNivel === idNivel && 
                   c.cursoNombre.toLowerCase() === grado.toLowerCase() && 
                   c.idParalelo === idParalelo && 
                   c.idTurno === idTurno;
        });

        if (coincidencia) {
            idCursoActual = coincidencia.idCurso;
            if (selectCurso) selectCurso.value = idCursoActual;
            if (nombreCursoResuelto) nombreCursoResuelto.textContent = coincidencia.descripcion;
            if (labelCursoNombre) labelCursoNombre.textContent = coincidencia.descripcion;
            if (badgeEstadoResuelto) {
                badgeEstadoResuelto.className = 'badge bg-success';
                badgeEstadoResuelto.textContent = 'Curso Válido';
            }

            const btnDescargar = document.getElementById('btnDescargarPlantilla');
            if (btnDescargar) {
                btnDescargar.href = `index.php?c=importar_estudiantes&a=descargar_plantilla&idCurso=${idCursoActual}`;
            }

            cargarEstudiantesCurso(idCursoActual);
        } else {
            idCursoActual = 0;
            if (selectCurso) selectCurso.value = 0;
            if (nombreCursoResuelto) nombreCursoResuelto.textContent = 'No existe ningún curso registrado con la combinación seleccionada.';
            if (labelCursoNombre) labelCursoNombre.textContent = 'Sin curso seleccionado';
            if (badgeEstadoResuelto) {
                badgeEstadoResuelto.className = 'badge bg-danger';
                badgeEstadoResuelto.textContent = 'Sin coincidencia';
            }
            if (tbodyInscritos) {
                tbodyInscritos.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-exclamation-triangle fs-3 d-block mb-1"></i> No existe ningún curso con la combinación seleccionada.</td></tr>`;
            }
        }
    }

    // Eventos en los selectores
    if (selGrado) selGrado.addEventListener('change', () => actualizarCadenaSelectores('grado'));
    if (selParalelo) selParalelo.addEventListener('change', () => actualizarCadenaSelectores('paralelo'));
    if (selNivel) selNivel.addEventListener('change', () => actualizarCadenaSelectores('nivel'));
    if (selTurno) selTurno.addEventListener('change', () => resolverCursoFinal());

    // Inicializar al cargar
    if (typeof CURSOS_CATALOGO !== 'undefined' && CURSOS_CATALOGO.length > 0) {
        if (idCursoActual > 0) {
            const inicial = CURSOS_CATALOGO.find(c => c.idCurso === idCursoActual);
            if (inicial && selGrado) {
                selGrado.value = inicial.cursoNombre;
            }
        } else {
            if (selGrado) selGrado.value = '';
        }
        actualizarCadenaSelectores('inicial');
    }


    // ---------------------------------------------------------
    // 2. Manejo de Drag & Drop y Selección de Archivo
    // ---------------------------------------------------------
    const btnSeleccionarHeader = document.getElementById('btnSeleccionarArchivoExcel');
    if (btnSeleccionarHeader && inputExcel) {
        btnSeleccionarHeader.addEventListener('click', () => inputExcel.click());
    }

    if (dropzone && inputExcel) {
        dropzone.addEventListener('click', () => inputExcel.click());

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                procesarArchivo(files[0]);
            }
        });

        inputExcel.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                procesarArchivo(e.target.files[0]);
            }
        });
    }

    // ---------------------------------------------------------
    // 3. Procesar Archivo (.xlsx, .xls, .xml, .csv)
    // ---------------------------------------------------------
    function procesarArchivo(file) {
        const nombreArchivo = file.name;
        const ext = nombreArchivo.split('.').pop().toLowerCase();

        if (!['xlsx', 'xls', 'xml', 'csv'].includes(ext)) {
            alert('Formato no permitido. Por favor seleccione un archivo Excel (.xlsx, .xls) o CSV (.csv).');
            return;
        }

        const reader = new FileReader();

        reader.onload = function (e) {
            try {
                let dataRaw = e.target.result;
                let rows = [];

                if (typeof XLSX !== 'undefined') {
                    const workbook = XLSX.read(dataRaw, { type: 'binary', cellDates: true });
                    const sheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[sheetName];
                    rows = XLSX.utils.sheet_to_json(worksheet, { defval: '' });
                } else if (ext === 'csv' || ext === 'txt') {
                    rows = parsearCSV(dataRaw);
                } else {
                    mostrarAlertaModal('Librería de lectura Excel no cargada. Intente con un archivo CSV.', 'Atención', true);
                    return;
                }

                if (rows.length === 0) {
                    mostrarAlertaModal('El archivo seleccionado no contiene filas de datos.', 'Atención', true);
                    return;
                }

                filasParsed = normalizarColumnas(rows).filter(f => f.nombre !== '' || f.apPaterno !== '' || f.apMaterno !== '' || f.rude !== '' || f.ci !== '');
                if (filasParsed.length === 0) {
                    mostrarAlertaModal('El archivo no contiene registros de estudiantes para importar.', 'Atención', true);
                    return;
                }
                validarFilasServidor(filasParsed);

            } catch (err) {
                console.error('Error al procesar el archivo:', err);
                mostrarAlertaModal('No se pudo leer el archivo. Asegúrese de que el formato sea válido.', 'Error', true);
            }
        };

        if ((ext === 'csv' || ext === 'txt') && typeof XLSX === 'undefined') {
            reader.readAsText(file, 'UTF-8');
        } else {
            reader.readAsBinaryString(file);
        }
    }

    // Parser CSV inteligente autodetección (coma , o punto y coma ;)
    function parsearCSV(text) {
        text = text.replace(/^\uFEFF/, '').replace(/^sep=;\r?\n/i, '').trim();
        const lines = text.split(/\r\n|\n/);
        if (lines.length < 2) return [];

        const delimiter = (lines[0].includes(';') || (lines[1] && lines[1].includes(';'))) ? ';' : ',';
        const headers = lines[0].split(delimiter).map(h => h.trim().replace(/^"|"$/g, ''));
        const result = [];

        for (let i = 1; i < lines.length; i++) {
            if (!lines[i].trim()) continue;
            const values = lines[i].split(delimiter).map(v => v.trim().replace(/^"|"$/g, ''));
            const obj = {};
            headers.forEach((h, idx) => {
                obj[h] = values[idx] || '';
            });
            result.push(obj);
        }
        return result;
    }


    // Mapeo flexible de nombres de columnas (admite asteriscos, remarcados (OBLIGATORIO), tildes y variantes)
    function normalizarColumnas(rows) {
        return rows.map(r => {
            const keys = Object.keys(r);
            const getVal = (...possibleNames) => {
                for (let name of possibleNames) {
                    const nameClean = name.toLowerCase().trim();
                    const foundKey = keys.find(k => {
                        // Limpiar asteriscos, paréntesis, etiquetas y espacios para comparación limpia
                        const kClean = k.toLowerCase()
                            .replace(/[\*\(\)]/g, ' ')
                            .replace(/obligatorio/g, '')
                            .replace(/\s+/g, ' ')
                            .trim();
                        return kClean === nameClean || kClean.startsWith(nameClean);
                    });
                    if (foundKey && r[foundKey] !== undefined) {
                        return String(r[foundKey]).trim();
                    }
                }
                return '';
            };

            return {
                num:             getVal('n°', 'nº', 'n', 'no', '#', 'num', 'numero'),
                rude:            getVal('rude', 'codigo rude', 'nro rude', 'rude_estudiante'),
                ci:              getVal('ci', 'c.i.', 'cedula', 'documento', 'ci_estudiante'),
                apPaterno:       getVal('apellodo paterno', 'apellido paterno', 'ap paterno', 'ap. paterno', 'paterno', 'primer apellido', 'appaterno'),
                apMaterno:       getVal('apellido materno', 'ap materno', 'ap. materno', 'materno', 'segundo apellido', 'apmaterno'),
                nombre:          getVal('nombres', 'nombre', 'nombres estudiante', 'nombre_estudiante'),
                genero:          getVal('genero', 'género', 'sexo', 'genero m/f', 'genero (m/f)'),
                fechaNac:        getVal('fechanacimiento', 'fecha nacimiento', 'fecha nac', 'fecha_nacimiento', 'f_nacimiento', 'nacimiento'),
                nacionalidad:    getVal('nacionalidad', 'pais', 'origen') || 'BOLIVIANA',

                departamento:    getVal('departamento', 'depto', 'depto'),
                provincia:       getVal('provincia'),
                localidad:       getVal('localidad', 'ciudad'),
                barrio:          getVal('barrio', 'zona'),
                calle:           getVal('calle', 'avenida', 'av'),
                numero:          getVal('numero', 'número', 'nro'),
                tutorNombre:     getVal('nombre tutor', 'tutor nombre', 'nombres tutor', 'tutor_nombre', 'tutor'),
                tutorApPaterno:  getVal('apellido paterno tutor', 'ap paterno tutor', 'tutor ap paterno', 'paterno tutor'),
                tutorApMaterno:  getVal('apellido materno tutor', 'ap materno tutor', 'tutor ap materno', 'materno tutor'),
                tutorCi:         getVal('ci tutor', 'tutor ci', 'ci_tutor', 'documento tutor'),
                tutorTelefono:   getVal('telefono tutor', 'tel tutor', 'celular tutor', 'telefono_tutor', 'celular'),
                tutorCorreo:     getVal('correo tutor', 'email tutor', 'correo', 'email'),
                tutorParentesco: getVal('parentesco tutor', 'parentesco', 'relacion') || 'PADRE/MADRE'
            };
        });
    }


    // ---------------------------------------------------------
    // 4. Validar Filas en Servidor (AJAX)
    // ---------------------------------------------------------
    async function validarFilasServidor(filas) {
        try {
            const resp = await fetch('index.php?c=importar_estudiantes&a=validar_datos', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ estudiantes: filas })
            });

            const data = await resp.json();

            if (data.ok) {
                mostrarVistaPrevia(data.conteo, data.filas);
            } else {
                alert(data.mensaje || 'Error al validar registros.');
            }
        } catch (err) {
            console.error('Error al enviar datos de validación:', err);
            alert('Error de conexión con el servidor.');
        }
    }

    // ---------------------------------------------------------
    // 5. Renderizar Vista Previa y Métricas
    // ---------------------------------------------------------
    function mostrarVistaPrevia(conteo, filas) {
        if (metricTotal) metricTotal.textContent = conteo.total;
        if (metricValidos) metricValidos.textContent = conteo.validos;
        if (metricDuplicados) metricDuplicados.textContent = conteo.duplicados;
        if (metricIncompletos) metricIncompletos.textContent = conteo.incompletos;

        tbodyPreview.innerHTML = '';

        filas.forEach(f => {
            const tr = document.createElement('tr');

            let badgeClass = 'badge-valido';
            if (f.estado === 'DUPLICADO') badgeClass = 'badge-duplicado';
            if (f.estado === 'INCOMPLETO') badgeClass = 'badge-incompleto';

            tr.innerHTML = `
                <td>#${f.index}</td>
                <td><span class="badge-status ${badgeClass}">${f.estado}</span></td>
                <td>${escapeHtml(f.rude || '-')}</td>
                <td>${escapeHtml(f.ci || '-')}</td>
                <td><strong>${escapeHtml(f.apPaterno)} ${escapeHtml(f.apMaterno)}</strong></td>
                <td>${escapeHtml(f.nombre)}</td>
                <td>${escapeHtml(f.genero || 'M')}</td>
                <td>${escapeHtml(f.fechaNac || '-')}</td>
                <td>${escapeHtml(f.tutorNombre ? (f.tutorNombre + ' ' + (f.tutorApPaterno || '')) : '-')}</td>
                <td><small class="text-muted">${escapeHtml(f.observacion)}</small></td>
            `;
            tbodyPreview.appendChild(tr);
        });

        if (sectionPreview) sectionPreview.style.display = 'block';
        if (btnEjecutar) {
            btnEjecutar.disabled = (conteo.validos === 0 && conteo.duplicados === 0);
        }
    }

    // ---------------------------------------------------------
    // 6. Confirmar e Importar a la Base de Datos (AJAX)
    // ---------------------------------------------------------
    // Referencias a los modales de confirmación y resultado
    const btnEjecutarConfirmado = document.getElementById('btnEjecutarConfirmado');
    const modalConfirmEl = document.getElementById('modalConfirmarImportacion');
    const modalResultadoEl = document.getElementById('modalResultadoImportacion');
    let modalConfirmBS = null;
    let modalResultadoBS = null;

    if (modalConfirmEl && typeof bootstrap !== 'undefined') modalConfirmBS = new bootstrap.Modal(modalConfirmEl);
    if (modalResultadoEl && typeof bootstrap !== 'undefined') modalResultadoBS = new bootstrap.Modal(modalResultadoEl);

    if (btnEjecutar) {
        btnEjecutar.addEventListener('click', () => {
            if (idCursoActual <= 0) {
                mostrarAlertaModal('Por favor seleccione un curso de destino válido antes de importar.');
                return;
            }

            if (!filasParsed || filasParsed.length === 0) {
                mostrarAlertaModal('El archivo no contiene registros de estudiantes válidos para importar.');
                return;
            }

            // Llenar datos en el modal de confirmación
            const elCurso = document.getElementById('modalConfirmCurso');
            const elCant  = document.getElementById('modalConfirmCantidad');
            if (elCurso) elCurso.textContent = (nombreCursoResuelto ? nombreCursoResuelto.textContent : 'Curso Seleccionado');
            if (elCant)  elCant.textContent = `${filasParsed.length} estudiante(s)`;

            if (modalConfirmBS) {
                modalConfirmBS.show();
            } else {
                if (confirm(`¿Está seguro de importar ${filasParsed.length} estudiante(s) al curso seleccionado?`)) {
                    ejecutarImportacionAJAX();
                }
            }
        });
    }

    if (btnEjecutarConfirmado) {
        btnEjecutarConfirmado.addEventListener('click', () => {
            if (modalConfirmBS) modalConfirmBS.hide();
            ejecutarImportacionAJAX();
        });
    }

    async function ejecutarImportacionAJAX() {
        if (!btnEjecutar) return;
        btnEjecutar.disabled = true;
        btnEjecutar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Importando estudiantes...';

        try {
            const resp = await fetch('index.php?c=importar_estudiantes&a=guardar_importacion', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idCurso: idCursoActual,
                    estudiantes: filasParsed
                })
            });

            const data = await resp.json();

            if (data.ok) {
                // Llenar modal de resultado
                const elMensaje   = document.getElementById('modalResultadoMensaje');
                const elInsertados = document.getElementById('modalResultadoInsertados');
                const elInscritos  = document.getElementById('modalResultadoInscritos');

                if (elMensaje)   elMensaje.textContent = data.mensaje;
                if (elInsertados && data.resumen) elInsertados.textContent = `${data.resumen.insertados} estudiante(s)`;
                if (elInscritos  && data.resumen) elInscritos.textContent  = `${data.resumen.inscritos} estudiante(s)`;

                if (modalResultadoBS) {
                    modalResultadoBS.show();
                } else {
                    mostrarAlertaModal(data.mensaje, '¡Éxito!');
                }

                if (sectionPreview) sectionPreview.style.display = 'none';
                filasParsed = [];
                cargarEstudiantesCurso(idCursoActual);
            } else {
                mostrarAlertaModal(data.mensaje || 'Error al realizar la importación.', 'Error', true);
            }
        } catch (err) {
            console.error('Error durante la importación:', err);
            mostrarAlertaModal('Ocurrió un error inesperado al conectar con el servidor.', 'Error', true);
        } finally {
            btnEjecutar.disabled = false;
            btnEjecutar.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Confirmar e Importar al Curso';
        }
    }

    // ---------------------------------------------------------
    // 7. Cargar Estudiantes del Curso Seleccionado (AJAX)
    // ---------------------------------------------------------
    async function cargarEstudiantesCurso(idCurso, buscar = '') {
        if (!tbodyInscritos || idCurso <= 0) return;

        tbodyInscritos.innerHTML = `<tr><td colspan="7" class="text-center py-4"><span class="spinner-border text-primary me-2"></span> Cargando estudiantes...</td></tr>`;

        try {
            const resp = await fetch(`index.php?c=importar_estudiantes&a=obtener_estudiantes_curso&idCurso=${idCurso}&buscar=${encodeURIComponent(buscar)}`);
            const data = await resp.json();

            if (data.ok) {
                renderizarTablaInscritos(data.estudiantes);
            }
        } catch (err) {
            console.error('Error al cargar estudiantes del curso:', err);
            tbodyInscritos.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-3">Error al cargar estudiantes del curso.</td></tr>`;
        }
    }

    function renderizarTablaInscritos(estudiantes) {
        if (badgeCountCurso) badgeCountCurso.textContent = `${estudiantes.length} estudiante(s)`;

        if (estudiantes.length === 0) {
            tbodyInscritos.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i> No hay estudiantes inscritos en este curso aún.</td></tr>`;
            return;
        }

        tbodyInscritos.innerHTML = '';
        estudiantes.forEach((e, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${idx + 1}</td>
                <td><code>${escapeHtml(e.rude || 'S/R')}</code></td>
                <td>${escapeHtml(e.ci || '-')}</td>
                <td><strong>${escapeHtml(e.apPaterno)} ${escapeHtml(e.apMaterno)} ${escapeHtml(e.nombre)}</strong></td>
                <td><span class="badge ${e.genero === 'F' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary'}">${e.genero === 'F' ? 'Femenino' : 'Masculino'}</span></td>
                <td>${escapeHtml(e.tutorNombre || 'Sin registrado')} ${e.tutorTelefono ? '<small class="text-muted">(' + escapeHtml(e.tutorTelefono) + ')</small>' : ''}</td>
                <td class="text-end">
                    <a href="index.php?c=importar_estudiantes&a=eliminar_estudiante_curso&idInscripcion=${e.idInscripcion}&idCurso=${idCursoActual}"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('¿Quitar a este estudiante de este curso?');" title="Quitar del curso">
                       <i class="bi bi-trash"></i>
                    </a>
                </td>
            `;
            tbodyInscritos.appendChild(tr);
        });
    }

    // Filtro de búsqueda en tiempo real
    if (inputBuscarCurso) {
        let timeoutBuscar = null;
        inputBuscarCurso.addEventListener('input', (e) => {
            clearTimeout(timeoutBuscar);
            timeoutBuscar = setTimeout(() => {
                cargarEstudiantesCurso(idCursoActual, e.target.value.trim());
            }, 300);
        });
    }

    // Helper sanitizar HTML
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
