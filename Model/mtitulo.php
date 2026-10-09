<?php
/**
 * mtitulo.php — Modelo del CRUD Título
 * Usa SP (sin sp_): InsertarTitulo, EditarTitulo, EliminarTitulo
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloTitulo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT idTitulo, nombre, fechaRegistro FROM Titulo WHERE estado = 1";
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
                    $sub[] = "nombre LIKE {$paramKey1}";
                    $sub[] = "LOWER(nombre) REGEXP '[[:<:]]e[^n]'";
                    $sub[] = "LOWER(nombre) REGEXP '[[:<:]]en[a-z]'";
                    $params[$paramKey1] = $p . '%';
                } elseif ($esSingleLetter && in_array($pLower, ['d', 'l'])) {
                    $sub[] = "nombre LIKE {$paramKey1}";
                    $sub[] = "LOWER(nombre) REGEXP '[[:<:]]" . $pLower . "[^e]'";
                    $params[$paramKey1] = $p . '%';
                } else {
                    $esEnne = ($pLower === 'ñ');
                    if ($esEnne) {
                        $sub[] = "(BINARY nombre LIKE {$paramKey1} OR BINARY nombre LIKE {$paramKey2})";
                    } else {
                        $sub[] = "(nombre LIKE {$paramKey1} OR nombre LIKE {$paramKey2})";
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
        $sql .= " ORDER BY nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function crear(array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL InsertarTitulo(:nombre, @id, @m)");
            $stmt->execute([':nombre' => $d['nombre']]);
            $stmt->closeCursor();
            $out = $this->db->query("SELECT @id AS id, @m AS m")->fetch();
            $ok = ((int) ($out['id'] ?? 0)) > 0;
            return ['ok' => $ok, 'mensaje' => $ok ? 'Título registrado correctamente.' : ($out['m'] ?: 'No se pudo registrar.')];
        } catch (Throwable $e) {
            error_log('[TITULO] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar el título.'];
        }
    }

    public function actualizar(int $id, array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL EditarTitulo(:id, :nombre, @m)");
            $stmt->execute([':id' => $id, ':nombre' => $d['nombre']]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Título actualizado correctamente.' : ($m ?: 'No se pudo actualizar.')];
        } catch (Throwable $e) {
            error_log('[TITULO] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar el título.'];
        }
    }

    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL EliminarTitulo(:id, @m)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Título eliminado correctamente.' : ($m ?: 'No se pudo eliminar.')];
        } catch (Throwable $e) {
            error_log('[TITULO] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar el título.'];
        }
    }
}
