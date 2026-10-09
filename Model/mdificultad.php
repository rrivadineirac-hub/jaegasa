<?php
/**
 * mdificultad.php — Modelo del CRUD Dificultad de Aprendizaje
 * Usa SP (sin sp_): InsertarDificultad, EditarDificultad, EliminarDificultad
 * Nota: la columna del grado en la tabla se llama "gado".
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloDificultad
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT idDificultad, tipoDificultad, gado AS grado, fechaRegistro
                FROM Dificultad_Aprendizaje WHERE estado = 1";
        $params = [];
        if ($buscar !== '') { $sql .= " AND tipoDificultad LIKE :q"; $params[':q'] = $buscar . '%'; }
        $sql .= " ORDER BY tipoDificultad";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function crear(array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL InsertarDificultad(:tipo, :grado, @id, @m)");
            $stmt->execute([':tipo' => $d['tipoDificultad'], ':grado' => $d['grado'] ?: null]);
            $stmt->closeCursor();
            $out = $this->db->query("SELECT @id AS id, @m AS m")->fetch();
            $ok = ((int) ($out['id'] ?? 0)) > 0;
            return ['ok' => $ok, 'mensaje' => $ok ? 'Dificultad registrada correctamente.' : ($out['m'] ?: 'No se pudo registrar.')];
        } catch (Throwable $e) {
            error_log('[DIFICULTAD] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar la dificultad.'];
        }
    }

    public function actualizar(int $id, array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL EditarDificultad(:id, :tipo, :grado, @m)");
            $stmt->execute([':id' => $id, ':tipo' => $d['tipoDificultad'], ':grado' => $d['grado'] ?: null]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Dificultad actualizada correctamente.' : ($m ?: 'No se pudo actualizar.')];
        } catch (Throwable $e) {
            error_log('[DIFICULTAD] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar la dificultad.'];
        }
    }

    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL EliminarDificultad(:id, @m)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Dificultad eliminada correctamente.' : ($m ?: 'No se pudo eliminar.')];
        } catch (Throwable $e) {
            error_log('[DIFICULTAD] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar la dificultad.'];
        }
    }
}
