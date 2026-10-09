<?php
/**
 * mparalelo.php — Modelo del CRUD Paralelo
 * Usa SP (sin sp_): InsertarParalelo, EditarParalelo, EliminarParalelo
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloParalelo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT idParalelo, nombre, fechaRegistro FROM Paralelo WHERE estado = 1";
        $params = [];
        if ($buscar !== '') { $sql .= " AND nombre LIKE :q"; $params[':q'] = $buscar . '%'; }
        $sql .= " ORDER BY nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function crear(array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL InsertarParalelo(:nombre, @id, @m)");
            $stmt->execute([':nombre' => $d['nombre']]);
            $stmt->closeCursor();
            $out = $this->db->query("SELECT @id AS id, @m AS m")->fetch();
            $ok = ((int) ($out['id'] ?? 0)) > 0;
            return ['ok' => $ok, 'mensaje' => $ok ? 'Paralelo registrado correctamente.' : ($out['m'] ?: 'No se pudo registrar.')];
        } catch (Throwable $e) {
            error_log('[PARALELO] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar el paralelo.'];
        }
    }

    public function actualizar(int $id, array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL EditarParalelo(:id, :nombre, @m)");
            $stmt->execute([':id' => $id, ':nombre' => $d['nombre']]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Paralelo actualizado correctamente.' : ($m ?: 'No se pudo actualizar.')];
        } catch (Throwable $e) {
            error_log('[PARALELO] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar el paralelo.'];
        }
    }

    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL EliminarParalelo(:id, @m)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Paralelo eliminado correctamente.' : ($m ?: 'No se pudo eliminar.')];
        } catch (Throwable $e) {
            error_log('[PARALELO] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar el paralelo.'];
        }
    }
}
