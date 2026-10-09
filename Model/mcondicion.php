<?php
/**
 * mcondicion.php — Modelo del CRUD Condición Médica
 * Usa SP (sin sp_): InsertarCondicion, EditarCondicion, EliminarCondicion
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloCondicion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT idCondicion, condicion, fechaRegistro FROM Condicion_Medica WHERE estado = 1";
        $params = [];
        if ($buscar !== '') { $sql .= " AND condicion LIKE :q"; $params[':q'] = $buscar . '%'; }
        $sql .= " ORDER BY condicion";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function crear(array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL InsertarCondicion(:cond, @id, @m)");
            $stmt->execute([':cond' => $d['condicion']]);
            $stmt->closeCursor();
            $out = $this->db->query("SELECT @id AS id, @m AS m")->fetch();
            $ok = ((int) ($out['id'] ?? 0)) > 0;
            return ['ok' => $ok, 'mensaje' => $ok ? 'Condición médica registrada correctamente.' : ($out['m'] ?: 'No se pudo registrar.')];
        } catch (Throwable $e) {
            error_log('[CONDICION] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar la condición médica.'];
        }
    }

    public function actualizar(int $id, array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL EditarCondicion(:id, :cond, @m)");
            $stmt->execute([':id' => $id, ':cond' => $d['condicion']]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Condición médica actualizada correctamente.' : ($m ?: 'No se pudo actualizar.')];
        } catch (Throwable $e) {
            error_log('[CONDICION] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar la condición médica.'];
        }
    }

    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL EliminarCondicion(:id, @m)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Condición médica eliminada correctamente.' : ($m ?: 'No se pudo eliminar.')];
        } catch (Throwable $e) {
            error_log('[CONDICION] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar la condición médica.'];
        }
    }
}
