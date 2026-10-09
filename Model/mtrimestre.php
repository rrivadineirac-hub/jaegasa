<?php
/**
 * ============================================================
 * mtrimestre.php — Modelo del CRUD Trimestre
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Maneja la persistencia y reglas de negocio para los 3 trimestres
 * lectivos de cada gestión académica.
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloTrimestre
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
        $this->crearTablaSiNoExiste();
    }

    /**
     * Asegura la existencia de la tabla Trimestre en la base de datos.
     */
    private function crearTablaSiNoExiste(): void
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS Trimestre (
                idTrimestre INT AUTO_INCREMENT PRIMARY KEY,
                idGestion INT NOT NULL,
                numero TINYINT NOT NULL COMMENT '1: Primer Trimestre, 2: Segundo Trimestre, 3: Tercer Trimestre',
                nombre VARCHAR(50) NOT NULL,
                fechaInicio DATE NULL,
                fechaFin DATE NULL,
                estadoTrimestre ENUM('Pendiente', 'En Curso', 'Cerrado') NOT NULL DEFAULT 'Pendiente',
                observacion VARCHAR(255) NULL,
                estado TINYINT NOT NULL DEFAULT 1,
                fechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_trimestre_gestion (idGestion),
                CONSTRAINT unq_gestion_numero UNIQUE (idGestion, numero)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->db->exec($sql);
        } catch (Throwable $e) {
            error_log('[TRIMESTRE] Error creando tabla Trimestre: ' . $e->getMessage());
        }
    }

    /**
     * Lista los trimestres activos filtrados por gestión y/o término de búsqueda.
     */
    public function listar(string $buscar = '', ?int $idGestion = null): array
    {
        $sql = "SELECT t.idTrimestre, t.idGestion, t.numero, t.nombre, 
                       t.fechaInicio, t.fechaFin, t.estadoTrimestre, 
                       t.observacion, t.estado, t.fechaRegistro,
                       g.anio AS gestionAnio
                FROM Trimestre t
                INNER JOIN Gestion g ON t.idGestion = g.idGestion
                WHERE t.estado = 1 AND g.estado = 1";

        $params = [];

        if ($idGestion !== null && $idGestion > 0) {
            $sql .= " AND t.idGestion = :idGestion";
            $params[':idGestion'] = $idGestion;
        }

        if ($buscar !== '') {
            $sql .= " AND (t.nombre LIKE :q OR g.anio LIKE :q OR t.observacion LIKE :q)";
            $params[':q'] = '%' . $buscar . '%';
        }

        $sql .= " ORDER BY g.anio DESC, t.numero ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los trimestres pertenecientes a una gestión específica.
     */
    public function listarPorGestion(int $idGestion): array
    {
        return $this->listar('', $idGestion);
    }

    /**
     * Obtiene los datos de un trimestre por su ID.
     */
    public function obtener(int $idTrimestre): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*, g.anio AS gestionAnio 
             FROM Trimestre t 
             INNER JOIN Gestion g ON t.idGestion = g.idGestion 
             WHERE t.idTrimestre = :id AND t.estado = 1 LIMIT 1"
        );
        $stmt->execute([':id' => $idTrimestre]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Inicializa automáticamente los 3 trimestres de una gestión si no existen.
     */
    public function inicializarGestion(int $idGestion): array
    {
        try {
            // Verificar el año de la gestión
            $stmtG = $this->db->prepare("SELECT idGestion, anio FROM Gestion WHERE idGestion = :g AND estado = 1 LIMIT 1");
            $stmtG->execute([':g' => $idGestion]);
            $g = $stmtG->fetch();

            if (!$g) {
                return ['ok' => false, 'mensaje' => 'La gestión especificada no existe o está inactiva.'];
            }

            $anio = (int)$g['anio'];
            $trimestresDefecto = [
                1 => ['nombre' => 'Primer Trimestre',  'inicio' => "{$anio}-02-01", 'fin' => "{$anio}-05-15"],
                2 => ['nombre' => 'Segundo Trimestre', 'inicio' => "{$anio}-05-16", 'fin' => "{$anio}-08-31"],
                3 => ['nombre' => 'Tercer Trimestre',  'inicio' => "{$anio}-09-01", 'fin' => "{$anio}-11-30"],
            ];

            $creados = 0;
            $sql = "INSERT INTO Trimestre (idGestion, numero, nombre, fechaInicio, fechaFin, estadoTrimestre, estado)
                    VALUES (:idGestion, :numero, :nombre, :fechaInicio, :fechaFin, 'Pendiente', 1)
                    ON DUPLICATE KEY UPDATE 
                        estado = 1,
                        fechaInicio = COALESCE(fechaInicio, VALUES(fechaInicio)),
                        fechaFin = COALESCE(fechaFin, VALUES(fechaFin))";

            $stmt = $this->db->prepare($sql);

            foreach ($trimestresDefecto as $num => $t) {
                $stmt->execute([
                    ':idGestion'   => $idGestion,
                    ':numero'      => $num,
                    ':nombre'      => $t['nombre'],
                    ':fechaInicio' => $t['inicio'],
                    ':fechaFin'    => $t['fin']
                ]);
                $creados++;
            }

            return ['ok' => true, 'mensaje' => "Se inicializaron los 3 trimestres para la gestión {$anio}."];
        } catch (Throwable $e) {
            error_log('[TRIMESTRE] inicializarGestion: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudieron inicializar los trimestres: ' . $e->getMessage()];
        }
    }

    /**
     * Registra un nuevo trimestre en la base de datos.
     */
    public function crear(array $d): array
    {
        try {
            $idGestion = (int)($d['idGestion'] ?? 0);
            $numero    = (int)($d['numero'] ?? 1);
            $nombre    = trim($d['nombre'] ?? '');
            $inicio    = !empty($d['fechaInicio']) ? $d['fechaInicio'] : null;
            $fin       = !empty($d['fechaFin']) ? $d['fechaFin'] : null;
            $estadoT   = !empty($d['estadoTrimestre']) ? $d['estadoTrimestre'] : 'Pendiente';
            $obs       = !empty($d['observacion']) ? trim($d['observacion']) : null;

            if ($idGestion <= 0) {
                return ['ok' => false, 'mensaje' => 'Debe seleccionar una gestión válida.'];
            }
            if ($numero < 1 || $numero > 3) {
                return ['ok' => false, 'mensaje' => 'El número de trimestre debe ser 1, 2 o 3.'];
            }
            if ($nombre === '') {
                return ['ok' => false, 'mensaje' => 'El nombre del trimestre es obligatorio.'];
            }

            // Verificar si el trimestre ya existe en la gestión
            $chk = $this->db->prepare("SELECT idTrimestre FROM Trimestre WHERE idGestion = :g AND numero = :n AND estado = 1 LIMIT 1");
            $chk->execute([':g' => $idGestion, ':n' => $numero]);
            if ($chk->fetch()) {
                return ['ok' => false, 'mensaje' => "El Trimestre {$numero} ya existe en la gestión seleccionada."];
            }

            $stmt = $this->db->prepare(
                "INSERT INTO Trimestre (idGestion, numero, nombre, fechaInicio, fechaFin, estadoTrimestre, observacion, estado)
                 VALUES (:idGestion, :numero, :nombre, :fechaInicio, :fechaFin, :estadoTrimestre, :observacion, 1)"
            );

            $stmt->execute([
                ':idGestion'       => $idGestion,
                ':numero'          => $numero,
                ':nombre'          => $nombre,
                ':fechaInicio'     => $inicio,
                ':fechaFin'        => $fin,
                ':estadoTrimestre' => $estadoT,
                ':observacion'     => $obs
            ]);

            return ['ok' => true, 'mensaje' => 'Trimestre creado correctamente.'];
        } catch (Throwable $e) {
            error_log('[TRIMESTRE] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo crear el trimestre.'];
        }
    }

    /**
     * Actualiza los datos y fechas manuales de un trimestre.
     */
    public function actualizar(int $idTrimestre, array $d): array
    {
        try {
            $nombre  = trim($d['nombre'] ?? '');
            $inicio  = !empty($d['fechaInicio']) ? $d['fechaInicio'] : null;
            $fin     = !empty($d['fechaFin']) ? $d['fechaFin'] : null;
            $estadoT = !empty($d['estadoTrimestre']) ? $d['estadoTrimestre'] : 'Pendiente';
            $obs     = !empty($d['observacion']) ? trim($d['observacion']) : null;

            if ($nombre === '') {
                return ['ok' => false, 'mensaje' => 'El nombre del trimestre es obligatorio.'];
            }

            if ($inicio && $fin && $inicio > $fin) {
                return ['ok' => false, 'mensaje' => 'La fecha de inicio no puede ser posterior a la fecha de fin.'];
            }

            $stmt = $this->db->prepare(
                "UPDATE Trimestre
                 SET nombre = :nombre,
                     fechaInicio = :fechaInicio,
                     fechaFin = :fechaFin,
                     estadoTrimestre = :estadoTrimestre,
                     observacion = :observacion
                 WHERE idTrimestre = :id AND estado = 1"
            );

            $stmt->execute([
                ':id'              => $idTrimestre,
                ':nombre'          => $nombre,
                ':fechaInicio'     => $inicio,
                ':fechaFin'        => $fin,
                ':estadoTrimestre' => $estadoT,
                ':observacion'     => $obs
            ]);

            return ['ok' => true, 'mensaje' => 'Trimestre actualizado correctamente.'];
        } catch (Throwable $e) {
            error_log('[TRIMESTRE] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar el trimestre.'];
        }
    }

    /**
     * Realiza el borrado lógico del trimestre.
     */
    public function eliminar(int $idTrimestre): array
    {
        try {
            $stmt = $this->db->prepare("UPDATE Trimestre SET estado = 0 WHERE idTrimestre = :id");
            $stmt->execute([':id' => $idTrimestre]);
            return ['ok' => true, 'mensaje' => 'Trimestre eliminado correctamente.'];
        } catch (Throwable $e) {
            error_log('[TRIMESTRE] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar el trimestre.'];
        }
    }

    /**
     * Devuelve la lista de gestiones activas ordenadas de forma descendente por año.
     */
    public function listarGestionesActivas(): array
    {
        $stmt = $this->db->query("SELECT idGestion, anio FROM Gestion WHERE estado = 1 ORDER BY anio DESC");
        return $stmt->fetchAll();
    }

    /**
     * Obtiene la gestión más reciente o activa.
     */
    public function obtenerGestionActual(): ?array
    {
        $stmt = $this->db->query("SELECT idGestion, anio FROM Gestion WHERE estado = 1 ORDER BY anio DESC LIMIT 1");
        $res = $stmt->fetch();
        return $res ?: null;
    }
}
