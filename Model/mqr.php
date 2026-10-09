<?php
/**
 * mqr.php — Modelo del CRUD QR
 * Sistema Académico JAEGASA
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloQr
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    private const SELECT_BASE = "SELECT e.idEstudiante, e.rude, e.apPaterno, e.apMaterno, e.nombre,
                       e.ci, e.foto, e.genero, e.qr,
                       ins.cursoNombre, ins.cursoNivel, ins.cursoParalelo, ins.cursoTurno, ins.cursoAnio
                FROM Estudiante e
                LEFT JOIN (
                    SELECT x.idEstudiante, c.nombre AS cursoNombre, n.nombre AS cursoNivel,
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
                          ORDER BY x2.fechaRegistro DESC LIMIT 1
                      )
                ) ins ON ins.idEstudiante = e.idEstudiante";

    /**
     * Lista los estudiantes activos con sus detalles de curso.
     */
    public function listarEstudiantes(string $buscar = '', string $grado = '', string $paralelo = '', string $nivel = '', string $turno = ''): array
    {
        $sql = self::SELECT_BASE . " WHERE e.estado = 1";

        $params = [];
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

        if ($grado !== '') {
            $sql .= " AND ins.cursoNombre = :grado";
            $params[':grado'] = $grado;
        }
        if ($paralelo !== '') {
            $sql .= " AND ins.idParalelo = :paralelo";
            $params[':paralelo'] = (int) $paralelo;
        }
        if ($nivel !== '') {
            $sql .= " AND ins.idNivel = :nivel";
            $params[':nivel'] = (int) $nivel;
        }
        if ($turno !== '') {
            $sql .= " AND ins.idTurno = :turno";
            $params[':turno'] = (int) $turno;
        }

        $sql .= " ORDER BY e.apPaterno, e.apMaterno, e.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los datos de un estudiante (para PDF, crear/eliminar/modificar QR).
     */
    public function obtenerEstudiante(int $idEstudiante): ?array
    {
        $sql = self::SELECT_BASE . " WHERE e.idEstudiante = :id AND e.estado = 1 LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idEstudiante]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtiene los datos de múltiples estudiantes por listado de IDs.
     */
    public function obtenerEstudiantesPorIds(array $ids): array
    {
        if (empty($ids)) return [];
        $idsClean = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));
        if (empty($idsClean)) return [];

        $placeholders = implode(',', array_fill(0, count($idsClean), '?'));
        $sql = self::SELECT_BASE . " WHERE e.estado = 1 AND e.idEstudiante IN ($placeholders) ORDER BY e.apPaterno, e.apMaterno, e.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($idsClean);
        return $stmt->fetchAll();
    }

    /**
     * Guarda el nombre del archivo QR generado en el campo qr del estudiante.
     */
    public function guardarQr(int $idEstudiante, string $nombreArchivo): bool
    {
        $stmt = $this->db->prepare("UPDATE Estudiante SET qr = :qr WHERE idEstudiante = :id");
        return $stmt->execute([':qr' => $nombreArchivo, ':id' => $idEstudiante]);
    }

    /**
     * Limpia el campo qr del estudiante (deja de referenciar el archivo).
     */
    public function limpiarQr(int $idEstudiante): bool
    {
        $stmt = $this->db->prepare("UPDATE Estudiante SET qr = NULL WHERE idEstudiante = :id");
        return $stmt->execute([':id' => $idEstudiante]);
    }

    /**
     * Obtiene los cursos activos para los combobox.
     */
    public function listarCursosActivos(): array
    {
        $sql = "SELECT c.idCurso, c.nombre AS cursoNombre, c.idParalelo, p.nombre AS paraleloNombre,
                       c.idNivel, n.nombre AS nivelNombre, c.idTurno, t.nombre AS turnoNombre,
                       g.anio AS gestionAnio
                FROM curso c
                INNER JOIN paralelo p ON c.idParalelo = p.idParalelo
                INNER JOIN nivel n    ON c.idNivel = n.idNivel
                INNER JOIN turno t    ON c.idTurno = t.idTurno
                INNER JOIN gestion g  ON c.idGestion = g.idGestion
                WHERE c.estado = 1
                ORDER BY g.anio DESC, n.nombre, FIELD(c.nombre, 'Primero', 'Segundo', 'Tercero', 'Cuarto', 'Quinto', 'Sexto'), c.nombre, p.nombre, t.nombre";
        return $this->db->query($sql)->fetchAll();
    }
}