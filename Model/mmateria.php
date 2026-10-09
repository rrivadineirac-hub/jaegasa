<?php
/**
 * ============================================================
 * mmateria.php  —  Modelo del CRUD Materia
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Utiliza SQL directo mediante PDO.
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloMateria
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Lista las materias activas (con filtro de búsqueda opcional).
     */
    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT idMateria, nombre, sigla, fechaRegistro 
                FROM materia 
                WHERE estado = 1";

        $params = [];
        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            foreach ($palabras as $p) {
                $sub = [];
                $campos = ["nombre", "sigla"];
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

        $sql .= " ORDER BY LENGTH(nombre) ASC, nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los detalles de una materia por su ID.
     */
    public function obtener(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT idMateria, nombre, sigla, estado, fechaRegistro 
             FROM materia 
             WHERE idMateria = :id AND estado = 1 LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Verifica si un nombre de materia ya existe (excluyendo un ID opcional).
     */
    public function nombreEnUso(string $nombre, int $exceptoId = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM materia 
             WHERE nombre = :nombre AND estado = 1 AND idMateria <> :id"
        );
        $stmt->execute([':nombre' => $nombre, ':id' => $exceptoId]);
        return (int) ($stmt->fetch()['n'] ?? 0) > 0;
    }

    /**
     * Crea un nuevo registro de materia.
     */
    public function crear(array $d): array
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO materia (nombre, sigla, estado) 
                 VALUES (:nombre, :sigla, 1)"
            );
            $stmt->execute([
                ':nombre' => $d['nombre'],
                ':sigla'  => !empty($d['sigla']) ? trim($d['sigla']) : null
            ]);

            return [
                'ok'      => true,
                'mensaje' => 'Materia registrada correctamente.'
            ];
        } catch (Throwable $e) {
            error_log('[MATERIA] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar la materia.'];
        }
    }

    /**
     * Actualiza los datos de una materia existente.
     */
    public function actualizar(int $id, array $d): array
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE materia 
                 SET nombre = :nombre, sigla = :sigla 
                 WHERE idMateria = :id AND estado = 1"
            );
            $stmt->execute([
                ':id'     => $id,
                ':nombre' => $d['nombre'],
                ':sigla'  => !empty($d['sigla']) ? trim($d['sigla']) : null
            ]);

            return [
                'ok'      => true,
                'mensaje' => 'Materia actualizada correctamente.'
            ];
        } catch (Throwable $e) {
            error_log('[MATERIA] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar la materia.'];
        }
    }

    /**
     * Realiza la baja lógica de una materia.
     */
    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE materia SET estado = 0 WHERE idMateria = :id"
            );
            $stmt->execute([':id' => $id]);

            return [
                'ok'      => true,
                'mensaje' => 'Materia eliminada correctamente.'
            ];
        } catch (Throwable $e) {
            error_log('[MATERIA] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar la materia.'];
        }
    }
}
