<?php
/**
 * ============================================================
 * mimportar_estudiantes.php  —  Modelo para Importación Excel por Curso
 * Sistema Académico JAEGASA
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloImportarEstudiantes
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Devuelve la lista de cursos activos con información detallada para el select.
     */
    public function listarCursos(): array
    {
        $sql = "SELECT c.idCurso,
                       c.idNivel,
                       c.idParalelo,
                       c.idTurno,
                       c.nombre AS cursoNombre,
                       n.nombre AS nivelNombre,
                       p.nombre AS paraleloNombre,
                       t.nombre AS turnoNombre,
                       g.anio AS gestionAnio,
                       CONCAT(c.nombre, ' ', p.nombre, ' de ', n.nombre, ' — ', t.nombre, ' (', g.anio, ')') AS descripcion
                FROM Curso c
                INNER JOIN Nivel n    ON n.idNivel    = c.idNivel
                INNER JOIN Paralelo p ON p.idParalelo = c.idParalelo
                INNER JOIN Turno t    ON t.idTurno    = c.idTurno
                INNER JOIN Gestion g  ON g.idGestion  = c.idGestion
                WHERE c.estado = 1
                ORDER BY g.anio DESC, n.nombre, c.nombre, p.nombre";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Obtiene los datos completos de un curso por su ID.
     */
    public function obtenerCursoInfo(int $idCurso): ?array
    {
        $sql = "SELECT c.idCurso, c.nombre AS cursoNombre,
                       n.nombre AS nivelNombre, p.nombre AS paraleloNombre,
                       t.nombre AS turnoNombre, g.anio AS gestionAnio
                FROM Curso c
                INNER JOIN Nivel n    ON n.idNivel    = c.idNivel
                INNER JOIN Paralelo p ON p.idParalelo = c.idParalelo
                INNER JOIN Turno t    ON t.idTurno    = c.idTurno
                INNER JOIN Gestion g  ON g.idGestion  = c.idGestion
                WHERE c.idCurso = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idCurso]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Devuelve el idAdministrativo del usuario en sesión (o 0 si no existe).
     */
    public function obtenerIdAdministrativo(int $idUsuario): int
    {
        $sql = "SELECT idAdministrativo FROM Administrativo WHERE idUsuario = :u AND estado = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':u' => $idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['idAdministrativo'] ?? 0);
    }

    /**
     * Verifica CIs y RUDES existentes en la base de datos.
     * Recibe un array de filas y retorna mapas de duplicados.
     */
    public function verificarDuplicados(array $cis, array $rudes): array
    {
        $cisExistentes = [];
        $rudesExistentes = [];

        $cisFiltrados = array_filter(array_map('trim', $cis));
        $rudesFiltrados = array_filter(array_map('trim', $rudes));

        if (!empty($cisFiltrados)) {
            $placeholders = implode(',', array_fill(0, count($cisFiltrados), '?'));
            $stmt = $this->db->prepare("SELECT ci FROM Estudiante WHERE ci IN ($placeholders) AND estado = 1");
            $stmt->execute(array_values($cisFiltrados));
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cisExistentes[$r['ci']] = true;
            }
        }

        if (!empty($rudesFiltrados)) {
            $placeholders = implode(',', array_fill(0, count($rudesFiltrados), '?'));
            $stmt = $this->db->prepare("SELECT rude FROM Estudiante WHERE rude IN ($placeholders) AND estado = 1");
            $stmt->execute(array_values($rudesFiltrados));
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $rudesExistentes[$r['rude']] = true;
            }
        }

        return [
            'cis'   => $cisExistentes,
            'rudes' => $rudesExistentes
        ];
    }

    /**
     * Procesa la importación en lote de estudiantes para un curso.
     */
    public function procesarImportacionLote(int $idCurso, int $idAdministrativo, array $estudiantes): array
    {
        $insertados = 0;
        $inscritos  = 0;
        $omitidos   = 0;
        $errores    = [];

        $stmtIns = $this->db->prepare("CALL InscribirEstudiante(:idEstudiante, :idCurso, :idAdministrativo, @idInscripcion, @mensaje)");


        foreach ($estudiantes as $index => $est) {
            $numFila = $index + 1;
            if (class_exists('Seguridad')) {
                $est = Seguridad::normalizarMayusculas($est);
            }
            $nombre = trim($est['nombre'] ?? '');
            $apPaterno = trim($est['apPaterno'] ?? '');
            $apMaterno = trim($est['apMaterno'] ?? '');
            $ci = trim($est['ci'] ?? '');
            $rude = trim($est['rude'] ?? '');


            if ($nombre === '' || ($apPaterno === '' && $apMaterno === '')) {
                $errores[] = "Fila #{$numFila}: Nombre o apellido requerido incompleto.";
                $omitidos++;
                continue;
            }

            try {
                // 1. Buscar si ya existe por CI o RUDE
                $idEstudiante = 0;
                if ($ci !== '') {
                    $stmtSearch = $this->db->prepare("SELECT idEstudiante FROM Estudiante WHERE ci = :ci AND estado = 1 LIMIT 1");
                    $stmtSearch->execute([':ci' => $ci]);
                    $idEstudiante = (int) ($stmtSearch->fetchColumn() ?: 0);
                }
                if ($idEstudiante === 0 && $rude !== '') {
                    $stmtSearch = $this->db->prepare("SELECT idEstudiante FROM Estudiante WHERE rude = :r AND estado = 1 LIMIT 1");
                    $stmtSearch->execute([':r' => $rude]);
                    $idEstudiante = (int) ($stmtSearch->fetchColumn() ?: 0);
                }

                // Normalizar fechaNacimiento a formato YYYY-MM-DD
                $fechaNac = null;
                if (!empty($est['fechaNac'])) {
                    $fRaw = trim((string) $est['fechaNac']);
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fRaw)) {
                        $fechaNac = $fRaw;
                    } elseif (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $fRaw, $m)) {
                        $fechaNac = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
                    } else {
                        $ts = strtotime($fRaw);
                        if ($ts !== false) {
                            $fechaNac = date('Y-m-d', $ts);
                        }
                    }
                }

                // 2. Si no existe, crearlo llamando al Stored Procedure InsertarEstudiante
                if ($idEstudiante === 0) {
                    $sqlCrear = "CALL InsertarEstudiante(
                                    :barrio, :localidad, :calle, :numero, :ubicacionGps,
                                    :rude, :apPaterno, :apMaterno, :nombre, :genero, :ci, :fechaNac,
                                    :provincia, :departamento, :nacionalidad, :qr,
                                    :tutorApPaterno, :tutorApMaterno, :tutorNombre, :tutorCi,
                                    :tutorTelefono, :tutorCorreo, :tutorParentesco, :tutorTipo,
                                    @idEstudiante, @idTutor, @mensaje
                                )";

                    $stmtCrear = $this->db->prepare($sqlCrear);
                    $stmtCrear->execute([
                        ':barrio'          => $est['barrio']          ?? null,
                        ':localidad'       => $est['localidad']       ?? null,
                        ':calle'           => $est['calle']           ?? null,
                        ':numero'          => $est['numero']          ?? null,
                        ':ubicacionGps'    => $est['ubicacionGps']    ?? null,
                        ':rude'            => $rude !== '' ? $rude : null,
                        ':apPaterno'       => $apPaterno,
                        ':apMaterno'       => $apMaterno,
                        ':nombre'          => $nombre,
                        ':genero'          => (strtoupper(trim($est['genero'] ?? '')) === 'F') ? 'F' : 'M',
                        ':ci'              => $ci !== '' ? $ci : null,
                        ':fechaNac'        => $fechaNac,
                        ':provincia'       => $est['provincia']       ?? null,
                        ':departamento'    => $est['departamento']    ?? null,
                        ':nacionalidad'    => !empty($est['nacionalidad']) ? $est['nacionalidad'] : 'BOLIVIANA',
                        ':qr'              => null,
                        ':tutorApPaterno'  => $est['tutorApPaterno']  ?? '',
                        ':tutorApMaterno'  => $est['tutorApMaterno']  ?? '',
                        ':tutorNombre'     => $est['tutorNombre']     ?? '',
                        ':tutorCi'         => !empty($est['tutorCi']) ? $est['tutorCi'] : null,
                        ':tutorTelefono'   => !empty($est['tutorTelefono']) ? $est['tutorTelefono'] : null,
                        ':tutorCorreo'     => !empty($est['tutorCorreo']) ? $est['tutorCorreo'] : null,
                        ':tutorParentesco' => !empty($est['tutorParentesco']) ? $est['tutorParentesco'] : 'PADRE/MADRE',
                        ':tutorTipo'       => 'Principal',
                    ]);

                    $stmtCrear->closeCursor();

                    $out = $this->db->query("SELECT @idEstudiante AS id, @mensaje AS mensaje")->fetch(PDO::FETCH_ASSOC);
                    $idEstudiante = (int) ($out['id'] ?? 0);

                    if ($idEstudiante > 0) {
                        $insertados++;
                    } else {
                        $errores[] = "Fila #{$numFila} ({$nombre} {$apPaterno}): " . ($out['mensaje'] ?? 'Error al crear estudiante.');
                        $omitidos++;
                        continue;
                    }
                }

                // 3. Inscribir al estudiante en el curso
                if ($idEstudiante > 0) {
                    // Comprobar si ya está inscrito en el curso en la gestión
                    $stmtCheckIns = $this->db->prepare("SELECT idInscripcion FROM Inscripcion WHERE idEstudiante = :idE AND idCurso = :idC AND estado = 1 LIMIT 1");
                    $stmtCheckIns->execute([':idE' => $idEstudiante, ':idC' => $idCurso]);
                    $existeInscripcion = (int) ($stmtCheckIns->fetchColumn() ?: 0);

                    if ($existeInscripcion === 0) {
                        $stmtIns->execute([
                            ':idEstudiante'     => $idEstudiante,
                            ':idCurso'          => $idCurso,
                            ':idAdministrativo' => $idAdministrativo > 0 ? $idAdministrativo : null
                        ]);
                        $stmtIns->closeCursor();
                        $this->db->query("SELECT @idInscripcion AS idInscripcion, @mensaje AS mensaje")->fetch(PDO::FETCH_ASSOC);
                        $inscritos++;
                    } else {

                        // Ya estaba inscrito en este curso
                        $inscritos++;
                    }
                }

            } catch (Throwable $e) {
                error_log("[IMPORTAR] Fila #{$numFila} error: " . $e->getMessage());
                $errores[] = "Fila #{$numFila} ({$nombre}): Error interno (" . $e->getMessage() . ")";
                $omitidos++;
            }
        }

        return [
            'insertados' => $insertados,
            'inscritos'  => $inscritos,
            'omitidos'   => $omitidos,
            'errores'    => $errores
        ];
    }

    /**
     * Lista los estudiantes actualmente inscritos en un curso específico.
     */
    public function listarEstudiantesCurso(int $idCurso, string $buscar = ''): array
    {
        $sql = "SELECT i.idInscripcion, i.fechaRegistro AS fechaInscripcion,
                       e.idEstudiante, e.rude, e.ci, e.nombre, e.apPaterno, e.apMaterno, e.genero, e.fechaNacimiento,
                       (SELECT CONCAT(t.apPaterno, ' ', t.apMaterno, ' ', t.nombre)
                        FROM Estudiante_Tutor et
                        INNER JOIN Tutor t ON t.idTutor = et.idTutor
                        WHERE et.idEstudiante = e.idEstudiante LIMIT 1) AS tutorNombre,
                       (SELECT t.telefono
                        FROM Estudiante_Tutor et
                        INNER JOIN Tutor t ON t.idTutor = et.idTutor
                        WHERE et.idEstudiante = e.idEstudiante LIMIT 1) AS tutorTelefono
                FROM Inscripcion i
                INNER JOIN Estudiante e ON e.idEstudiante = i.idEstudiante
                WHERE i.idCurso = :idCurso AND i.estado = 1 AND e.estado = 1";

        $params = [':idCurso' => $idCurso];

        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            $esSingleLetter = (mb_strlen(trim($buscar)) === 1 && !is_numeric(trim($buscar)));

            foreach ($palabras as $p) {
                $sub = [];
                $campos = ["e.nombre", "e.apPaterno", "e.apMaterno", "e.ci"];
                if (!$esSingleLetter || preg_match('/[0-9]/', $p)) {
                    $campos[] = "e.rude";
                }

                $esEnne = (mb_strtolower($p) === 'ñ');

                foreach ($campos as $campo) {
                    $k++;
                    $paramKey1 = ":qpk{$k}a";
                    $paramKey2 = ":qpk{$k}b";
                    if ($esEnne) {
                        $sub[] = "(BINARY {$campo} LIKE {$paramKey1} OR BINARY {$campo} LIKE {$paramKey2})";
                    } else {
                        $sub[] = "({$campo} LIKE {$paramKey1} OR {$campo} LIKE {$paramKey2})";
                    }
                    $params[$paramKey1] = $p . '%';
                    $params[$paramKey2] = '% ' . $p . '%';
                }
                $k++;
                $paramKey3 = ":qpk{$k}c";
                $sub[] = ($esEnne ? "BINARY " : "") . "CONCAT_WS(' ', e.apPaterno, e.apMaterno, e.nombre) LIKE {$paramKey3}";
                $params[$paramKey3] = $p . '%';

                $k++;
                $paramKey4 = ":qpk{$k}d";
                $sub[] = ($esEnne ? "BINARY " : "") . "CONCAT_WS(' ', e.nombre, e.apPaterno, e.apMaterno) LIKE {$paramKey4}";
                $params[$paramKey4] = $p . '%';

                $condicionesPalabras[] = "(" . implode(" OR ", $sub) . ")";
            }
            if (!empty($condicionesPalabras)) {
                $sql .= " AND " . implode(" AND ", $condicionesPalabras);
            }
        }

        $sql .= " ORDER BY e.apPaterno, e.apMaterno, e.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cancela la inscripción de un estudiante de un curso (baja lógica).
     */
    public function eliminarInscripcion(int $idInscripcion): bool
    {
        $stmt = $this->db->prepare("UPDATE Inscripcion SET estado = 0 WHERE idInscripcion = :id");
        return $stmt->execute([':id' => $idInscripcion]);
    }
}
