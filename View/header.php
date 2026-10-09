<?php
/**
 * ============================================================
 * header.php  —  Cabecera y menú lateral del panel
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Se incluye al inicio de cada vista protegida. Muestra la
 * barra superior, el menú lateral (según el rol) y abre el
 * contenedor del contenido.
 * ============================================================
 */

if (!isset($_SESSION['idUsuario'])) {
    header('Location: ' . BASE_URL . 'index.php?c=login');
    exit;
}

$rol            = $_SESSION['idRoles'] ?? 0;
$nombreCompleto = $_SESSION['nombreCompleto'] ?? $_SESSION['nombre'] ?? 'Usuario';
$rolNombre      = $_SESSION['rol'] ?? '';

if (!isset($_SESSION['cargo']) && isset($_SESSION['idUsuario'])) {
    require_once BASE_PATH . '/Model/mlogin.php';
    $mTemp = new ModeloLogin();
    $pTemp = $mTemp->perfil((int)$_SESSION['idUsuario']);
    $_SESSION['cargo'] = !empty($pTemp['cargo']) ? $pTemp['cargo'] : $rolNombre;
}

$cargoNombre = !empty($_SESSION['cargo']) ? $_SESSION['cargo'] : $rolNombre;

// Página activa para resaltar el menú
$paginaActiva = $_GET['c'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel | Sistema Académico JAEGASA</title>

    <!-- Logo del colegio como icono de la pestaña (Favicon) -->
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>inagenes/logos/Logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="<?= BASE_URL ?>inagenes/logos/Logo.jpeg">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>Css/panel.css?v=<?= @filemtime(BASE_PATH . '/Css/panel.css') ?>">
</head>
<body>

<div class="app-layout">

    <!-- ===================== Menú lateral (parcial) ===================== -->
    <?php require_once BASE_PATH . '/partes/menuLateral.php'; ?>

    <!-- ===================== Contenido ===================== -->
    <div class="app-main">

        <!-- Barra superior -->
        <header class="topbar">
            <button class="btn-toggle" id="btnToggle" aria-label="Menú">
                <i class="bi bi-list"></i>
            </button>

            <div class="topbar-user">
                <div class="user-info">
                    <span class="user-nombre"><?= htmlspecialchars($nombreCompleto) ?></span>
                    <span class="user-rol"><?= htmlspecialchars($cargoNombre) ?></span>
                </div>
                <div class="user-avatar">
                    <?php if (!empty($_SESSION['foto'])): ?>
                        <img src="<?= BASE_URL ?>inagenes/Admi/<?= htmlspecialchars($_SESSION['foto']) ?>" alt="Avatar"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <i class="bi bi-person-circle" style="display:none"></i>
                    <?php else: ?>
                        <i class="bi bi-person-circle"></i>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Aquí comienza el contenido de cada vista -->
        <main class="app-content">
            <?php require_once BASE_PATH . '/partes/flash.php'; ?>
