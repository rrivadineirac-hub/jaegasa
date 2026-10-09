document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('input[name="modoBusquedaRadio"]');
    const inputModoOculto = document.getElementById('inputModoBusqueda');
    const bloqueEstudiante = document.getElementById('bloqueEstudiante');
    const bloqueCurso = document.getElementById('bloqueCurso');
    const inputEstudiante = document.getElementById('inputBuscarEstudiante');
    const camposCurso = bloqueCurso.querySelectorAll('select, input');

    function actualizarModo() {
        const modo = document.querySelector('input[name="modoBusquedaRadio"]:checked').value;
        const esEstudiante = modo === 'estudiante';

        inputModoOculto.value = modo;

        bloqueEstudiante.classList.toggle('d-none', !esEstudiante);
        bloqueCurso.classList.toggle('d-none', esEstudiante);

        inputEstudiante.disabled = !esEstudiante;
        camposCurso.forEach(campo => { campo.disabled = esEstudiante; });

        // Limpia el bloque que se desactiva para no arrastrar datos viejos
        if (esEstudiante) {
            camposCurso.forEach(campo => { if (campo.tagName === 'SELECT') campo.value = ''; });
            document.getElementById('idCursoResuelto').value = '';
        } else {
            inputEstudiante.value = '';
        }
    }

    radios.forEach(r => r.addEventListener('change', function () {
        const modo = this.value;
        window.location.href = `index.php?c=inscripcion&modoBusqueda=${modo}`;
    }));
    actualizarModo(); // aplica el estado correcto al cargar la página

    // ============================================================
    // Resolver idCurso a partir de los selectores Grado, Paralelo, Nivel, Turno
    // ============================================================
    const selGrado    = bloqueCurso.querySelector('[data-tipo="grado"]');
    const selParalelo = bloqueCurso.querySelector('[data-tipo="paralelo"]');
    const selNivel    = bloqueCurso.querySelector('[data-tipo="nivel"]');
    const selTurno    = bloqueCurso.querySelector('[data-tipo="turno"]');
    const hiddenCurso = document.getElementById('idCursoResuelto');
    const avisoCurso  = bloqueCurso.querySelector('.cursoNoEncontrado');

    if (selGrado && selParalelo && selNivel && selTurno && hiddenCurso) {
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

            if (grado && paralelo && nivel && turno) {
                if (avisoCurso) avisoCurso.hidden = !!match;
            } else {
                if (avisoCurso) avisoCurso.hidden = true;
            }
        };

        [selGrado, selParalelo, selNivel, selTurno].forEach(function (s) {
            s.addEventListener('change', resolverCurso);
        });

        // Resolver inicialmente (útil si hay valores pre-seleccionados al cargar)
        resolverCurso();
    }
});