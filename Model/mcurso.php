<?php
/**
 * ============================================================
 * mcurso.php — Modelo del CRUD Curso
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Un curso agrupa: Gestión + Nivel + Turno + Paralelo (+ Horario).
 * Se usa SQL directo (transaccional donde aplica), sin SP.
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloCurso
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Lista los cursos activos con sus datos relacionados.
     */
    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT c.idCurso, c.nombre, c.idGestion, c.idNivel, c.idTurno, c.idParalelo, c.idHorario,
                       COALESCE(c.cupo, 36) AS cupo,
                       g.anio,
                       n.nombre AS nivel,
                       t.nombre AS turno,
                       p.nombre AS paralelo,
                       CASE WHEN h.idHorario IS NULL THEN NULL
                            ELSE CONCAT(h.diaSemana,' ',TIME_FORMAT(h.horaInicio,'%H:%i'),'-',TIME_FORMAT(h.horaFin,'%H:%i'))
                       END AS horario,
                       (SELECT COUNT(*) FROM Inscripcion i WHERE i.idCurso = c.idCurso AND i.estado = 1) AS inscritos
                FROM Curso c
                INNER JOIN Gestion g  ON g.idGestion  = c.idGestion
                INNER JOIN Nivel n    ON n.idNivel    = c.idNivel
                INNER JOIN Turno t    ON t.idTurno    = c.idTurno
                INNER JOIN Paralelo p ON p.idParalelo = c.idParalelo
                LEFT  JOIN Horario h  ON h.idHorario  = c.idHorario
                WHERE c.estado = 1";

        $params = [];
        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            foreach ($palabras as $p) {
                $sub = [];
                $campos = ["n.nombre", "p.nombre", "t.nombre", "g.anio", "c.nombre"];
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

        $sql .= " ORDER BY g.anio DESC, n.nombre, FIELD(c.nombre, 'Primero', 'Segundo', 'Tercero', 'Cuarto', 'Quinto', 'Sexto'), c.nombre, p.nombre, t.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ---------- Catálogos para los <select> ----------
    public function listarGestiones(): array
    {
        return $this->db->query("SELECT idGestion, anio FROM Gestion WHERE estado = 1 ORDER BY anio DESC")->fetchAll();
    }

    public function listarNiveles(): array
    {
        return $this->db->query("SELECT idNivel, nombre FROM Nivel WHERE estado = 1 ORDER BY nombre")->fetchAll();
    }

    public function listarTurnos(): array
    {
        return $this->db->query("SELECT idTurno, nombre FROM Turno WHERE estado = 1 ORDER BY idTurno")->fetchAll();
    }

    public function listarParalelos(): array
    {
        return $this->db->query("SELECT idParalelo, nombre FROM Paralelo WHERE estado = 1 ORDER BY nombre")->fetchAll();
    }

    public function listarHorarios(): array
    {
        $sql = "SELECT idHorario,
                       CONCAT(diaSemana,' ',TIME_FORMAT(horaInicio,'%H:%i'),'-',TIME_FORMAT(horaFin,'%H:%i')) AS descripcion
                FROM Horario WHERE estado = 1
                ORDER BY diaSemana, horaInicio";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Verifica si ya existe un curso con la misma combinación
     * (gestión + nivel + turno + paralelo), excluyendo un id opcional.
     */
    public function duplicado(array $d, int $exceptoId = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM Curso
             WHERE idGestion = :g AND idNivel = :n AND idTurno = :t AND idParalelo = :p
               AND estado = 1 AND idCurso <> :id"
        );
        $stmt->execute([
            ':g' => $d['idGestion'], ':n' => $d['idNivel'],
            ':t' => $d['idTurno'],   ':p' => $d['idParalelo'], ':id' => $exceptoId,
        ]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function crear(array $d): array
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO Curso (idGestion, idNivel, idTurno, idParalelo, idHorario, nombre, cupo)
                 VALUES (:g, :n, :t, :p, :h, :nom, :cupo)"
            );
            $stmt->execute([
                ':g'    => $d['idGestion'],
                ':n'    => $d['idNivel'],
                ':t'    => $d['idTurno'],
                ':p'    => $d['idParalelo'],
                ':h'    => $d['idHorario'] ?: null,
                ':nom'  => $d['nombre'] ?: null,
                ':cupo' => !empty($d['cupo']) ? (int)$d['cupo'] : 36,
            ]);
            return ['ok' => true, 'mensaje' => 'Curso registrado correctamente.'];
        } catch (Throwable $e) {
            error_log('[CURSO] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar el curso.'];
        }
    }

    public function actualizar(int $id, array $d): array
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE Curso
                 SET idGestion = :g, idNivel = :n, idTurno = :t, idParalelo = :p,
                     idHorario = :h, nombre = :nom, cupo = :cupo
                 WHERE idCurso = :id"
            );
            $stmt->execute([
                ':g'    => $d['idGestion'],
                ':n'    => $d['idNivel'],
                ':t'    => $d['idTurno'],
                ':p'    => $d['idParalelo'],
                ':h'    => $d['idHorario'] ?: null,
                ':nom'  => $d['nombre'] ?: null,
                ':cupo' => !empty($d['cupo']) ? (int)$d['cupo'] : 36,
                ':id'   => $id,
            ]);
            return ['ok' => true, 'mensaje' => 'Curso actualizado correctamente.'];
        } catch (Throwable $e) {
            error_log('[CURSO] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar el curso.'];
        }
    }

    /**
     * Baja lógica. No permite eliminar si tiene inscripciones activas.
     */
    public function eliminar(int $id): array
    {
        try {
            $q = $this->db->prepare("SELECT COUNT(*) FROM Inscripcion WHERE idCurso = :id AND estado = 1");
            $q->execute([':id' => $id]);
            if (((int) $q->fetchColumn()) > 0) {
                return ['ok' => false, 'mensaje' => 'No se puede eliminar: el curso tiene estudiantes inscritos.'];
            }

            $this->db->prepare("UPDATE Curso SET estado = 0 WHERE idCurso = :id")->execute([':id' => $id]);
            return ['ok' => true, 'mensaje' => 'Curso eliminado correctamente.'];
        } catch (Throwable $e) {
            error_log('[CURSO] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar el curso.'];
        }
    }
}
