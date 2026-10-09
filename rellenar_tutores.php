<?php
/**
 * ============================================================
 * rellenar_tutores.php  —  Generador Masivo de Padres/Madres/Tutores
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Este script examina todos los estudiantes en la base de datos
 * y asigna automáticamente a un Padre, Madre o Tutor legal a
 * aquellos estudiantes que aún no tengan una relación registrada
 * en la tabla Estudiante_Tutor.
 *
 * USO:
 *   - Desde Navegador Web: http://localhost/Sistema_Fluter/rellenar_tutores.php
 *   - Desde Línea de Comandos: php rellenar_tutores.php
 * ============================================================
 */

require_once __DIR__ . '/Config/Conexion.php';

// Configuración de visualización y encabezados
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
}

echo $isCli ? "=== GENERADOR MASIVO DE TUTORES ===\n\n" : "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Poblador de Tutores - JAEGASA</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f3f4f6; color:#1f2937; margin:0; padding:30px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 25px 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        h1 { color: #1e3a8a; font-size: 1.6rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; margin-top: 0; }
        .stat-card { display: inline-block; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 18px; margin: 5px 10px 15px 0; text-align: center; }
        .stat-card .num { font-size: 1.5rem; font-weight: bold; color: #1d4ed8; }
        .stat-card .label { font-size: 0.85rem; color: #4b5563; }
        .log-box { background: #1e293b; color: #38bdf8; font-family: monospace; font-size: 0.88rem; padding: 15px; border-radius: 8px; max-height: 400px; overflow-y: auto; margin-top: 15px; }
        .success { color: #22c55e; }
        .btn { display: inline-block; background: #1d4ed8; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 15px; }
        .btn:hover { background: #1e40af; }
    </style>
</head>
<body>
<div class='container'>
<h1>Poblador Automático de Padres/Madres/Tutores</h1>
";

// Listas de nombres típicos para generar tutores verosímiles
$nombresVarones = ['CARLOS', 'JUAN', 'PEDRO', 'LUIS', 'MARCO', 'ROBERTO', 'JORGE', 'FERNANDO', 'EDGAR', 'GONZALO', 'OSCAR', 'RAMIRO', 'MARCELO', 'GUSTAVO', 'VICTOR', 'DANIEL', 'MIGUEL', 'DAVID', 'HUGO', 'RAUL'];
$nombresMujeres = ['MARIA', 'ANA', 'JUANA', 'ELENA', 'PATRICIA', 'CARMEN', 'ROSA', 'SILVIA', 'ELIZABETH', 'MONICA', 'SONIA', 'ROXANA', 'LIDIA', 'SOCIETY', 'BEATRIZ', 'VERONICA', 'GLADYS', 'NANCY', 'LILIAN', 'TERESA'];
$apellidosComunes = ['FLORES', 'RODRIGUEZ', 'GARCIA', 'LOPEZ', 'MENDOZA', 'VARGAS', 'CASTRO', 'FERNANDEZ', 'CHAVEZ', 'TORRES', 'QUISPE', 'MAMANI', 'GUTIERREZ', 'ROJAS', 'ALVAREZ', 'ROMERO', 'GONZALES', 'SUAREZ', 'SILVA', 'RIVERA'];
$profesiones = ['COMERCIANTE', 'PROFESOR', 'CONDUCTOR', 'INGENIERO', 'ABOGADO', 'CONTADOR', 'MEDICO', 'EMPLEADO INDEPENDIENTE', 'TÉCNICO EN COMPUTACIÓN', 'ENFERMERA', 'ARQUITECTO', 'MECÁNICO'];

try {
    $db = Conexion::obtener();
    $db->beginTransaction();

    // 1. Obtener todos los estudiantes que NO tienen tutor en Estudiante_Tutor
    $sqlEstSinTutor = "SELECT e.idEstudiante, e.nombre, e.apPaterno, e.apMaterno, e.ci
                       FROM Estudiante e
                       WHERE e.idEstudiante NOT IN (SELECT DISTINCT idEstudiante FROM Estudiante_Tutor)
                       ORDER BY e.idEstudiante";

    $estudiantes = $db->query($sqlEstSinTutor)->fetchAll(PDO::FETCH_ASSOC);
    $totalSinTutor = count($estudiantes);

    $tutoresCreados = 0;
    $relacionesCreadas = 0;

    if ($totalSinTutor === 0) {
        $msg = "Todos los estudiantes ya cuentan con un Tutor/Padre/Madre asignado en la base de datos.";
        echo $isCli ? "$msg\n" : "<p style='color:#16a34a;font-weight:bold;'>$msg</p>";
    } else {
        echo $isCli ? "Estudiantes sin tutor encontrados: $totalSinTutor\nGenerando tutores...\n\n" : "
        <div class='stat-card'><div class='num'>$totalSinTutor</div><div class='label'>Estudiantes a procesar</div></div>
        <div class='log-box'>";

        $stmtInsTutor = $db->prepare("INSERT INTO Tutor (apPaterno, apMaterno, nombre, parentesco, tutor, ci, telefono, correo, direccion, profesion, ocupacion, tipo, fechaRegistro, estado)
                                      VALUES (:apPat, :apMat, :nom, :parentesco, :tutorNom, :ci, :tel, :correo, :dir, :prof, :ocup, :tipo, NOW(), 1)");

        $stmtInsRel = $db->prepare("INSERT IGNORE INTO Estudiante_Tutor (idEstudiante, idTutor, fechaRegistro) VALUES (:est, :tut, NOW())");

        foreach ($estudiantes as $est) {
            $idEst = (int) $est['idEstudiante'];
            $apPatEst = trim((string) ($est['apPaterno'] ?? ''));
            $apMatEst = trim((string) ($est['apMaterno'] ?? ''));

            // Determinar si crearemos PADRE (50%) o MADRE (50%)
            $esPadre = (rand(0, 1) === 1);
            $parentesco = $esPadre ? 'PADRE' : 'MADRE';

            if ($esPadre) {
                $nombreTutor = $nombresVarones[array_rand($nombresVarones)];
                // El Padre comparte el apellido paterno del estudiante
                $apPaternoTutor = $apPatEst !== '' ? $apPatEst : $apellidosComunes[array_rand($apellidosComunes)];
                $apMaternoTutor = $apellidosComunes[array_rand($apellidosComunes)];
            } else {
                $nombreTutor = $nombresMujeres[array_rand($nombresMujeres)];
                // La Madre comparte el apellido materno del estudiante
                $apPaternoTutor = $apMatEst !== '' ? $apMatEst : $apellidosComunes[array_rand($apellidosComunes)];
                $apMaternoTutor = $apellidosComunes[array_rand($apellidosComunes)];
            }

            $nombreCompletoTutor = trim("$nombreTutor $apPaternoTutor $apMaternoTutor");

            // Generar CI único (7-8 dígitos aleatorios)
            $ciGenerado = (string) rand(4000000, 9999999);
            // Verificar si el CI ya existe en Tutor
            $chkCi = $db->prepare("SELECT idTutor FROM Tutor WHERE ci = :ci LIMIT 1");
            $chkCi->execute([':ci' => $ciGenerado]);
            if ($chkCi->fetch()) {
                $ciGenerado .= rand(1, 9);
            }

            // Teléfono aleatorio boliviano (7XXXXXXX)
            $telefonoGenerado = "7" . rand(1000000, 9999999);
            $profesionGenerada = $profesiones[array_rand($profesiones)];
            $correoGenerado = strtolower(substr($nombreTutor, 0, 1) . $apPaternoTutor . rand(10, 99) . "@gmail.com");
            $direccionGenerada = "Av. Principal # " . rand(10, 500);

            // Insertar Tutor
            $stmtInsTutor->execute([
                ':apPat'      => $apPaternoTutor,
                ':apMat'      => $apMaternoTutor,
                ':nom'        => $nombreTutor,
                ':parentesco' => $parentesco,
                ':tutorNom'   => $nombreCompletoTutor,
                ':ci'         => $ciGenerado,
                ':tel'        => $telefonoGenerado,
                ':correo'     => $correoGenerado,
                ':dir'        => $direccionGenerada,
                ':prof'       => $profesionGenerada,
                ':ocup'       => $profesionGenerada,
                ':tipo'       => $parentesco,
            ]);

            $idTutorNuevo = (int) $db->lastInsertId();
            $tutoresCreados++;

            // Vincular Estudiante <-> Tutor
            $stmtInsRel->execute([
                ':est' => $idEst,
                ':tut' => $idTutorNuevo
            ]);
            $relacionesCreadas++;

            $logMsg = "[✓] Estudiante ID $idEst ({$est['nombre']} {$est['apPaterno']}) -> Asignado $parentesco: $nombreCompletoTutor (CI: $ciGenerado, Tel: $telefonoGenerado)";
            if ($isCli) {
                echo "$logMsg\n";
            } else {
                echo "<div>$logMsg</div>";
            }
        }

        if (!$isCli) {
            echo "</div>";
        }
    }

    $db->commit();

    $resumenHtml = "
    <div style='margin-top:20px;'>
        <div class='stat-card'><div class='num'>$tutoresCreados</div><div class='label'>Tutores Creados</div></div>
        <div class='stat-card'><div class='num'>$relacionesCreadas</div><div class='label'>Relaciones Asignadas</div></div>
        <p class='success' style='font-size:1.1rem;font-weight:bold;'>¡Proceso completado con éxito! Todos los estudiantes ahora tienen registrado su Padre/Madre/Tutor.</p>
        <a href='index.php?c=tutor' class='btn'>Ver Lista de Tutores en el Sistema</a>
    </div>
    ";

    if ($isCli) {
        echo "\n=== RESUMEN DE PROCESAMIENTO ===\n";
        echo " - Tutores Nuevos Creados: $tutoresCreados\n";
        echo " - Relaciones Asignadas: $relacionesCreadas\n";
        echo "¡Proceso completado exitosamente!\n";
    } else {
        echo $resumenHtml;
        echo "</div></body></html>";
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $errMsg = "Error al procesar la asignación de tutores: " . $e->getMessage();
    if ($isCli) {
        echo "\n[ERROR] $errMsg\n";
    } else {
        echo "<p style='color:#dc2626;font-weight:bold;'>$errMsg</p></div></body></html>";
    }
}
