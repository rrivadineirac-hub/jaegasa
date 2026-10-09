<?php
/**
 * ============================================================
 * mcategoria.php  —  Modelo del CRUD Categoría
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Usa los stored procedures de la base de datos:
 *   InsertarCategoria  -> crear
 *   EditarCategoria    -> editar
 *   EliminarCategoria  -> eliminar (baja lógica)
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloCategoria
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Lista las categorías activas (con filtro de búsqueda opcional).
     * Incluye cuántos administrativos y docentes usan cada categoría.
     */
    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT c.idCategoria, c.nombre, c.estado, c.fechaRegistro,
                       (SELECT COUNT(*) FROM Administrativo a WHERE a.idCategoria = c.idCategoria AND a.estado = 1) AS totalAdministrativos,
                       (SELECT COUNT(*) FROM Docente d WHERE d.idCategoria = c.idCategoria AND d.estado = 1) AS totalDocentes
                FROM Categoria c
                WHERE c.estado = 1";

        $params = [];
        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            $esSingleLetter = (mb_strlen(trim($buscar)) === 1 && !is_numeric(trim($buscar)));

            foreach ($palabras as $p) {
                $sub = [];
                $k++;
                $paramKey1 = ":qpk{$k}a";
                $paramKey2 = ":qpk{$k}b";
                $pLower = mb_strtolower($p);

                if ($esSingleLetter && $pLower === 'e') {
                    $sub[] = "(c.nombre LIKE {$paramKey1} OR LOWER(c.nombre) REGEXP '[[:<:]]e[^n]' OR LOWER(c.nombre) REGEXP '[[:<:]]en[a-z]')";
                    $params[$paramKey1] = $p . '%';
                } elseif ($esSingleLetter && in_array($pLower, ['d', 'l'])) {
                    $sub[] = "(c.nombre LIKE {$paramKey1} OR LOWER(c.nombre) REGEXP '[[:<:]]" . $pLower . "[^e]')";
                    $params[$paramKey1] = $p . '%';
                } else {
                    $esEnne = ($pLower === 'ñ');
                    if ($esEnne) {
                        $sub[] = "(BINARY c.nombre LIKE {$paramKey1} OR BINARY c.nombre LIKE {$paramKey2})";
                    } else {
                        $sub[] = "(c.nombre LIKE {$paramKey1} OR c.nombre LIKE {$paramKey2})";
                    }
                    $params[$paramKey1] = $p . '%';
                    $params[$paramKey2] = '% ' . $p . '%';
                }

                $condicionesPalabras[] = "(" . implode(" OR ", $sub) . ")";
            }
            if (!empty($condicionesPalabras)) {
                $sql .= " AND " . implode(" AND ", $condicionesPalabras);
            }
        }

        $sql .= " ORDER BY CASE LOWER(TRIM(c.nombre)) WHEN 'quinta' THEN 1 WHEN 'cuarta' THEN 2 WHEN 'tercera' THEN 3 WHEN 'segunda' THEN 4 WHEN 'primera' THEN 5 WHEN 'cero' THEN 6 WHEN 'mérito' THEN 7 WHEN 'merito' THEN 7 ELSE 8 END, c.idCategoria ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una categoría por su id.
     */
    public function obtener(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT idCategoria, nombre, estado, fechaRegistro
             FROM Categoria WHERE idCategoria = :id AND estado = 1 LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Verifica si un nombre de categoría ya existe (excluye un id opcional).
     */
    public function nombreEnUso(string $nombre, int $exceptoId = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM Categoria
             WHERE nombre = :n AND estado = 1 AND idCategoria <> :id"
        );
        $stmt->execute([':n' => $nombre, ':id' => $exceptoId]);
        return (int) ($stmt->fetch()['n'] ?? 0) > 0;
    }

    // =========================================================
    // CREAR  ->  InsertarCategoria
    // =========================================================
    public function crear(array $d): array
    {
        try {
            $stmt = $this->db->prepare(
                "CALL InsertarCategoria(:nombre, @idCategoria, @mensaje)"
            );
            $stmt->execute([':nombre' => $d['nombre']]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @idCategoria AS id, @mensaje AS mensaje")->fetch();
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok = str_starts_with($mensaje, 'OK');

            return [
                'ok'      => $ok,
                'mensaje' => $ok ? 'Categoría registrada correctamente.'
                                 : ($mensaje ?: 'No se pudo registrar la categoría.'),
            ];

        } catch (Throwable $e) {
            error_log('[CATEGORIA] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar la categoría.'];
        }
    }

    // =========================================================
    // EDITAR  ->  EditarCategoria
    // =========================================================
    public function actualizar(int $id, array $d): array
    {
        try {
            $stmt = $this->db->prepare(
                "CALL EditarCategoria(:id, :nombre, :estado, @mensaje)"
            );
            $stmt->execute([
                ':id'     => $id,
                ':nombre' => $d['nombre'],
                ':estado' => 1,
            ]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @mensaje AS mensaje")->fetch();
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok = str_starts_with($mensaje, 'OK');

            return [
                'ok'      => $ok,
                'mensaje' => $ok ? 'Categoría actualizada correctamente.' : ($mensaje ?: 'No se pudo actualizar.'),
            ];

        } catch (Throwable $e) {
            error_log('[CATEGORIA] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar la categoría.'];
        }
    }

    // =========================================================
    // ELIMINAR (baja lógica)  ->  EliminarCategoria
    // =========================================================
    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL EliminarCategoria(:id, @mensaje)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @mensaje AS mensaje")->fetch();
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok = str_starts_with($mensaje, 'OK');

            return [
                'ok'      => $ok,
                'mensaje' => $ok ? 'Categoría eliminada correctamente.' : ($mensaje ?: 'No se pudo eliminar.'),
            ];

        } catch (Throwable $e) {
            error_log('[CATEGORIA] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar la categoría.'];
        }
    }
}
