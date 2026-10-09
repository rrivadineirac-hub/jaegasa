<?php
/**
 * mdiscapacidad.php — Modelo del CRUD Discapacidad
 * Usa SP (sin sp_): InsertarDiscapacidad, EditarDiscapacidad, EliminarDiscapacidad
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloDiscapacidad
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT idDiscapacidad, tipoDiscapacidad, fechaRegistro FROM Discapacidad WHERE estado = 1";
        $params = [];
        if ($buscar !== '') { $sql .= " AND tipoDiscapacidad LIKE :q"; $params[':q'] = $buscar . '%'; }
        $sql .= " ORDER BY tipoDiscapacidad";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function crear(array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL InsertarDiscapacidad(:tipo, @id, @m)");
            $stmt->execute([':tipo' => $d['tipoDiscapacidad']]);
            $stmt->closeCursor();
            $out = $this->db->query("SELECT @id AS id, @m AS m")->fetch();
            $ok = ((int) ($out['id'] ?? 0)) > 0;
            return ['ok' => $ok, 'mensaje' => $ok ? 'Discapacidad registrada correctamente.' : ($out['m'] ?: 'No se pudo registrar.')];
        } catch (Throwable $e) {
            error_log('[DISCAPACIDAD] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar la discapacidad.'];
        }
    }

    public function actualizar(int $id, array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL EditarDiscapacidad(:id, :tipo, @m)");
            $stmt->execute([':id' => $id, ':tipo' => $d['tipoDiscapacidad']]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Discapacidad actualizada correctamente.' : ($m ?: 'No se pudo actualizar.')];
        } catch (Throwable $e) {
            error_log('[DISCAPACIDAD] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar la discapacidad.'];
        }
    }

    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL EliminarDiscapacidad(:id, @m)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Discapacidad eliminada correctamente.' : ($m ?: 'No se pudo eliminar.')];
        } catch (Throwable $e) {
            error_log('[DISCAPACIDAD] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar la discapacidad.'];
        }
    }
}
