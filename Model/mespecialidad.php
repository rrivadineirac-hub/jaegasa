<?php
/**
 * mespecialidad.php — Modelo del CRUD Especialidad
 * Usa SP (sin sp_): InsertarEspecialidad, EditarEspecialidad, EliminarEspecialidad
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloEspecialidad
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT e.idEspecialidad, e.nombre, e.idCategoria, e.fechaRegistro,
                       c.nombre AS categoria
                FROM Especialidad e
                LEFT JOIN Categoria c ON c.idCategoria = e.idCategoria
                WHERE e.estado = 1";
        $params = [];
        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            $esSingleLetter = (mb_strlen(trim($buscar)) === 1 && !is_numeric(trim($buscar)));

            foreach ($palabras as $p) {
                $sub = [];
                $campos = ["e.nombre", "c.nombre"];
                $pLower = mb_strtolower($p);

                foreach ($campos as $campo) {
                    $k++;
                    $paramKey1 = ":qpk{$k}a";
                    $paramKey2 = ":qpk{$k}b";

                    if ($esSingleLetter && $pLower === 'e') {
                        $sub[] = "({$campo} LIKE {$paramKey1} OR LOWER({$campo}) REGEXP '[[:<:]]e[^n]' OR LOWER({$campo}) REGEXP '[[:<:]]en[a-z]')";
                        $params[$paramKey1] = $p . '%';
                    } elseif ($esSingleLetter && in_array($pLower, ['d', 'l'])) {
                        $sub[] = "({$campo} LIKE {$paramKey1} OR LOWER({$campo}) REGEXP '[[:<:]]" . $pLower . "[^e]')";
                        $params[$paramKey1] = $p . '%';
                    } else {
                        $esEnne = ($pLower === 'ñ');
                        if ($esEnne) {
                            $sub[] = "(BINARY {$campo} LIKE {$paramKey1} OR BINARY {$campo} LIKE {$paramKey2})";
                        } else {
                            $sub[] = "({$campo} LIKE {$paramKey1} OR {$campo} LIKE {$paramKey2})";
                        }
                        $params[$paramKey1] = $p . '%';
                        $params[$paramKey2] = '% ' . $p . '%';
                    }
                }
                $condicionesPalabras[] = "(" . implode(" OR ", $sub) . ")";
            }
            if (!empty($condicionesPalabras)) {
                $sql .= " AND " . implode(" AND ", $condicionesPalabras);
            }
        }
        $sql .= " ORDER BY e.nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listarCategorias(): array
    {
        return $this->db->query("SELECT idCategoria, nombre FROM Categoria WHERE estado = 1 ORDER BY CASE LOWER(TRIM(nombre)) WHEN 'quinta' THEN 1 WHEN 'cuarta' THEN 2 WHEN 'tercera' THEN 3 WHEN 'segunda' THEN 4 WHEN 'primera' THEN 5 WHEN 'cero' THEN 6 WHEN 'mérito' THEN 7 WHEN 'merito' THEN 7 ELSE 8 END, idCategoria ASC")->fetchAll();
    }

    public function crear(array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL InsertarEspecialidad(:nombre, :categoria, @id, @m)");
            $stmt->execute([':nombre' => $d['nombre'], ':categoria' => $d['categoria'] ?: null]);
            $stmt->closeCursor();
            $out = $this->db->query("SELECT @id AS id, @m AS m")->fetch();
            $ok = ((int) ($out['id'] ?? 0)) > 0;
            return ['ok' => $ok, 'mensaje' => $ok ? 'Especialidad registrada correctamente.' : ($out['m'] ?: 'No se pudo registrar.')];
        } catch (Throwable $e) {
            error_log('[ESPECIALIDAD] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar la especialidad.'];
        }
    }

    public function actualizar(int $id, array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL EditarEspecialidad(:id, :nombre, :categoria, @m)");
            $stmt->execute([':id' => $id, ':nombre' => $d['nombre'], ':categoria' => $d['categoria'] ?: null]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Especialidad actualizada correctamente.' : ($m ?: 'No se pudo actualizar.')];
        } catch (Throwable $e) {
            error_log('[ESPECIALIDAD] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar la especialidad.'];
        }
    }

    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL EliminarEspecialidad(:id, @m)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Especialidad eliminada correctamente.' : ($m ?: 'No se pudo eliminar.')];
        } catch (Throwable $e) {
            error_log('[ESPECIALIDAD] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar la especialidad.'];
        }
    }
}
