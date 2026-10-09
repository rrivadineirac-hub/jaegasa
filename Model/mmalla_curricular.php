<?php
/**
 * ============================================================
 * mmalla_curricular.php  —  Modelo del CRUD Malla Curricular
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Utiliza SQL directo mediante PDO para asociar Curso, Materia
 * y Docente con su respectiva carga horaria.
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloMallaCurricular
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Lista la malla curricular activa (con filtros de búsqueda opcionales).
     */
    public function listar(string $buscar = '', string $grado = '', string $paralelo = '', string $nivel = '', string $turno = '', string $materia = '', string $docente = ''): array
    {
        $sql = "SELECT mc.idMallaCurricular,
                       mc.idCurso,
                       mc.idMateria,
                       mc.cargaHoraria,
                       mc.fechaRegistro,
                       c.nombre AS cursoNombre,
                       c.idParalelo,
                       p.nombre AS paraleloNombre,
                       c.idNivel,
                       n.nombre AS nivelNombre,
                       c.idTurno,
                       t.nombre AS turnoNombre,
                       g.anio AS gestionAnio,
                       m.nombre AS materiaNombre,
                       m.sigla AS materiaSigla,
                       CONCAT(doc.apPaterno, ' ', doc.apMaterno, ' ', doc.nombre) AS docenteCompleto
                FROM malla_curricular mc
                INNER JOIN curso c     ON mc.idCurso = c.idCurso
                INNER JOIN paralelo p  ON c.idParalelo = p.idParalelo
                INNER JOIN nivel n     ON c.idNivel = n.idNivel
                INNER JOIN turno t     ON c.idTurno = t.idTurno
                INNER JOIN gestion g   ON c.idGestion = g.idGestion
                INNER JOIN materia m   ON mc.idMateria = m.idMateria
                LEFT JOIN asignacion_docente ad ON ad.idCurso = c.idCurso AND ad.idMateria = m.idMateria AND ad.estado = 1
                LEFT JOIN docente doc ON ad.idDocente = doc.idDocente
                WHERE mc.estado = 1";

        $params = [];
        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            foreach ($palabras as $p) {
                $sub = [];
                $campos = ["c.nombre", "m.nombre", "m.sigla"];
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

        if ($grado !== '') {
            $sql .= " AND c.nombre = :grado";
            $params[':grado'] = $grado;
        }
        if ($paralelo !== '') {
            $sql .= " AND c.idParalelo = :paralelo";
            $params[':paralelo'] = (int) $paralelo;
        }
        if ($nivel !== '') {
            $sql .= " AND c.idNivel = :nivel";
            $params[':nivel'] = (int) $nivel;
        }
        if ($turno !== '') {
            $sql .= " AND c.idTurno = :turno";
            $params[':turno'] = (int) $turno;
        }
        if ($materia !== '') {
            $sql .= " AND mc.idMateria = :materia";
            $params[':materia'] = (int) $materia;
        }
        if ($docente !== '') {
            $sql .= " AND ad.idDocente = :docente";
            $params[':docente'] = (int) $docente;
        }

        $sql .= " ORDER BY g.anio DESC, n.nombre, FIELD(c.nombre, 'Primero', 'Segundo', 'Tercero', 'Cuarto', 'Quinto', 'Sexto'), c.nombre, p.nombre, m.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los detalles de una asignación específica.
     */
    public function obtener(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT idMallaCurricular, idCurso, idMateria, cargaHoraria, estado
             FROM malla_curricular 
             WHERE idMallaCurricular = :id AND estado = 1 LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Verifica si una combinación de Curso y Materia ya existe en la malla curricular.
     */
    public function existeAsignacion(int $idCurso, int $idMateria, int $exceptoId = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n 
             FROM malla_curricular
             WHERE idCurso = :idCurso 
               AND idMateria = :idMateria 
               AND estado = 1 
               AND idMallaCurricular <> :id"
        );
        $stmt->execute([
            ':idCurso'   => $idCurso,
            ':idMateria' => $idMateria,
            ':id'        => $exceptoId
        ]);
        return (int) ($stmt->fetch()['n'] ?? 0) > 0;
    }

    /**
     * Crea un nuevo registro de malla curricular.
     */
    public function crear(array $d): array
    {
        try {
            // Verificar si ya existe el registro (activo o inactivo) para evitar error de clave duplicada
            $stmtCheck = $this->db->prepare(
                "SELECT idMallaCurricular FROM malla_curricular 
                 WHERE idCurso = :idCurso AND idMateria = :idMateria LIMIT 1"
            );
            $stmtCheck->execute([
                ':idCurso'   => (int) $d['idCurso'],
                ':idMateria' => (int) $d['idMateria']
            ]);
            $fila = $stmtCheck->fetch();

            if ($fila) {
                // Si existe, lo reactivamos (estado = 1) y actualizamos la carga horaria
                $stmt = $this->db->prepare(
                    "UPDATE malla_curricular 
                     SET estado = 1, cargaHoraria = :cargaHoraria 
                     WHERE idMallaCurricular = :id"
                );
                $stmt->execute([
                    ':id'           => (int) $fila['idMallaCurricular'],
                    ':cargaHoraria' => !empty($d['cargaHoraria']) ? (int) $d['cargaHoraria'] : null
                ]);
            } else {
                // Si no existe, lo creamos
                $stmt = $this->db->prepare(
                    "INSERT INTO malla_curricular (idCurso, idMateria, cargaHoraria, estado)
                     VALUES (:idCurso, :idMateria, :cargaHoraria, 1)"
                );
                $stmt->execute([
                    ':idCurso'      => (int) $d['idCurso'],
                    ':idMateria'    => (int) $d['idMateria'],
                    ':cargaHoraria' => !empty($d['cargaHoraria']) ? (int) $d['cargaHoraria'] : null
                ]);
            }

            return [
                'ok'      => true,
                'mensaje' => 'Asignación curricular registrada correctamente.'
            ];
        } catch (Throwable $e) {
            error_log('[MALLA CURRICULAR] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar la asignación curricular.'];
        }
    }

    /**
     * Sincroniza las materias asignadas a un curso (paso por lote para crear/editar).
     */
    public function sincronizarCurso(int $idCurso, array $materiasSeleccionadas, array $cargasHorarias): array
    {
        try {
            $this->db->beginTransaction();

            // 1. Desactivar temporalmente todas las materias asociadas a este curso
            $stmtDisable = $this->db->prepare("UPDATE malla_curricular SET estado = 0 WHERE idCurso = :idCurso");
            $stmtDisable->execute([':idCurso' => $idCurso]);

            // 2. Insertar o reactivar las materias seleccionadas con sus cargas horarias
            foreach ($materiasSeleccionadas as $idMateria) {
                $idMateria = (int) $idMateria;
                if ($idMateria <= 0) continue;

                $carga = isset($cargasHorarias[$idMateria]) && $cargasHorarias[$idMateria] !== '' ? (int) $cargasHorarias[$idMateria] : null;

                // Verificar si ya existe registro en la tabla
                $stmtCheck = $this->db->prepare(
                    "SELECT idMallaCurricular FROM malla_curricular 
                     WHERE idCurso = :idCurso AND idMateria = :idMateria LIMIT 1"
                );
                $stmtCheck->execute([
                    ':idCurso'   => $idCurso,
                    ':idMateria' => $idMateria
                ]);
                $fila = $stmtCheck->fetch();

                if ($fila) {
                    // Reactivar y actualizar carga horaria
                    $stmt = $this->db->prepare(
                        "UPDATE malla_curricular 
                         SET estado = 1, cargaHoraria = :cargaHoraria 
                         WHERE idMallaCurricular = :id"
                    );
                    $stmt->execute([
                        ':id'           => (int) $fila['idMallaCurricular'],
                        ':cargaHoraria' => $carga
                    ]);
                } else {
                    // Crear nuevo
                    $stmt = $this->db->prepare(
                        "INSERT INTO malla_curricular (idCurso, idMateria, cargaHoraria, estado)
                         VALUES (:idCurso, :idMateria, :cargaHoraria, 1)"
                    );
                    $stmt->execute([
                        ':idCurso'      => $idCurso,
                        ':idMateria'    => $idMateria,
                        ':cargaHoraria' => $carga
                    ]);
                }
            }

            $this->db->commit();
            return [
                'ok'      => true,
                'mensaje' => 'Malla curricular guardada correctamente.'
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[MALLA CURRICULAR] sincronizarCurso: ' . $e->getMessage());
            return [
                'ok'      => false,
                'mensaje' => 'No se pudo guardar la malla curricular. Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza un registro existente de malla curricular.
     */
    public function actualizar(int $id, array $d): array
    {
        try {
            // Verificar si la combinación idCurso - idMateria ya existe en otro registro (activo o inactivo)
            $stmtCheck = $this->db->prepare(
                "SELECT idMallaCurricular FROM malla_curricular 
                 WHERE idCurso = :idCurso AND idMateria = :idMateria AND idMallaCurricular <> :id LIMIT 1"
            );
            $stmtCheck->execute([
                ':idCurso'   => (int) $d['idCurso'],
                ':idMateria' => (int) $d['idMateria'],
                ':id'        => $id
            ]);
            $fila = $stmtCheck->fetch();

            if ($fila) {
                // Si existe otro registro con esa combinación:
                // 1. Damos de baja el registro actual ($id)
                $stmtDel = $this->db->prepare("UPDATE malla_curricular SET estado = 0 WHERE idMallaCurricular = :id");
                $stmtDel->execute([':id' => $id]);

                // 2. Reactivamos el otro registro y actualizamos su carga horaria
                $stmtAct = $this->db->prepare(
                    "UPDATE malla_curricular 
                     SET estado = 1, cargaHoraria = :cargaHoraria 
                     WHERE idMallaCurricular = :id"
                );
                $stmtAct->execute([
                    ':id'           => (int) $fila['idMallaCurricular'],
                    ':cargaHoraria' => !empty($d['cargaHoraria']) ? (int) $d['cargaHoraria'] : null
                ]);
            } else {
                // Si no existe conflicto, procedemos con la actualización normal
                $stmt = $this->db->prepare(
                    "UPDATE malla_curricular
                     SET idCurso = :idCurso, idMateria = :idMateria, cargaHoraria = :cargaHoraria
                     WHERE idMallaCurricular = :id AND estado = 1"
                );
                $stmt->execute([
                    ':id'           => $id,
                    ':idCurso'      => (int) $d['idCurso'],
                    ':idMateria'    => (int) $d['idMateria'],
                    ':cargaHoraria' => !empty($d['cargaHoraria']) ? (int) $d['cargaHoraria'] : null
                ]);
            }

            return [
                'ok'      => true,
                'mensaje' => 'Asignación curricular actualizada correctamente.'
            ];
        } catch (Throwable $e) {
            error_log('[MALLA CURRICULAR] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar la asignación curricular.'];
        }
    }

    /**
     * Elimina lógicamente un registro de malla curricular (estado = 0).
     */
    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE malla_curricular SET estado = 0 WHERE idMallaCurricular = :id"
            );
            $stmt->execute([':id' => $id]);

            return [
                'ok'      => true,
                'mensaje' => 'Asignación curricular eliminada correctamente.'
            ];
        } catch (Throwable $e) {
            error_log('[MALLA CURRICULAR] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar la asignación curricular.'];
        }
    }

    /**
     * Elimina lógicamente todas las materias de la malla curricular de un curso completo (estado = 0).
     */
    public function eliminarCurso(int $idCurso): array
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE malla_curricular SET estado = 0 WHERE idCurso = :idCurso"
            );
            $stmt->execute([':idCurso' => $idCurso]);

            return [
                'ok'      => true,
                'mensaje' => 'La malla curricular del curso se eliminó correctamente.'
            ];
        } catch (Throwable $e) {
            error_log('[MALLA CURRICULAR] eliminarCurso: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar la malla curricular del curso.'];
        }
    }

    // ------------------------------------------------------------
    // Métodos Auxiliares para Selectores (Catálogos)
    // ------------------------------------------------------------

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
                ORDER BY g.anio DESC, n.nombre, FIELD(c.nombre, 'Primero', 'Segundo', 'Tercero', 'Cuarto', 'Quinto', 'Sexto'), c.nombre, p.nombre, t.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Obtiene la lista de materias activas
     */
    public function listarMateriasActivas(): array
    {
        $sql = "SELECT idMateria, nombre, sigla FROM materia WHERE estado = 1 ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Obtiene la lista de docentes activos
     */
    public function listarDocentesActivos(): array
    {
        $sql = "SELECT idDocente, CONCAT(apPaterno, ' ', apMaterno, ' ', nombre) AS nombreCompleto 
                FROM docente 
                WHERE estado = 1 
                ORDER BY apPaterno, apMaterno, nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }
}
