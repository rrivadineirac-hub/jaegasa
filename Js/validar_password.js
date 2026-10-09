/* ============================================================
   validar_password.js  —  Validación de contraseñas seguras (Cliente)
   Sistema Académico JAEGASA
   ============================================================ */

/**
 * Valida si una contraseña cumple con los requisitos de seguridad.
 * @param {string} password 
 * @returns {{ ok: boolean, errores: string[] }}
 */
function validarPasswordSegura(password) {
    const errores = [];
    if (password.length < 8) {
        errores.push("Mínimo 8 caracteres");
    }
    if (!/[A-Z]/.test(password)) {
        errores.push("Al menos una mayúscula (A-Z)");
    }
    if (!/[0-9]/.test(password)) {
        errores.push("Al menos un número (0-9)");
    }
    if (!/[^a-zA-Z0-9]/.test(password)) {
        errores.push("Al menos un símbolo (!@#$%^&*...)");
    }
    return {
        ok: errores.length === 0,
        errores: errores
    };
}

/**
 * Adjunta un escuchador de eventos a un input de contraseña para mostrar feedback visual en tiempo real.
 * @param {HTMLInputElement} inputEl 
 * @param {HTMLElement} feedbackEl 
 */
function vincularValidacionPassword(inputEl, feedbackEl) {
    if (!inputEl || !feedbackEl) return;

    inputEl.addEventListener('input', () => {
        const val = inputEl.value;
        if (val === '') {
            feedbackEl.innerHTML = '';
            return;
        }
        const res = validarPasswordSegura(val);
        if (res.ok) {
            feedbackEl.innerHTML = '<span class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>Contraseña segura</span>';
        } else {
            feedbackEl.innerHTML = '<span class="text-danger small"><i class="bi bi-exclamation-triangle-fill me-1"></i>Requiere: ' + res.errores.join(', ') + '</span>';
        }
    });
}
