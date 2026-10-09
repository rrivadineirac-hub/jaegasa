<?php
/**
 * ============================================================
 * maula.php  —  Modelo del CRUD Aula
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Utiliza SQL directo mediante PDO.
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloAula
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Lista las aulas activas (con filtro de búsqueda opcional).
     * Incluye un recuento de cuántas clases están asignadas a cada aula.
     */
    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT a.idAula, a.nombre, a.observacion, a.fechaRegistro, a.idDocente, a.idTurno,
                       CONCAT(d.apPaterno, ' ', d.apMaterno, ' ', d.nombre) AS docenteNombre,
                       t.nombre AS turnoNombre,
                       (SELECT COUNT(*) FROM clase c WHERE c.idAula = a.idAula AND c.estado = 1) AS totalClases
                FROM aula a
                LEFT JOIN docente d ON a.idDocente = d.idDocente
                LEFT JOIN turno t ON a.idTurno = t.idTurno
                WHERE a.estado = 1";

        $params = [];
        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            foreach ($palabras as $p) {
                $sub = [];
                $campos = [
                    "a.nombre", "a.observacion", "d.nombre", "d.apPaterno", "d.apMaterno", "t.nombre",
                    "CONCAT_WS(' ', d.apPaterno, d.apMaterno, d.nombre)",
                    "CONCAT_WS(' ', d.nombre, d.apPaterno, d.apMaterno)"
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

        $sql .= " ORDER BY LENGTH(a.nombre) ASC, a.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los detalles de un aula por su ID.
     */
    public function obtener(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT idAula, nombre, observacion, idDocente, idTurno, estado, fechaRegistro
             FROM aula WHERE idAula = :id AND estado = 1 LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Verifica si un nombre de aula ya existe (excluyendo un ID opcional).
     */
    public function nombreEnUso(string $nombre, int $exceptoId = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM aula
             WHERE nombre = :n AND estado = 1 AND idAula <> :id"
        );
        $stmt->execute([':n' => $nombre, ':id' => $exceptoId]);
        return (int) ($stmt->fetch()['n'] ?? 0) > 0;
    }

    /**
     * Crea un nuevo registro de aula.
     */
    public function crear(array $d): array
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO aula (nombre, idDocente, idTurno, observacion, estado)
                 VALUES (:nombre, :idDocente, :idTurno,  :observacion, 1)"
            );
            $stmt->execute([
                ':nombre'      => $d['nombre'],
                ':idDocente'   => $d['idDocente'],
                ':idTurno'     => $d['idTurno'],
                ':observacion' => $d['observacion'] ?: null
            ]);

            return [
                'ok'      => true,
                'mensaje' => 'Aula registrada correctamente.'
            ];
        } catch (Throwable $e) {
            error_log('[AULA] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar el aula.'];
        }
    }

    /**
     * Actualiza los datos de un aula existente.
     */
    public function actualizar(int $id, array $d): array
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE aula
                 SET nombre = :nombre, observacion = :observacion, idDocente = :idDocente, idTurno = :idTurno
                 WHERE idAula = :id AND estado = 1"
            );
            $stmt->execute([
                ':id'          => $id,
                ':nombre'      => $d['nombre'],
                ':idDocente'   => $d['idDocente'],
                ':idTurno'     => $d['idTurno'],
                ':observacion' => $d['observacion'] ?: null
            ]);

            return [
                'ok'      => true,
                'mensaje' => 'Aula actualizada correctamente.'
            ];
        } catch (Throwable $e) {
            error_log('[AULA] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar the aula.'];
        }
    }

    /**
     * Realiza la baja lógica de un aula.
     */
    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE aula SET estado = 0 WHERE idAula = :id"
            );
            $stmt->execute([':id' => $id]);

            return [
                'ok'      => true,
                'mensaje' => 'Aula de-allocated/eliminada correctamente.'
            ];
        } catch (Throwable $e) {
            error_log('[AULA] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar el aula.'];
        }
    }

    /**
     * Lista todos los docentes activos con sus nombres completos.
     */
    public function listarDocentesActivos(): array
    {
        $sql = "SELECT idDocente, CONCAT(apPaterno, ' ', apMaterno, ' ', nombre) AS nombreCompleto 
                FROM docente 
                WHERE estado = 1 
                ORDER BY apPaterno, apMaterno, nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Lista todos los turnos activos.
     */
    public function listarTurnosActivos(): array
    {
        $sql = "SELECT idTurno, nombre FROM turno WHERE estado = 1 ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }
}
