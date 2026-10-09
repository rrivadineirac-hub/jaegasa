/* ============================================================
   login.js  —  Lógica del formulario de acceso (AJAX)
   Sistema Académico JAEGASA
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    const form    = document.getElementById('formLogin');
    const alerta  = document.getElementById('loginAlerta');
    const btn     = document.getElementById('btnLogin');
    const toggle  = document.getElementById('togglePass');
    const inputUser = document.getElementById('usuario');
    const inputPass = document.getElementById('password');

    // ---------- Limpiar campos al cargar o regresar (bfcache) ----------
    function vaciarCampos() {
        if (form) form.reset();
        if (inputUser) inputUser.value = '';
        if (inputPass) inputPass.value = '';
    }

    vaciarCampos();
    window.addEventListener('pageshow', (e) => {
        if (e.persisted) vaciarCampos();
    });

    // ---------- Mostrar / ocultar contraseña ----------
    if (toggle && inputPass) {
        toggle.addEventListener('click', () => {
            const oculto = inputPass.type === 'password';
            inputPass.type = oculto ? 'text' : 'password';
            toggle.querySelector('i').className = oculto ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }

    // ---------- Mostrar mensaje ----------
    function mostrarAlerta(mensaje, tipo) {
        alerta.textContent = mensaje;
        alerta.className = 'login-alerta ' + tipo;
    }

    function limpiarAlerta() {
        alerta.className = 'login-alerta';
        alerta.textContent = '';
    }

    // ---------- Envío del formulario ----------
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        limpiarAlerta();

        const datos = new FormData(form);

        // Estado de carga
        btn.classList.add('cargando');
        btn.disabled = true;

        try {
            const respuesta = await fetch(LOGIN_URL, {
                method: 'POST',
                body: datos,
                credentials: 'same-origin',      // enviar/recibir la cookie de sesión
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            // Leer como texto primero para poder mostrar el error real si NO es JSON
            const textoCrudo = await respuesta.text();
            let data;
            try {
                data = JSON.parse(textoCrudo);
            } catch (parseErr) {
                // El servidor devolvió algo que no es JSON (error PHP, aviso, HTML, etc.)
                console.error('Respuesta no válida del servidor:', textoCrudo);
                mostrarAlerta(
                    'El servidor devolvió una respuesta inesperada (HTTP ' +
                    respuesta.status + '). Revise la consola para más detalle.',
                    'error'
                );
                btn.classList.remove('cargando');
                btn.disabled = false;
                return;
            }

            // Si el servidor detectó salida inesperada, mostrarla en consola
            if (data.debug) {
                console.warn('[LOGIN] Salida inesperada capturada por el servidor:\n', data.debug);
            }

            if (data.ok) {
                mostrarAlerta(data.mensaje, 'exito');
                setTimeout(() => { window.location.href = data.redirect; }, 700);
            } else {
                mostrarAlerta(data.mensaje, 'error');
                btn.classList.remove('cargando');
                btn.disabled = false;
            }
        } catch (error) {
            console.error('Error de red en el login:', error);
            mostrarAlerta('Error de conexión con el servidor.', 'error');
            btn.classList.remove('cargando');
            btn.disabled = false;
        }
    });
});
