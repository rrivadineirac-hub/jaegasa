<?php
/**
 * mnivel.php — Modelo del CRUD Nivel
 * Usa SP (sin sp_): InsertarNivel, EditarNivel, EliminarNivel
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloNivel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT idNivel, nombre, horaInicio, horaFin, fechaRegistro
                FROM Nivel WHERE estado = 1";
        $params = [];
        if ($buscar !== '') {
            $sql .= " AND nombre LIKE :q";
            $params[':q'] = $buscar . '%';
        }
        $sql .= " ORDER BY nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function crear(array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL InsertarNivel(:nombre, :hi, :hf, @id, @m)");
            $stmt->execute([
                ':nombre' => $d['nombre'],
                ':hi'     => $d['horaInicio'] ?: null,
                ':hf'     => $d['horaFin']    ?: null,
            ]);
            $stmt->closeCursor();
            $out = $this->db->query("SELECT @id AS id, @m AS m")->fetch();
            $ok = ((int) ($out['id'] ?? 0)) > 0;
            return ['ok' => $ok, 'mensaje' => $ok ? 'Nivel registrado correctamente.' : ($out['m'] ?: 'No se pudo registrar.')];
        } catch (Throwable $e) {
            error_log('[NIVEL] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar el nivel.'];
        }
    }

    public function actualizar(int $id, array $d): array
    {
        try {
            $stmt = $this->db->prepare("CALL EditarNivel(:id, :nombre, :hi, :hf, @m)");
            $stmt->execute([
                ':id'     => $id,
                ':nombre' => $d['nombre'],
                ':hi'     => $d['horaInicio'] ?: null,
                ':hf'     => $d['horaFin']    ?: null,
            ]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Nivel actualizado correctamente.' : ($m ?: 'No se pudo actualizar.')];
        } catch (Throwable $e) {
            error_log('[NIVEL] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar el nivel.'];
        }
    }

    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL EliminarNivel(:id, @m)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();
            $m = (string) ($this->db->query("SELECT @m AS m")->fetch()['m'] ?? '');
            $ok = str_starts_with($m, 'OK');
            return ['ok' => $ok, 'mensaje' => $ok ? 'Nivel eliminado correctamente.' : ($m ?: 'No se pudo eliminar.')];
        } catch (Throwable $e) {
            error_log('[NIVEL] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar el nivel.'];
        }
    }
}
