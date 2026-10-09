<?php
/**
 * mturno.php — Modelo del CRUD Turno
 * Usa SP (sin sp_): InsertarTurno, EditarTurno, EliminarTurno
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloTurno
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT idTurno, nombre, fechaRegistro FROM Turno WHERE estado = 1";
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
                    $sub[] = "(nombre LIKE {$paramKey1} OR LOWER(nombre) REGEXP '[[:<:]]e[^n]' OR LOWER(nombre) REGEXP '[[:<:]]en[a-z]')";
                    $params[$paramKey1] = $p . '%';
                } elseif ($esSingleLetter && in_array($pLower, ['d', 'l'])) {
                    $sub[] = "(nombre LIKE {$paramKey1} OR LOWER(nombre) REGEXP '[[:<:]]" . $pLower . "[^e]')";
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
            $stmt = $this->db->prepare("CALL InsertarTurno(:nombre, @id, @m)");
            $stmt->execute([':nombre' => $d['nombre']]);
            $stmt->closeCursor();
            $out = $this->db->query("SELECT @id AS id, @m AS m")->fetch();
            $ok = ((int) ($out['id'] ?? 0)) > 0;
            return ['ok' => $ok, 'mensaje' => $ok ? 'Turno registrado correctamente.' : ($out['m'] ?: 'No se pudo registrar.')];
        } catch (Throwable $e) {
            error_log('[TURNO] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar el turno.'];
        }
    }

    public function actualizar(int $id, array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL EditarTurno(:id, :nombre, @m)");
            $stmt->execute([':id' => $id, ':nombre' => $d['nombre']]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Turno actualizado correctamente.' : ($m ?: 'No se pudo actualizar.')];
        } catch (Throwable $e) {
            error_log('[TURNO] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar el turno.'];
        }
    }

    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL EliminarTurno(:id, @m)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Turno eliminado correctamente.' : ($m ?: 'No se pudo eliminar.')];
        } catch (Throwable $e) {
            error_log('[TURNO] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar el turno.'];
        }
    }
}
