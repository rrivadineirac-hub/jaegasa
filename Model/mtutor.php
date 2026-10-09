<?php
/**
 * ============================================================
 * mtutor.php  —  Modelo del CRUD Tutor
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Usa los stored procedures de la base de datos (SIN prefijo sp_):
 *   InsertarTutor  -> crear
 *   EditarTutor    -> editar
 *   EliminarTutor  -> eliminar (baja lógica)
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloTutor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Lista los tutores activos (con filtro de búsqueda opcional).
     */
    public function listar(string $buscar = ''): array
    {
        $params = [];
        $sql = "SELECT t.idTutor, t.apPaterno, t.apMaterno, t.nombre,
                       t.ci, t.telefono, t.correo, t.direccion,
                       t.parentesco, t.tipo, t.profesion, t.ocupacion,
                       t.fechaRegistro, t.idUsuario,
                       u.nombre AS usuario,
                       (SELECT COUNT(*) FROM Estudiante_Tutor et WHERE et.idTutor = t.idTutor) AS totalEstudiantes
                FROM Tutor t
                LEFT JOIN Usuario u ON u.idUsuario = t.idUsuario
                WHERE t.estado = 1";

        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            foreach ($palabras as $p) {
                $sub = [];
                $campos = [
                    "t.nombre", "t.apPaterno", "t.apMaterno", "t.ci", "t.telefono",
                    "CONCAT_WS(' ', t.apPaterno, t.apMaterno, t.nombre)",
                    "CONCAT_WS(' ', t.nombre, t.apPaterno, t.apMaterno)"
                ];
                foreach ($campos as $campo) {
                    $k++;
                    $paramKey = ":qpk{$k}";
                    $sub[] = "{$campo} LIKE {$paramKey}";
                    $params[$paramKey] = $p . '%';
                }
                $condicionesPalabras[] = "(" . implode(" OR ", $sub) . ")";
            }
            if (!empty($condicionesPalabras)) {
                $sql .= " AND " . implode(" AND ", $condicionesPalabras);
            }
        }

        $sql .= " ORDER BY t.apPaterno, t.apMaterno";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un tutor por su id.
     */
    public function obtener(int $id): ?array
    {
        $sql = "SELECT t.*, u.nombre AS usuario
                FROM Tutor t
                LEFT JOIN Usuario u ON u.idUsuario = t.idUsuario
                WHERE t.idTutor = :id AND t.estado = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Obtiene un tutor activo por su número de CI.
     */
    public function obtenerPorCi(string $ci): ?array
    {
        $ci = trim($ci);
        if ($ci === '') return null;

        $sql = "SELECT t.idTutor, t.apPaterno, t.apMaterno, t.nombre,
                       t.ci, t.telefono, t.correo, t.direccion,
                       t.parentesco, t.tipo, t.profesion, t.ocupacion
                FROM Tutor t
                WHERE t.ci = :ci AND t.estado = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ci' => $ci]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Verifica si un CI ya está en uso (excluye un id opcional).
     */
    public function ciEnUso(string $ci, int $exceptoId = 0): bool
    {
        if ($ci === '') return false;
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM Tutor
             WHERE ci = :ci AND estado = 1 AND idTutor <> :id"
        );
        $stmt->execute([':ci' => $ci, ':id' => $exceptoId]);
        return (int) ($stmt->fetch()['n'] ?? 0) > 0;
    }

    /**
     * Verifica si un nombre de usuario ya existe.
     */
    public function usuarioEnUso(string $nombreUsuario, int $exceptoIdUsuario = 0): bool
    {
        if ($nombreUsuario === '') return false;
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM Usuario WHERE nombre = :n AND idUsuario <> :id"
        );
        $stmt->execute([':n' => $nombreUsuario, ':id' => $exceptoIdUsuario]);
        return (int) ($stmt->fetch()['n'] ?? 0) > 0;
    }

    // =========================================================
    // CREAR  ->  InsertarTutor
    // =========================================================
    public function crear(array $d): array
    {
        // La contraseña solo se hashea si hay usuario y contraseña
        $hash = (!empty($d['usuario']) && !empty($d['password']))
            ? password_hash($d['password'], PASSWORD_DEFAULT)
            : null;

        try {
            $sql = "CALL InsertarTutor(
                        :nombreUsuario, :password,
                        :apPaterno, :apMaterno, :nombre, :ci,
                        :telefono, :correo, :parentesco, :tipo,
                        :direccion, :profesion, :ocupacion,
                        @idUsuario, @idTutor, @mensaje
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nombreUsuario' => $d['usuario']    ?: null,
                ':password'      => $hash,
                ':apPaterno'     => $d['apPaterno'],
                ':apMaterno'     => $d['apMaterno'],
                ':nombre'        => $d['nombre'],
                ':ci'            => $d['ci'],
                ':telefono'      => $d['telefono']   ?: null,
                ':correo'        => $d['correo']     ?: null,
                ':parentesco'    => $d['parentesco'] ?: null,
                ':tipo'          => $d['tipo']       ?: null,
                ':direccion'     => $d['direccion']  ?: null,
                ':profesion'     => $d['profesion']  ?: null,
                ':ocupacion'     => $d['ocupacion']  ?: null,
            ]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @idTutor AS id, @mensaje AS mensaje")->fetch();
            $id      = (int) ($out['id'] ?? 0);
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok      = $id > 0;

            return [
                'ok'      => $ok,
                'mensaje' => $ok ? 'Tutor registrado correctamente.'
                                 : ($mensaje ?: 'No se pudo registrar el tutor.'),
            ];

        } catch (Throwable $e) {
            error_log('[TUTOR] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar. Verifique que el CI/usuario no estén duplicados.'];
        }
    }

    // =========================================================
    // EDITAR  ->  EditarTutor
    // =========================================================
    public function actualizar(int $id, array $d): array
    {
        $actual = $this->obtener($id);
        if ($actual === null) {
            return ['ok' => false, 'mensaje' => 'El tutor no existe.'];
        }

        $hashPass = '';
        if (!empty($d['password'])) {
            $hashPass = password_hash($d['password'], PASSWORD_DEFAULT);
        }

        try {
            $sql = "CALL EditarTutor(
                        :idTutor,
                        :nombreUsuario, :password,
                        :apPaterno, :apMaterno, :nombre, :ci,
                        :telefono, :correo, :parentesco, :tipo,
                        :direccion, :profesion, :ocupacion,
                        @mensaje
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':idTutor'       => $id,
                ':nombreUsuario' => $d['usuario']    ?: null,
                ':password'      => $hashPass,
                ':apPaterno'     => $d['apPaterno'],
                ':apMaterno'     => $d['apMaterno'],
                ':nombre'        => $d['nombre'],
                ':ci'            => $d['ci'],
                ':telefono'      => $d['telefono']   ?: null,
                ':correo'        => $d['correo']     ?: null,
                ':parentesco'    => $d['parentesco'] ?: null,
                ':tipo'          => $d['tipo']       ?: null,
                ':direccion'     => $d['direccion']  ?: null,
                ':profesion'     => $d['profesion']  ?: null,
                ':ocupacion'     => $d['ocupacion']  ?: null,
            ]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @mensaje AS mensaje")->fetch();
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok = str_starts_with($mensaje, 'OK');

            return [
                'ok'      => $ok,
                'mensaje' => $ok ? 'Tutor actualizado correctamente.' : ($mensaje ?: 'No se pudo actualizar.'),
            ];

        } catch (Throwable $e) {
            error_log('[TUTOR] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar. Verifique los datos (CI/usuario duplicado).'];
        }
    }

    // =========================================================
    // ELIMINAR (baja lógica)  ->  EliminarTutor
    // =========================================================
    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL EliminarTutor(:id, @mensaje)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @mensaje AS mensaje")->fetch();
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok = str_starts_with($mensaje, 'OK');

            return [
                'ok'      => $ok,
                'mensaje' => $ok ? 'Tutor eliminado correctamente.' : ($mensaje ?: 'No se pudo eliminar.'),
            ];

        } catch (Throwable $e) {
            error_log('[TUTOR] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar el tutor.'];
        }
    }

    /**
     * Lista estudiantes con sus tutores clasificados en Padre, Madre y Tutor/Apoderado.
     */
    public function listarEstudiantesConTutores(string $buscar = '', string $grado = '', string $paralelo = '', string $nivel = '', string $turno = ''): array
    {
        $sql = "SELECT e.idEstudiante, e.rude, e.apPaterno, e.apMaterno, e.nombre, e.ci,
                       ins.cursoNombre, ins.cursoNivel, ins.cursoParalelo, ins.cursoTurno, ins.cursoAnio,
                       
                       padre.idTutor AS idPadre,
                       CONCAT(padre.apPaterno, ' ', padre.apMaterno, ' ', padre.nombre) AS padreNombre,
                       padre.ci AS padreCi,
                       padre.telefono AS padreTelefono,
                       
                       madre.idTutor AS idMadre,
                       CONCAT(madre.apPaterno, ' ', madre.apMaterno, ' ', madre.nombre) AS madreNombre,
                       madre.ci AS madreCi,
                       madre.telefono AS madreTelefono,
                       
                       tutor.idTutor AS idTutor,
                       CONCAT(tutor.apPaterno, ' ', tutor.apMaterno, ' ', tutor.nombre) AS tutorNombre,
                       tutor.ci AS tutorCi,
                       tutor.telefono AS tutorTelefono,
                       tutor.parentesco AS tutorParentesco,
                       tutor.tipo AS tutorTipo

                FROM Estudiante e
                LEFT JOIN (
                    SELECT x.idEstudiante, c.idCurso, c.nombre AS cursoNombre, n.nombre AS cursoNivel,
                           p.nombre AS cursoParalelo, t.nombre AS cursoTurno, g.anio AS cursoAnio,
                           c.idParalelo, c.idNivel, c.idTurno
                    FROM Inscripcion x
                    INNER JOIN Curso c    ON c.idCurso    = x.idCurso
                    INNER JOIN Nivel n    ON n.idNivel    = c.idNivel
                    INNER JOIN Paralelo p ON p.idParalelo = c.idParalelo
                    INNER JOIN Turno t    ON t.idTurno    = c.idTurno
                    INNER JOIN Gestion g  ON g.idGestion  = c.idGestion
                    WHERE x.estado = 1
                      AND x.idInscripcion = (
                          SELECT x2.idInscripcion FROM Inscripcion x2
                          WHERE x2.idEstudiante = x.idEstudiante AND x2.estado = 1
                          ORDER BY x2.fechaRegistro DESC LIMIT 1)
                ) ins ON ins.idEstudiante = e.idEstudiante

                LEFT JOIN (
                    SELECT et.idEstudiante, MAX(t.idTutor) AS idTutor, MAX(t.apPaterno) AS apPaterno, MAX(t.apMaterno) AS apMaterno, MAX(t.nombre) AS nombre, MAX(t.ci) AS ci, MAX(t.telefono) AS telefono
                    FROM Estudiante_Tutor et
                    INNER JOIN Tutor t ON t.idTutor = et.idTutor
                    WHERE t.estado = 1 AND (LOWER(t.parentesco) LIKE '%padre%' OR LOWER(t.tipo) LIKE '%padre%')
                    GROUP BY et.idEstudiante
                ) padre ON padre.idEstudiante = e.idEstudiante

                LEFT JOIN (
                    SELECT et.idEstudiante, MAX(t.idTutor) AS idTutor, MAX(t.apPaterno) AS apPaterno, MAX(t.apMaterno) AS apMaterno, MAX(t.nombre) AS nombre, MAX(t.ci) AS ci, MAX(t.telefono) AS telefono
                    FROM Estudiante_Tutor et
                    INNER JOIN Tutor t ON t.idTutor = et.idTutor
                    WHERE t.estado = 1 AND (LOWER(t.parentesco) LIKE '%madre%' OR LOWER(t.tipo) LIKE '%madre%')
                    GROUP BY et.idEstudiante
                ) madre ON madre.idEstudiante = e.idEstudiante

                LEFT JOIN (
                    SELECT et.idEstudiante, MAX(t.idTutor) AS idTutor, MAX(t.apPaterno) AS apPaterno, MAX(t.apMaterno) AS apMaterno, MAX(t.nombre) AS nombre, MAX(t.ci) AS ci, MAX(t.telefono) AS telefono, MAX(t.parentesco) AS parentesco, MAX(t.tipo) AS tipo
                    FROM Estudiante_Tutor et
                    INNER JOIN Tutor t ON t.idTutor = et.idTutor
                    WHERE t.estado = 1 
                      AND (t.parentesco IS NULL OR (LOWER(t.parentesco) NOT LIKE '%padre%' AND LOWER(t.parentesco) NOT LIKE '%madre%'))
                      AND (t.tipo IS NULL OR (LOWER(t.tipo) NOT LIKE '%padre%' AND LOWER(t.tipo) NOT LIKE '%madre%'))
                    GROUP BY et.idEstudiante
                ) tutor ON tutor.idEstudiante = e.idEstudiante

                WHERE e.estado = 1";

        $params = [];
        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            $esSingleLetter = (mb_strlen(trim($buscar)) === 1 && !is_numeric(trim($buscar)));

            foreach ($palabras as $p) {
                $sub = [];
                $campos = [
                    "e.nombre", "e.apPaterno", "e.apMaterno", "e.ci",
                    "padre.nombre", "padre.apPaterno", "padre.apMaterno",
                    "madre.nombre", "madre.apPaterno", "madre.apMaterno",
                    "tutor.nombre", "tutor.apPaterno", "tutor.apMaterno"
                ];
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

        if (!empty($idCurso)) {
            $sql .= " AND ins.idCurso = :idCurso";
            $params[':idCurso'] = (int) $idCurso;
        } elseif ($grado !== '') {
            $sql .= " AND ins.cursoNombre = :grado";
            $params[':grado'] = $grado;
        }

        if ($paralelo !== '') {
            if (is_numeric($paralelo)) {
                $sql .= " AND ins.idParalelo = :paralelo";
                $params[':paralelo'] = (int) $paralelo;
            } else {
                $sql .= " AND ins.cursoParalelo = :paralelo";
                $params[':paralelo'] = $paralelo;
            }
        }
        if ($nivel !== '') {
            if (is_numeric($nivel)) {
                $sql .= " AND ins.idNivel = :nivel";
                $params[':nivel'] = (int) $nivel;
            } else {
                $sql .= " AND ins.cursoNivel = :nivel";
                $params[':nivel'] = $nivel;
            }
        }
        if ($turno !== '') {
            if (is_numeric($turno)) {
                $sql .= " AND ins.idTurno = :turno";
                $params[':turno'] = (int) $turno;
            } else {
                $sql .= " AND ins.cursoTurno = :turno";
                $params[':turno'] = $turno;
            }
        }

        $sql .= " ORDER BY FIELD(ins.cursoNombre, 'Primero', 'Segundo', 'Tercero', 'Cuarto', 'Quinto', 'Sexto'), ins.cursoNombre, ins.cursoParalelo, e.apPaterno, e.apMaterno, e.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
