<?php
/**
 * ============================================================
 * masignacion_docente.php  —  Modelo del CRUD Asignación Docente
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Utiliza SQL directo mediante PDO para asociar Gestión, Curso,
 * Materia y Docente.
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloAsignacionDocente
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Lista la asignación docente activa (con filtros de búsqueda opcionales).
     */
    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT ad.idAsignacion,
                       ad.idGestion,
                       ad.idCurso,
                       ad.idMateria,
                       ad.idDocente,
                       ad.fechaRegistro,
                       g.anio AS gestionAnio,
                       c.nombre AS cursoNombre,
                       p.nombre AS paraleloNombre,
                       n.nombre AS nivelNombre,
                       t.nombre AS turnoNombre,
                       m.nombre AS materiaNombre,
                       m.sigla AS materiaSigla,
                       CONCAT(d.apPaterno, ' ', d.apMaterno, ' ', d.nombre) AS docenteNombre,
                       d.foto AS docenteFoto,
                       d.ci AS docenteCi,
                       d.telefono AS docenteTelefono,
                       d.correo AS docenteCorreo
                FROM asignacion_docente ad
                INNER JOIN gestion g   ON ad.idGestion = g.idGestion
                INNER JOIN curso c     ON ad.idCurso = c.idCurso
                INNER JOIN paralelo p  ON c.idParalelo = p.idParalelo
                INNER JOIN nivel n     ON c.idNivel = n.idNivel
                INNER JOIN turno t     ON c.idTurno = t.idTurno
                INNER JOIN materia m   ON ad.idMateria = m.idMateria
                INNER JOIN docente d   ON ad.idDocente = d.idDocente
                WHERE ad.estado = 1";

        $params = [];
        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            foreach ($palabras as $p) {
                $sub = [];
                $campos = [
                    "c.nombre", "m.nombre", "m.sigla", "d.nombre", "d.apPaterno", "d.apMaterno", "g.anio",
                    "CONCAT_WS(' ', d.apPaterno, d.apMaterno, d.nombre)",
                    "CONCAT_WS(' ', d.nombre, d.apPaterno, d.apMaterno)"
                ];
                foreach ($campos as $campo) {
                    $k++;
                    $paramKey = ":qpk{$k}";
                    $sub[] = "{$campo} LIKE {$paramKey}";
                    $params[$paramKey] = $p . '%';
                }
                $condicionesPalabras[] = "(" . implode(" OR ", $sub) . ")";
            }
            if (!empty($condicionesPalabras)) {
                $sql .= " AND " . implode(" AND ", $condicionesPalabras);
            }
        }

        $sql .= " ORDER BY g.anio DESC, n.nombre, c.nombre, p.nombre, m.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los IDs de las asignaciones activas de un docente específico.
     */
    public function listarPorDocente(int $idDocente): array
    {
        $stmt = $this->db->prepare(
            "SELECT idAsignacion 
             FROM asignacion_docente 
             WHERE idDocente = :idDocente AND estado = 1"
        );
        $stmt->execute([':idDocente' => $idDocente]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Da de baja todas las asignaciones activas de un docente específico.
     */
    public function eliminarPorDocente(int $idDocente): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE asignacion_docente 
             SET estado = 0 
             WHERE idDocente = :idDocente"
        );
        return $stmt->execute([':idDocente' => $idDocente]);
    }

    /**
     * Obtiene los detalles de una asignación de docente específica.
     */
    public function obtener(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT idAsignacion, idGestion, idCurso, idMateria, idDocente, estado
             FROM asignacion_docente 
             WHERE idAsignacion = :id AND estado = 1 LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Verifica si una combinación de Gestión, Curso y Materia ya tiene un docente asignado.
     */
    public function existeAsignacion(int $idGestion, int $idCurso, int $idMateria, int $exceptoId = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n 
             FROM asignacion_docente
             WHERE idGestion = :idGestion
               AND idCurso = :idCurso 
               AND idMateria = :idMateria 
               AND estado = 1 
               AND idAsignacion <> :id"
        );
        $stmt->execute([
            ':idGestion' => $idGestion,
            ':idCurso'   => $idCurso,
            ':idMateria' => $idMateria,
            ':id'        => $exceptoId
        ]);
        return (int) ($stmt->fetch()['n'] ?? 0) > 0;
    }

    /**
     * Crea un nuevo registro de asignación docente.
     */
    public function crear(array $d): array
    {
        try {
            // Verificar si ya existe el registro (activo o inactivo) para evitar error de clave duplicada
            $stmtCheck = $this->db->prepare(
                "SELECT idAsignacion FROM asignacion_docente 
                 WHERE idGestion = :idGestion AND idCurso = :idCurso AND idMateria = :idMateria LIMIT 1"
            );
            $stmtCheck->execute([
                ':idGestion' => (int) $d['idGestion'],
                ':idCurso'   => (int) $d['idCurso'],
                ':idMateria' => (int) $d['idMateria']
            ]);
            $fila = $stmtCheck->fetch();

            if ($fila) {
                // Si existe, lo reactivamos (estado = 1) y actualizamos el docente asignado
                $stmt = $this->db->prepare(
                    "UPDATE asignacion_docente 
                     SET estado = 1, idDocente = :idDocente 
                     WHERE idAsignacion = :id"
                );
                $stmt->execute([
                    ':id'        => (int) $fila['idAsignacion'],
                    ':idDocente' => (int) $d['idDocente']
                ]);
            } else {
                // Si no existe, lo creamos de cero
                $stmt = $this->db->prepare(
                    "INSERT INTO asignacion_docente (idGestion, idCurso, idMateria, idDocente, estado)
                     VALUES (:idGestion, :idCurso, :idMateria, :idDocente, 1)"
                );
                $stmt->execute([
                    ':idGestion' => (int) $d['idGestion'],
                    ':idCurso'   => (int) $d['idCurso'],
                    ':idMateria' => (int) $d['idMateria'],
                    ':idDocente' => (int) $d['idDocente']
                ]);
            }

            return [
                'ok'      => true,
                'mensaje' => 'Asignación de docente registrada correctamente.'
            ];
        } catch (Throwable $e) {
            error_log('[ASIGNACION DOCENTE] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar la asignación de docente.'];
        }
    }

    /**
     * Actualiza un registro existente de asignación docente.
     */
    public function actualizar(int $id, array $d): array
    {
        try {
            // Verificar si la combinación idGestion - idCurso - idMateria ya existe en otro registro (activo o inactivo)
            $stmtCheck = $this->db->prepare(
                "SELECT idAsignacion FROM asignacion_docente 
                 WHERE idGestion = :idGestion AND idCurso = :idCurso AND idMateria = :idMateria AND idAsignacion <> :id LIMIT 1"
            );
            $stmtCheck->execute([
                ':idGestion' => (int) $d['idGestion'],
                ':idCurso'   => (int) $d['idCurso'],
                ':idMateria' => (int) $d['idMateria'],
                ':id'        => $id
            ]);
            $fila = $stmtCheck->fetch();

            if ($fila) {
                // Si existe otro registro con esa combinación:
                // 1. Damos de baja el registro actual ($id)
                $stmtDel = $this->db->prepare("UPDATE asignacion_docente SET estado = 0 WHERE idAsignacion = :id");
                $stmtDel->execute([':id' => $id]);

                // 2. Reactivamos el otro registro y actualizamos el docente
                $stmtAct = $this->db->prepare(
                    "UPDATE asignacion_docente 
                     SET estado = 1, idDocente = :idDocente 
                     WHERE idAsignacion = :id"
                );
                $stmtAct->execute([
                    ':id'        => (int) $fila['idAsignacion'],
                    ':idDocente' => (int) $d['idDocente']
                ]);
            } else {
                // Si no existe conflicto, procedemos con la actualización normal
                $stmt = $this->db->prepare(
                    "UPDATE asignacion_docente
                     SET idGestion = :idGestion, idCurso = :idCurso, idMateria = :idMateria, idDocente = :idDocente
                     WHERE idAsignacion = :id AND estado = 1"
                );
                $stmt->execute([
                    ':id'        => $id,
                    ':idGestion' => (int) $d['idGestion'],
                    ':idCurso'   => (int) $d['idCurso'],
                    ':idMateria' => (int) $d['idMateria'],
                    ':idDocente' => (int) $d['idDocente']
                ]);
            }

            return [
                'ok'      => true,
                'mensaje' => 'Asignación de docente actualizada correctamente.'
            ];
        } catch (Throwable $e) {
            error_log('[ASIGNACION DOCENTE] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar la asignación de docente.'];
        }
    }

    /**
     * Elimina lógicamente un registro de asignación docente (estado = 0).
     */
    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE asignacion_docente SET estado = 0 WHERE idAsignacion = :id"
            );
            $stmt->execute([':id' => $id]);

            return [
                'ok'      => true,
                'mensaje' => 'Asignación de docente eliminada correctamente.'
            ];
        } catch (Throwable $e) {
            error_log('[ASIGNACION DOCENTE] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar la asignación de docente.'];
        }
    }

    // ------------------------------------------------------------
    // Métodos Auxiliares para Selectores (Catálogos)
    // ------------------------------------------------------------

    /**
     * Lista las gestiones académicas activas.
     */
    public function listarGestionesActivas(): array
    {
        $sql = "SELECT idGestion, anio FROM gestion WHERE estado = 1 ORDER BY anio DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Lista todos los cursos activos con sus detalles.
     */
    public function listarCursosActivos(): array
    {
        $sql = "SELECT c.idCurso, 
                       c.nombre, 
                       c.nombre AS cursoNombre,
                       c.idNivel, 
                       n.nombre AS nivel, 
                       n.nombre AS nivelNombre,
                       c.idTurno, 
                       t.nombre AS turno, 
                       t.nombre AS turnoNombre,
                       c.idParalelo, 
                       p.nombre AS paralelo, 
                       p.nombre AS paraleloNombre,
                       c.idGestion, 
                       g.anio
                FROM curso c
                INNER JOIN paralelo p ON c.idParalelo = p.idParalelo
                INNER JOIN nivel n     ON c.idNivel = n.idNivel
                INNER JOIN turno t     ON c.idTurno = t.idTurno
                INNER JOIN gestion g   ON c.idGestion = g.idGestion
                WHERE c.estado = 1
                ORDER BY g.anio DESC, n.nombre, FIELD(c.nombre, 'Primero', 'Segundo', 'Tercero', 'Cuarto', 'Quinto', 'Sexto'), p.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Lista todas las materias activas.
     */
    public function listarMateriasActivas(): array
    {
        $sql = "SELECT idMateria, nombre, sigla FROM materia WHERE estado = 1 ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Lista todos los docentes activos con sus nombres completos.
     */
    public function listarDocentesActivos(): array
    {
        $sql = "SELECT idDocente, CONCAT(apPaterno, ' ', apMaterno, ' ', nombre) AS nombreCompleto 
                FROM docente 
                WHERE estado = 1 
                ORDER BY apPaterno, apMaterno, nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function obtenerMateriasDisponiblesPorCurso(int $idGestion): array
    {
        $sql = "SELECT mc.idCurso, m.idMateria, m.nombre AS materiaNombre, m.sigla AS materiaSigla
                FROM malla_curricular mc
                INNER JOIN materia m ON mc.idMateria = m.idMateria
                WHERE mc.estado = 1 AND m.estado = 1
                  AND NOT EXISTS (
                      SELECT 1 
                      FROM asignacion_docente ad 
                      WHERE ad.idCurso = mc.idCurso 
                        AND ad.idMateria = mc.idMateria 
                        AND ad.idGestion = :idGestion 
                        AND ad.estado = 1
                  )
                ORDER BY mc.idCurso, m.nombre ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':idGestion' => $idGestion]);
        $rows = $stmt->fetchAll();

        // Armar el listado indexado por idCurso
        $disponibles = [];
        
        // Inicializar todos los cursos activos para evitar índices indefinidos
        $sqlCursos = "SELECT idCurso FROM curso WHERE estado = 1";
        $cursos = $this->db->query($sqlCursos)->fetchAll();
        foreach ($cursos as $curso) {
            $disponibles[(int)$curso['idCurso']] = [];
        }

        foreach ($rows as $row) {
            $cId = (int)$row['idCurso'];
            $disponibles[$cId][] = [
                'idMateria'     => (int)$row['idMateria'],
                'materiaNombre' => $row['materiaNombre'],
                'materiaSigla'  => $row['materiaSigla']
            ];
        }

        return $disponibles;
    }
}
