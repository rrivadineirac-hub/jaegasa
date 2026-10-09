<?php
/**
 * ============================================================
 * reset.php  —  Reinicio rápido de contraseñas (RAÍZ)
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Versión con URL corta y a prueba de errores de tipeo:
 *   http://localhost/SistemaAcademico/reset.php
 *
 * Asigna a TODOS los usuarios la misma contraseña, hasheada
 * correctamente con password_hash(). Elimine este archivo
 * después de usarlo.
 * ============================================================
 */

require_once __DIR__ . '/Config/Conexion.php';

// Contraseña por defecto para todos los usuarios (cumple políticas de seguridad)
$passwordPorDefecto = 'Jaegasa@2026';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Conexion::obtener();

    $usuarios = $db->query("SELECT idUsuario, nombre FROM Usuario ORDER BY idUsuario")->fetchAll();

    if (!$usuarios) {
        exit('<p style="font-family:sans-serif">No hay usuarios en la tabla Usuario.</p>');
    }

    // Un solo hash para la misma contraseña
    $hash = password_hash($passwordPorDefecto, PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE Usuario SET password = :pass WHERE idUsuario = :id");
    foreach ($usuarios as $u) {
        $stmt->execute([':pass' => $hash, ':id' => $u['idUsuario']]);
    }

    echo '<div style="font-family:sans-serif;max-width:640px;margin:40px auto;padding:26px;'
       . 'border:1px solid #d1d5db;border-radius:14px;background:#fff">';
    echo '<h2 style="color:#16a34a;margin-bottom:6px">Contraseñas regeneradas</h2>';
    echo '<p style="color:#374151">Se actualizaron <b>' . count($usuarios) . '</b> usuarios. '
       . 'Todos usan ahora la contraseña:</p>';
    echo '<p style="font-size:1.1rem"><b>Contraseña:</b> <code style="background:#f3f4f6;'
       . 'padding:3px 8px;border-radius:6px">' . htmlspecialchars($passwordPorDefecto) . '</code></p>';

    echo '<table style="width:100%;border-collapse:collapse;margin-top:14px;font-size:0.9rem">';
    echo '<thead><tr style="background:#1D8AB5;color:#fff">'
       . '<th style="padding:8px;text-align:left">ID</th>'
       . '<th style="padding:8px;text-align:left">Usuario</th></tr></thead><tbody>';
    foreach ($usuarios as $u) {
        echo '<tr style="border-bottom:1px solid #e5e7eb">'
           . '<td style="padding:8px">' . (int) $u['idUsuario'] . '</td>'
           . '<td style="padding:8px">' . htmlspecialchars($u['nombre']) . '</td></tr>';
    }
    echo '</tbody></table>';

    echo '<p style="color:#b45309;margin-top:16px"><b>Seguridad:</b> elimine este archivo '
       . '(reset.php) al terminar.</p>';
    echo '<a href="index.php?c=login" style="display:inline-block;margin-top:8px;padding:10px 18px;'
       . 'background:#1D8AB5;color:#fff;border-radius:8px;text-decoration:none">Ir al login</a>';
    echo '</div>';

} catch (PDOException $e) {
    die('<p style="font-family:sans-serif;color:#dc2626">Error: '
        . htmlspecialchars($e->getMessage()) . '</p>');
}
