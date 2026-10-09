/**
 * asistencia_qr.js — Lógica interactiva del Escáner QR de Asistencia
 * Sistema Académico JAEGASA
 */

document.addEventListener('DOMContentLoaded', function () {
    // Referencias del DOM
    const selectCamaras = document.getElementById('selectCamaras');
    const btnIniciar = document.getElementById('btnIniciarEscaner');
    const btnPausar = document.getElementById('btnPausarEscaner');
    const fechaControl = document.getElementById('fechaControl');
    const horaLimite = document.getElementById('horaLimite');
    const formManual = document.getElementById('formManualRude');
    const manualInput = document.getElementById('manualRudeInput');
    
    const tarjetaEstudiante = document.getElementById('tarjetaEstudiante');
    const tableBody = document.getElementById('historialSesionBody');
    const txtContador = document.getElementById('contadorSesion');
    
    const readerContainer = document.getElementById('reader-container');
    const scannerOverlay = document.getElementById('scanner-overlay');
    const overlayTitle = scannerOverlay ? scannerOverlay.querySelector('.overlay-title') : null;
    
    let html5QrCode = null;
    let escaneando = false;
    let bloqueadoPorCooldown = false;
    let contadorSesion = 0;

    // ------------------------------------------------------------
    // 1. Sintetizador de Audio nativo (Web Audio API)
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
                osc.frequency.setValueAtTime(140.00, ctx.currentTime);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.38);
                osc.start();
                osc.stop(ctx.currentTime + 0.4);
            }
        } catch (e) {
            console.warn('No se pudo reproducir el sonido: ', e);
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
    // 3. Control de Encendido / Apagado del Escáner
    // ------------------------------------------------------------
    if (btnIniciar) btnIniciar.addEventListener('click', iniciarEscaner);
    if (btnPausar) btnPausar.addEventListener('click', detenerEscaner);

    function intentarIniciarCamara(config) {
        return html5QrCode.start(
            config,
            {
                fps: 10,
                qrbox: function (width, height) {
                    const min = Math.min(width, height);
                    return {
                        width: Math.floor(min * 0.65),
                        height: Math.floor(min * 0.65)
                    };
                }
            },
            onScanSuccess,
            onScanError
        );
    }

    function iniciarEscaner() {
        if (escaneando) return;
        if (btnIniciar) btnIniciar.disabled = true;
        if (!html5QrCode) html5QrCode = new Html5Qrcode("reader");

        Html5Qrcode.getCameras().then(function (cameras) {
            if (!cameras || cameras.length === 0) {
                throw new Error("No se detectó ninguna cámara en este dispositivo.");
            }
            // Usa el id real de la cámara en vez de facingMode
            const camId = (selectCamaras && selectCamaras.value && cameras.some(c => c.id === selectCamaras.value))
                ? selectCamaras.value
                : cameras[0].id;

            return intentarIniciarCamara({ deviceId: { exact: camId } });
        })
        .then(function () {
            escaneando = true;
            if (selectCamaras) selectCamaras.disabled = true;
            if (btnPausar) btnPausar.disabled = false;
        })
        .catch(function (err) {
            console.error("Error al iniciar lector de cámara: ", err);
            if (btnIniciar) btnIniciar.disabled = false;
            alert("No se pudo iniciar el escáner de cámara: " + (err.message || err));
        });
    }

        function detenerEscaner() {
            if (!html5QrCode || !escaneando) return;

            html5QrCode.stop().then(function () {
                escaneando = false;
                if (btnIniciar) btnIniciar.disabled = false;
                if (selectCamaras) selectCamaras.disabled = false;
                if (btnPausar) btnPausar.disabled = true;
                if (readerContainer) readerContainer.classList.add('escaner-pausado');
            }).catch(function (err) {
                console.error("Error al detener lector: ", err);
            });
    }

    function onScanError(errorMessage) {
        // Silencioso durante el escaneo
    }

    function onScanSuccess(decodedText, decodedResult) {
        if (bloqueadoPorCooldown) return;
        bloqueadoPorCooldown = true;
        
        const rude = decodedText.trim();
        procesarLecturaRude(rude);
    }

    // ------------------------------------------------------------
    // 4. Lógica de Comunicación AJAX con el Backend
    // ------------------------------------------------------------
    function procesarLecturaRude(rude) {
        const fecha = fechaControl.value;
        const horaMax = horaLimite.value;
        
        fetch('index.php?c=asistencia&a=registrarQr', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                rude: rude,
                fecha: fecha,
                horaMaxEntrada: horaMax
            })
        })
        .then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok) {
                    throw new Error(data.mensaje || 'Error al procesar asistencia.');
                }
                return data;
            });
        })
        .then(function (res) {
            if (res.datos && res.datos.yaTomada) {
                reproducirSonido('error');
                mostrarDestelloVisual('error', 'YA REGISTRADA: ' + res.datos.nombre);
            } else {
                reproducirSonido('exito');
                mostrarDestelloVisual('exito', res.datos.nombre);
            }
            cargarTarjetaEstudiante(res.datos);
            agregarAlHistorial(res.datos);
        })
        .catch(function (error) {
            reproducirSonido('error');
            mostrarDestelloVisual('error', error.message);
            console.error('[SCAN ERROR] ', error);
        })
        .finally(function () {
            setTimeout(function () {
                bloqueadoPorCooldown = false;
            }, 1800);
        });
    }

    // ------------------------------------------------------------
    // 5. Gestión del DOM y Efectos Visuales
    // ------------------------------------------------------------
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

    function mostrarDestelloVisual(tipo, texto) {
        if (!scannerOverlay || !overlayTitle) return;
        if (destelloTimeout) {
            clearTimeout(destelloTimeout);
        }
        scannerOverlay.className = 'scanner-overlay ' + tipo;
        overlayTitle.textContent = texto;
        scannerOverlay.hidden = false;
        
        const duracion = tipo === 'error' ? 3000 : 1500;
        destelloTimeout = setTimeout(function () {
            scannerOverlay.hidden = true;
            destelloTimeout = null;
        }, duracion);
    }

    function cargarTarjetaEstudiante(datos) {
        let textEstado = datos.estado === 'A' ? 'Presente' : (datos.estado === 'R' ? 'Retraso' : (datos.estado === 'F' ? 'Falta' : 'Licencia'));
        let badgeClass = datos.estado === 'A' ? 'badge-estado-A' : 'badge-estado-R';
        let iconoEstado = datos.estado === 'A' ? 'bi-check-circle-fill' : 'bi-clock-fill';
        let yaTomadaHtml = datos.yaTomada ? '<span class="badge bg-warning text-dark d-block mt-1 mb-1 fw-bold"><i class="bi bi-info-circle-fill"></i> ASISTENCIA YA REGISTRADA</span>' : '';

        tarjetaEstudiante.innerHTML = 
            '<div class="credencial-estudiante">' +
                '<div class="credencial-foto-contenedor">' +
                    '<img src="' + datos.foto + '" class="credencial-foto" alt="' + datos.nombre + '" onerror="this.src=\'inagenes/Estudiante/default.png\'">' +
                '</div>' +
                '<div class="credencial-datos">' +
                    '<div class="credencial-nombre">' + datos.nombre + '</div>' +
                    '<span class="credencial-rude">RUDE: ' + datos.rude + '</span>' +
                    '<div class="credencial-curso"><i class="bi bi-mortarboard-fill"></i> ' + datos.curso + '</div>' +
                    yaTomadaHtml +
                    '<div class="credencial-resultado">' +
                        '<span class="badge-estado ' + badgeClass + '">' +
                            '<i class="bi ' + iconoEstado + '"></i> ' + textEstado +
                        '</span>' +
                        '<span class="small text-muted font-monospace"><i class="bi bi-clock"></i> ' + datos.horaLlegada + '</span>' +
                    '</div>' +
                '</div>' +
            '</div>';
    }

    function agregarAlHistorial(datos) {
        const vacioRow = document.getElementById('historialVacio');
        if (vacioRow) {
            vacioRow.remove();
        }
        
        let textEstado = datos.estado === 'A' ? 'A' : (datos.estado === 'R' ? 'R' : (datos.estado === 'F' ? 'F' : 'L'));
        let badgeColor = datos.estado === 'A' ? 'bg-success' : 'bg-warning text-dark';
        let tituloEstado = datos.estado === 'A' ? 'Presente' : 'Retraso';
        
        const newRow = document.createElement('tr');
        newRow.innerHTML = 
            '<td class="font-monospace fw-semibold text-jaegasa">' + datos.horaLlegada + '</td>' +
            '<td><img src="' + datos.foto + '" class="historial-foto" alt="Foto" onerror="this.src=\'inagenes/Estudiante/default.png\'"></td>' +
            '<td>' +
                '<span class="fw-semibold text-dark d-block" style="font-size:0.9rem;">' + datos.nombre + '</span>' +
                '<span class="text-muted small" style="font-size:0.75rem;">RUDE: ' + datos.rude + '</span>' +
            '</td>' +
            '<td class="small text-muted">' + datos.curso + '</td>' +
            '<td>' +
                '<span class="badge ' + badgeColor + '" title="' + tituloEstado + '" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;font-weight:700;">' +
                    textEstado +
                '</span>' +
            '</td>';
        
        tableBody.insertBefore(newRow, tableBody.firstChild);
        
        contadorSesion++;
        txtContador.textContent = contadorSesion;
    }

    if (formManual) {
        formManual.addEventListener('submit', function (e) {
            e.preventDefault();
            
            const rudeVal = manualInput.value.trim();
            if (!rudeVal) return;
            
            procesarLecturaRude(rudeVal);
            
            manualInput.value = '';
            manualInput.focus();
        });
    }
});
