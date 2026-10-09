<?php

// Aviso si la sesión se cerró por inactividad o si el acceso está bloqueado
$ahora = time();
$bloqueadoHasta = (int) ($_SESSION['login_bloqueado_hasta'] ?? 0);
$avisoSesion = '';

if ($bloqueadoHasta > $ahora) {
    $minutosRestantes = (int) ceil(($bloqueadoHasta - $ahora) / 60);
    $avisoSesion = "Acceso bloqueado por 6 intentos fallidos. Por favor espere {$minutosRestantes} minuto(s) para reintentar.";
} elseif (isset($_GET['sesion']) && $_GET['sesion'] === 'expirada') {
    $avisoSesion = 'Tu sesión expiró por inactividad. Vuelve a iniciar sesión.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso | Sistema Académico JAEGASA</title>

    <!-- Logo del colegio como icono de la pestaña -->
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>inagenes/logos/Logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="<?= BASE_URL ?>inagenes/logos/Logo.jpeg">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Estilos del login -->
    <link rel="stylesheet" href="<?= BASE_URL ?>Css/login.css">
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">

            <!-- Marca -->
            <div class="login-avatar">
                <img src="<?= BASE_URL ?>inagenes/logos/Logo.jpeg" alt="Logo del colegio" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" onerror="this.outerHTML='<i class=\'bi bi-person\'></i>'">
            </div>
            <h1 class="login-title">JAEGASA</h1>
            <p class="login-sub">Prof. Jaime Alfonso Escalante Gutierrez "A"</p>
            <p class="login-motto">Ciencia &bull; Honor &bull; Disciplina</p>

            <!-- Formulario -->
            <form id="formLogin" class="login-form" autocomplete="off">

                <div id="loginAlerta" class="login-alerta<?= $avisoSesion !== '' ? ' error' : '' ?>" role="alert"><?= htmlspecialchars($avisoSesion) ?></div>

                <div class="input-group">
                    <span class="input-icon"><i class="bi bi-envelope"></i></span>
                    <input type="text" id="usuario" name="usuario" value=""
                           placeholder="Usuario" autocomplete="off" required autofocus>
                </div>

                <div class="input-group">
                    <span class="input-icon"><i class="bi bi-lock"></i></span>
                    <input type="password" id="password" name="password" value=""
                           placeholder="Contraseña" autocomplete="new-password" required>
                    <span class="input-toggle" id="togglePass">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>

                <div class="login-options">
                    <label class="check-recordar">
                        <input type="checkbox" id="recordar" name="recordar">
                        <span>Recordarme</span>
                    </label>
                    <a href="#" class="link-olvido">¿Olvidó su contraseña?</a>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <span class="btn-text">Ingresar</span>
                    <span class="btn-spinner"></span>
                </button>
            </form>

            <!-- Pie -->
            <div class="login-footer">
                &copy; <?= date('Y') ?> JAEGASA — Todos los derechos reservados
            </div>
        </div>
    </div>

    <!-- Endpoint de autenticación para el JS -->
    <script>
        const LOGIN_URL = "<?= BASE_URL ?>index.php?c=login&a=autenticar";
    </script>
    <script src="<?= BASE_URL ?>Js/login.js"></script>

</body>
</html>

