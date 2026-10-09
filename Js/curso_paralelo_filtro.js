/**
 * ============================================================
 * curso_paralelo_filtro.js
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Filtrado y autoselección multidireccional entre los selectores de:
 *   - Curso / Grado
 *   - Paralelo
 *   - Nivel
 *   - Turno
 *
 * Si sólo existe un único curso, paralelo, nivel o turno válido
 * según las opciones seleccionadas, se autoselecciona e interactúa
 * automáticamente.
 * ============================================================
 */

let isUpdatingOpcionesCurso = false;

function actualizarOpcionesCurso(container) {
    if (isUpdatingOpcionesCurso) return;
    if (typeof CURSOS_DATA === 'undefined' || !Array.isArray(CURSOS_DATA)) return;

    isUpdatingOpcionesCurso = true;

    try {
        const selGrado = container.querySelector('[data-tipo="grado"], select[name="grado"]');
        const selParalelo = container.querySelector('[data-tipo="paralelo"], select[name="paralelo"]');
        const selNivel = container.querySelector('[data-tipo="nivel"], select[name="nivel"]');
        const selTurno = container.querySelector('[data-tipo="turno"], select[name="turno"]');

        if (!selGrado && !selParalelo && !selNivel && !selTurno) return;

        let seHizoSeleccionAuto = false;

        // Leer valores actuales seleccionados
        let gradoVal = selGrado ? (selGrado.value || '').trim() : '';
        let paraleloVal = (selParalelo && selParalelo.value !== '') ? parseInt(selParalelo.value, 10) : null;
        let nivelVal = (selNivel && selNivel.value !== '') ? parseInt(selNivel.value, 10) : null;
        let turnoVal = (selTurno && selTurno.value !== '') ? parseInt(selTurno.value, 10) : null;

        // --- A. FILTRAR Y AUTO-SELECCIONAR GRADO / CURSO ---
        if (selGrado) {
            const gradosValidos = new Set();
            CURSOS_DATA.forEach(function (c) {
                const nom = (c.cursoNombre || c.nombreCurso || c.nombre || '').trim();
                const matchP = (paraleloVal === null || c.idParalelo === paraleloVal);
                const matchN = (nivelVal === null || c.idNivel === nivelVal);
                const matchT = (turnoVal === null || c.idTurno === turnoVal);
                if (nom && matchP && matchN && matchT) {
                    gradosValidos.add(nom);
                }
            });

            let opcionesVisibles = [];
            let selEsValida = false;

            Array.from(selGrado.options).forEach(function (opt) {
                if (opt.value === '') {
                    opt.hidden = false; opt.disabled = false; opt.style.display = '';
                    return;
                }
                const gNom = opt.value.trim();
                if (gradosValidos.has(gNom)) {
                    opt.hidden = false; opt.disabled = false; opt.style.display = '';
                    opcionesVisibles.push(opt);
                    if (gradoVal === gNom) selEsValida = true;
                } else {
                    opt.hidden = true; opt.disabled = true; opt.style.display = 'none';
                }
            });

            if (gradoVal !== '' && !selEsValida) {
                selGrado.value = '';
                gradoVal = '';
            }

            if (opcionesVisibles.length === 1 && (selGrado.value === '' || !selEsValida)) {
                selGrado.value = opcionesVisibles[0].value;
                gradoVal = selGrado.value.trim();
                seHizoSeleccionAuto = true;
            }
        }

        // --- B. FILTRAR Y AUTO-SELECCIONAR PARALELO ---
        if (selParalelo) {
            const paralelosValidos = new Set();
            CURSOS_DATA.forEach(function (c) {
                const nom = (c.cursoNombre || c.nombreCurso || c.nombre || '').trim();
                const matchG = (gradoVal === '' || nom === gradoVal);
                const matchN = (nivelVal === null || c.idNivel === nivelVal);
                const matchT = (turnoVal === null || c.idTurno === turnoVal);
                if (c.idParalelo && matchG && matchN && matchT) {
                    paralelosValidos.add(c.idParalelo);
                }
            });

            let opcionesVisibles = [];
            let selEsValida = false;

            Array.from(selParalelo.options).forEach(function (opt) {
                if (opt.value === '') {
                    opt.hidden = false; opt.disabled = false; opt.style.display = '';
                    return;
                }
                const idP = parseInt(opt.value, 10);
                if (paralelosValidos.has(idP)) {
                    opt.hidden = false; opt.disabled = false; opt.style.display = '';
                    opcionesVisibles.push(opt);
                    if (paraleloVal === idP) selEsValida = true;
                } else {
                    opt.hidden = true; opt.disabled = true; opt.style.display = 'none';
                }
            });

            if (paraleloVal !== null && !selEsValida) {
                selParalelo.value = '';
                paraleloVal = null;
            }

            if (opcionesVisibles.length === 1 && (selParalelo.value === '' || !selEsValida)) {
                selParalelo.value = opcionesVisibles[0].value;
                paraleloVal = parseInt(selParalelo.value, 10);
                seHizoSeleccionAuto = true;
            }
        }

        // --- C. FILTRAR Y AUTO-SELECCIONAR NIVEL ---
        if (selNivel) {
            const nivelesValidos = new Set();
            CURSOS_DATA.forEach(function (c) {
                const nom = (c.cursoNombre || c.nombreCurso || c.nombre || '').trim();
                const matchG = (gradoVal === '' || nom === gradoVal);
                const matchP = (paraleloVal === null || c.idParalelo === paraleloVal);
                const matchT = (turnoVal === null || c.idTurno === turnoVal);
                if (c.idNivel && matchG && matchP && matchT) {
                    nivelesValidos.add(c.idNivel);
                }
            });

            let opcionesVisibles = [];
            let selEsValida = false;

            Array.from(selNivel.options).forEach(function (opt) {
                if (opt.value === '') {
                    opt.hidden = false; opt.disabled = false; opt.style.display = '';
                    return;
                }
                const idN = parseInt(opt.value, 10);
                if (nivelesValidos.has(idN)) {
                    opt.hidden = false; opt.disabled = false; opt.style.display = '';
                    opcionesVisibles.push(opt);
                    if (nivelVal === idN) selEsValida = true;
                } else {
                    opt.hidden = true; opt.disabled = true; opt.style.display = 'none';
                }
            });

            if (nivelVal !== null && !selEsValida) {
                selNivel.value = '';
                nivelVal = null;
            }

            if (opcionesVisibles.length === 1 && (selNivel.value === '' || !selEsValida)) {
                selNivel.value = opcionesVisibles[0].value;
                nivelVal = parseInt(selNivel.value, 10);
                seHizoSeleccionAuto = true;
            }
        }

        // --- D. FILTRAR Y AUTO-SELECCIONAR TURNO ---
        if (selTurno) {
            const turnosValidos = new Set();
            CURSOS_DATA.forEach(function (c) {
                const nom = (c.cursoNombre || c.nombreCurso || c.nombre || '').trim();
                const matchG = (gradoVal === '' || nom === gradoVal);
                const matchP = (paraleloVal === null || c.idParalelo === paraleloVal);
                const matchN = (nivelVal === null || c.idNivel === nivelVal);
                if (c.idTurno && matchG && matchP && matchN) {
                    turnosValidos.add(c.idTurno);
                }
            });

            let opcionesVisibles = [];
            let selEsValida = false;

            Array.from(selTurno.options).forEach(function (opt) {
                if (opt.value === '') {
                    opt.hidden = false; opt.disabled = false; opt.style.display = '';
                    return;
                }
                const idT = parseInt(opt.value, 10);
                if (turnosValidos.has(idT)) {
                    opt.hidden = false; opt.disabled = false; opt.style.display = '';
                    opcionesVisibles.push(opt);
                    if (turnoVal === idT) selEsValida = true;
                } else {
                    opt.hidden = true; opt.disabled = true; opt.style.display = 'none';
                }
            });

            if (turnoVal !== null && !selEsValida) {
                selTurno.value = '';
                turnoVal = null;
            }

            if (opcionesVisibles.length === 1 && (selTurno.value === '' || !selEsValida)) {
                selTurno.value = opcionesVisibles[0].value;
                turnoVal = parseInt(selTurno.value, 10);
                seHizoSeleccionAuto = true;
            }
        }

        // Si se realizó alguna autoselección, disparar eventos para actualizar idCurso y resolutores
        if (seHizoSeleccionAuto) {
            const evt = new Event('change', { bubbles: true });
            [selGrado, selParalelo, selNivel, selTurno].forEach(function (s) {
                if (s && s.value !== '') s.dispatchEvent(evt);
            });
        }

    } finally {
        isUpdatingOpcionesCurso = false;
    }
}

function inicializarFiltrosCurso() {
    const selectoresCurso = document.querySelectorAll('[data-tipo="grado"], [data-tipo="paralelo"], [data-tipo="nivel"], [data-tipo="turno"], select[name="grado"], select[name="paralelo"], select[name="nivel"], select[name="turno"]');
    const contenedoresProcesados = new Set();

    selectoresCurso.forEach(function (sel) {
        const container = sel.closest('form') || sel.closest('.tabla-toolbar') || sel.closest('.card-panel') || sel.closest('.row') || sel.parentElement.parentElement;
        if (container && !contenedoresProcesados.has(container)) {
            contenedoresProcesados.add(container);
            actualizarOpcionesCurso(container);
        }
    });
}

// Escuchar cambios en cualquiera de los 4 selectores (Grado, Paralelo, Nivel, Turno)
document.addEventListener('change', function (e) {
    if (!e.target || isUpdatingOpcionesCurso) return;
    const isCursoSelector = e.target.matches('[data-tipo="grado"], [data-tipo="paralelo"], [data-tipo="nivel"], [data-tipo="turno"]')
        || ['grado', 'paralelo', 'nivel', 'turno'].includes(e.target.name);

    if (isCursoSelector) {
        const container = e.target.closest('form') || e.target.closest('.tabla-toolbar') || e.target.closest('.card-panel') || e.target.closest('.row') || e.target.parentElement.parentElement;
        if (container) {
            actualizarOpcionesCurso(container);
        }
    }
});

// Inicializar al cargar el DOM
document.addEventListener('DOMContentLoaded', function () {
    inicializarFiltrosCurso();
});

// Inicializar al abrir modales
document.addEventListener('show.bs.modal', function (e) {
    if (e.target) {
        const selectoresCurso = e.target.querySelectorAll('[data-tipo="grado"], [data-tipo="paralelo"], [data-tipo="nivel"], [data-tipo="turno"], select[name="grado"], select[name="paralelo"], select[name="nivel"], select[name="turno"]');
        const contenedoresProcesados = new Set();
        selectoresCurso.forEach(function (sel) {
            const container = sel.closest('form') || sel.closest('.row') || sel.parentElement.parentElement;
            if (container && !contenedoresProcesados.has(container)) {
                contenedoresProcesados.add(container);
                actualizarOpcionesCurso(container);
            }
        });
    }
});
