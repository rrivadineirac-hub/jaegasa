/**
 * asistencia_qr_manual.js — Control del Escáner QR Integrado en Asistencia Manual
 * Sistema Académico JAEGASA
 */

document.addEventListener('DOMContentLoaded', function () {
    const selectCamaras = document.getElementById('selectCamaras');
    const btnIniciar = document.getElementById('btnIniciarEscaner');
    const btnPausar = document.getElementById('btnPausarEscaner');
    const fechaControl = document.getElementById('fechaControl');
    const horaLimite = document.getElementById('horaLimite');

    const readerContainer = document.getElementById('reader-container');
    const scannerOverlay = document.getElementById('scanner-overlay');
    const overlayTitle = scannerOverlay ? scannerOverlay.querySelector('.overlay-title') : null;

    let html5QrCode = null;
    let escaneando = false;
    let bloqueadoPorCooldown = false;

    // ------------------------------------------------------------
    // 1. Sintetizador de Tonos de Alerta (Web Audio API)
    // ------------------------------------------------------------
    function reproducirSonido(tipo) {
        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) return;

            const ctx = new AudioContextClass();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.connect(gain);
            gain.connect(ctx.destination);

            if (tipo === 'exito') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(987.77, ctx.currentTime);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.12);
                osc.start();
                osc.stop(ctx.currentTime + 0.15);
            } else {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(150.00, ctx.currentTime);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.38);
                osc.start();
                osc.stop(ctx.currentTime + 0.4);
            }
        } catch (e) {
            console.warn('AudioContext falló: ', e);
        }
    }

    // ------------------------------------------------------------
    // 2. Estado inicial de la interfaz
    // ------------------------------------------------------------
    function prepararInterfaz() {
        if (btnIniciar) btnIniciar.disabled = false;
        if (btnPausar) btnPausar.disabled = true;
        if (selectCamaras) {
            selectCamaras.innerHTML = 
                '<option value="environment" selected>Cámara Principal / Trasera</option>' +
                '<option value="user">Cámara Frontal</option>';
        }
    }

    prepararInterfaz();

    // ------------------------------------------------------------
    // 3. Controladores de Encendido / Apagado
    // ------------------------------------------------------------
    if (btnIniciar) btnIniciar.addEventListener('click', iniciarLector);
    if (btnPausar) btnPausar.addEventListener('click', detenerLector);

    function intentarIniciarCamara(config) {
        return html5QrCode.start(
            config,
            {
                fps: 10,
                qrbox: function (width, height) {
                    const min = Math.min(width, height);
                    return {
                        width: Math.floor(min * 0.7),
                        height: Math.floor(min * 0.7)
                    };
                }
            },
            onQrCodeDetected,
            onQrError
        );
    }

    function iniciarLector() {
        if (escaneando) return;

        if (btnIniciar) btnIniciar.disabled = true;

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("reader");
        }

        let cameraConfig = (selectCamaras && selectCamaras.value) ? selectCamaras.value : "environment";
        if (cameraConfig === "environment" || cameraConfig === "user") {
            cameraConfig = { facingMode: cameraConfig };
        }

        if (readerContainer) readerContainer.classList.remove('escaner-pausado');

        intentarIniciarCamara(cameraConfig)
            .then(function () {
                escaneando = true;
                if (btnIniciar) btnIniciar.disabled = true;
                if (selectCamaras) selectCamaras.disabled = true;
                if (btnPausar) btnPausar.disabled = false;

                return Html5Qrcode.getCameras();
            })
            .then(function (cameras) {
                if (cameras && cameras.length > 0 && selectCamaras) {
                    const valPrev = selectCamaras.value;
                    selectCamaras.innerHTML = '';
                    cameras.forEach(function (cam, idx) {
                        const opt = document.createElement('option');
                        opt.value = cam.id;
                        opt.textContent = cam.label || ('Cámara ' + (idx + 1));
                        selectCamaras.appendChild(opt);
                    });
                    if (valPrev && selectCamaras.querySelector('option[value="' + valPrev + '"]')) {
                        selectCamaras.value = valPrev;
                    }
                }
            })
            .catch(function (err) {
                console.warn("Primer intento de cámara falló, reintentando con configuración de respaldo...", err);

                setTimeout(function () {
                    intentarIniciarCamara({ facingMode: "user" })
                        .then(function () {
                            escaneando = true;
                            if (btnIniciar) btnIniciar.disabled = true;
                            if (selectCamaras) selectCamaras.disabled = true;
                            if (btnPausar) btnPausar.disabled = false;
                        })
                        .catch(function (err2) {
                            console.error("Error definitivo al iniciar lector: ", err2);
                            escaneando = false;
                            if (btnIniciar) btnIniciar.disabled = false;
                            if (selectCamaras) selectCamaras.disabled = false;

                            const esAbort = (err2 && (err2.name === 'AbortError' || (err2.message && err2.message.includes('Timeout'))));
                            if (esAbort) {
                                alert("⚠️ La cámara física está ocupada por otra aplicación o pestaña del navegador.\n\nPasos para solucionar:\n1. Cierra otras pestañas del navegador donde tengas abierta la asistencia.\n2. Cierra programas como Zoom, Teams, WhatsApp o la app Cámara.\n3. Reinicia tu navegador web.");
                            } else {
                                alert("No se pudo iniciar el escáner de cámara: " + (err2.message || err2));
                            }
                        });
                }, 400);
            });
    }

    function detenerLector() {
        if (!html5QrCode || !escaneando) return;

        html5QrCode.stop().then(function () {
            escaneando = false;
            if (btnIniciar) btnIniciar.disabled = false;
            if (selectCamaras) selectCamaras.disabled = false;
            if (btnPausar) btnPausar.disabled = true;
            if (readerContainer) readerContainer.classList.add('escaner-pausado');
        }).catch(function (err) {
            console.error("Error deteniendo cámara: ", err);
        });
    }

    function onQrError(msg) { }

    // ------------------------------------------------------------
    // 4. Lógica de Marcación y Consulta de Estudiante
    // ------------------------------------------------------------
    function onQrCodeDetected(decodedText, decodedResult) {
        if (bloqueadoPorCooldown) return;
        bloqueadoPorCooldown = true;

        const rude = decodedText.trim();
        const filaEstudiante = document.querySelector('tr.fila-estudiante-asistencia[data-rude="' + rude + '"]');

        if (filaEstudiante) {
            marcarAsistenciaLocal(filaEstudiante, rude);
        } else {
            registrarYAbrirCurso(rude);
        }
    }

    function marcarAsistenciaLocal(fila, rude) {
        const nombreEl = fila.querySelector('.estudiante-nombre');
        const nombre = nombreEl ? nombreEl.textContent.trim() : 'Estudiante';

        const ahora = new Date();
        const hrsActual = ahora.getHours();
        const minsActual = ahora.getMinutes();

        const limitStr = (horaLimite ? horaLimite.value : "08:00") || "08:00";
        const parts = limitStr.split(':');
        const limitHrs = parseInt(parts[0], 10) || 8;
        const limitMins = parseInt(parts[1], 10) || 0;

        let estado = 'A';

        if (hrsActual > limitHrs || (hrsActual === limitHrs && minsActual > limitMins)) {
            estado = 'R';
        }

        const radioTarget = fila.querySelector('.radio-estado-asist[value="' + estado + '"]');

        if (radioTarget) {
            radioTarget.checked = true;

            if (estado === 'R') {
                const inputHora = fila.querySelector('.input-hora-llegada');
                if (inputHora) {
                    const hrsString = String(hrsActual).padStart(2, '0');
                    const minsString = String(minsActual).padStart(2, '0');
                    inputHora.value = hrsString + ':' + minsString;
                }
            }

            radioTarget.dispatchEvent(new Event('change'));
        }

        reproducirSonido('exito');
        mostrarDestello('exito', nombre + ' (' + (estado === 'R' ? 'Retraso' : 'Presente') + ')');

        fila.classList.add('fila-escaneada-exito');
        setTimeout(function () {
            fila.classList.remove('fila-escaneada-exito');
        }, 2500);

        fila.scrollIntoView({ behavior: 'smooth', block: 'center' });

        setTimeout(function () {
            bloqueadoPorCooldown = false;
        }, 1800);
    }

    function registrarYAbrirCurso(rude) {
        const fControl = fechaControl ? fechaControl.value : '';
        const hLimite = horaLimite ? horaLimite.value : '08:00';

        fetch('index.php?c=asistencia&a=registrarQr', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                rude: rude,
                fecha: fControl,
                horaMaxEntrada: hLimite
            })
        })
        .then(function (response) { return response.json(); })
        .then(function (res) {
            if (res.ok && res.datos && res.datos.idCurso) {
                reproducirSonido('exito');
                const txtEstado = res.datos.estado === 'R' ? 'Retraso' : (res.datos.estado === 'A' ? 'Presente' : 'Asistencia');
                mostrarDestello('exito', res.datos.nombre + ' (' + txtEstado + '). Abriendo curso...');
                
                // Redirigir al curso del estudiante para mostrar su asistencia en pantalla
                setTimeout(function () {
                    window.location.href = 'index.php?c=asistencia&idCurso=' + res.datos.idCurso + '&fecha=' + encodeURIComponent(fControl);
                }, 1200);
            } else {
                reproducirSonido('error');
                mostrarDestello('error', res.mensaje || 'Estudiante o código QR no encontrado.');
                setTimeout(function () {
                    bloqueadoPorCooldown = false;
                }, 2200);
            }
        })
        .catch(function (err) {
            console.error("Error al registrar QR:", err);
            reproducirSonido('error');
            mostrarDestello('error', 'Error al conectar con el servidor.');
            setTimeout(function () {
                bloqueadoPorCooldown = false;
            }, 2200);
        });
    }

    let destelloTimeout = null;

    if (scannerOverlay) {
        scannerOverlay.addEventListener('click', function () {
            scannerOverlay.hidden = true;
            if (destelloTimeout) {
                clearTimeout(destelloTimeout);
                destelloTimeout = null;
            }
        });
    }

    function mostrarDestello(tipo, texto) {
        if (!scannerOverlay || !overlayTitle) return;
        if (destelloTimeout) {
            clearTimeout(destelloTimeout);
        }
        scannerOverlay.className = 'scanner-overlay ' + tipo;
        overlayTitle.textContent = texto;
        scannerOverlay.hidden = false;

        const duracion = tipo === 'error' ? 5000 : 1600;
        destelloTimeout = setTimeout(function () {
            scannerOverlay.hidden = true;
            destelloTimeout = null;
        }, duracion);
    }

    // Iniciar automáticamente el lector QR al cargar la vista desde el inicio
    setTimeout(function () {
        iniciarLector();
    }, 400);
});
