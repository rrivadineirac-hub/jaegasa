<?php
/**
 * ============================================================
 * instalar.php  —  Script de instalación inicial (SEED)
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Crea el usuario ADMINISTRATIVO inicial usando el stored
 * procedure sp_InsertarAdministrativo, con la contraseña
 * correctamente hasheada mediante password_hash().
 *
 * USO:  abrir en el navegador una sola vez:
 *       http://localhost/SistemaAcademico/Config/instalar.php
 *
 * IMPORTANTE: elimine o renombre este archivo después de usarlo.
 * ============================================================
 */

require_once __DIR__ . '/Conexion.php';

// ------------------------------------------------------------
// Credenciales del administrativo inicial (CAMBIAR luego)
// ------------------------------------------------------------
$usuarioAcceso = 'admin';
$passwordPlano = 'Admin@1234';   // <-- contraseña inicial que cumple políticas de seguridad
$hash          = password_hash($passwordPlano, PASSWORD_DEFAULT);

try {
    $db = Conexion::obtener();

    // ¿Ya existe el usuario admin?
    $chk = $db->prepare("SELECT idUsuario FROM Usuario WHERE nombre = :n LIMIT 1");
    $chk->execute([':n' => $usuarioAcceso]);

    if ($chk->fetch()) {
        exit("<h3 style='font-family:sans-serif;color:#b45309'>"
            . "El usuario '{$usuarioAcceso}' ya existe. No se realizaron cambios.</h3>");
    }

    // Llamar al stored procedure sp_InsertarAdministrativo
    $sql = "CALL sp_InsertarAdministrativo(
                :nombreUsuario, :password,
                :apPaterno, :apMaterno, :nombre, :ci,
                :nitem, :rda, :categoria, :genero, :fechaNac,
                :correo, :telefono, :direccion, :nacionalidad,
                @idUsuario, @idAdministrativo, @mensaje
            )";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':nombreUsuario' => $usuarioAcceso,
        ':password'      => $hash,
        ':apPaterno'     => 'Sistema',
        ':apMaterno'     => 'JAEGASA',
        ':nombre'        => 'Administrativo',
        ':ci'            => '00000000',
        ':nitem'         => null,
        ':rda'           => null,
        ':categoria'     => null,
        ':genero'        => 'M',
        ':fechaNac'      => null,
        ':correo'        => 'admin@jaegasa.edu.bo',
        ':telefono'      => null,
        ':direccion'     => null,
        ':nacionalidad'  => 'Boliviana',
    ]);
    $stmt->closeCursor();

    // Recuperar mensaje de salida del procedimiento
    $out = $db->query("SELECT @idUsuario AS idUsuario, @mensaje AS mensaje")->fetch();

    echo "<div style='font-family:sans-serif;max-width:520px;margin:40px auto;"
       . "padding:24px;border:1px solid #d1d5db;border-radius:12px'>";
    echo "<h2 style='color:#16a34a'>Instalación completada</h2>";
    echo "<p><b>Resultado:</b> " . htmlspecialchars($out['mensaje'] ?? 'sin mensaje') . "</p>";
    echo "<hr><p><b>Usuario:</b> {$usuarioAcceso}<br><b>Contraseña:</b> {$passwordPlano}</p>";
    echo "<p style='color:#b45309'><b>Elimine este archivo</b> (Config/instalar.php) "
       . "y cambie la contraseña por seguridad.</p>";
    echo "<a href='../index.php?c=login' style='display:inline-block;margin-top:8px;"
       . "padding:10px 18px;background:#1e3a8a;color:#fff;border-radius:8px;"
       . "text-decoration:none'>Ir al login</a>";
    echo "</div>";

} catch (PDOException $e) {
    die('<h3 style="font-family:sans-serif;color:#dc2626">Error en la instalación: '
        . htmlspecialchars($e->getMessage()) . '</h3>');
}
