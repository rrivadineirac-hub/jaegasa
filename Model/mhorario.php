<?php
/**
 * ============================================================
 * mhorario.php  —  Modelo del CRUD Horario por Curso
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Interactúa con la tabla horario_curso, curso, materia, docente
 * y periodo para planificar los horarios escolares.
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloHorario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Obtiene la lista de cursos activos de vista_curso_activos.
     */
    public function listarCursos(): array
    {
        $sql = "SELECT idCurso, nombreCurso, idNivel, idTurno, idParalelo, nivel, turno, paralelo, anio, descripcionCompleta 
                FROM vista_curso_activos 
                WHERE estado = 1 
                ORDER BY nivel, nombreCurso, paralelo";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Obtiene la lista de materias activas.
     */
    public function listarMaterias(): array
    {
        $sql = "SELECT idMateria, nombre, sigla 
                FROM materia 
                WHERE estado = 1 
                ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Obtiene la lista de materias asociadas a un curso desde la malla curricular, incluyendo su carga horaria semanal.
     */
    public function listarMateriasPorCurso(int $idCurso): array
    {
        $sql = "SELECT m.idMateria, m.nombre, m.sigla, mc.cargaHoraria
                FROM materia m
                INNER JOIN malla_curricular mc ON m.idMateria = mc.idMateria AND mc.idCurso = :idCurso AND mc.estado = 1
                WHERE m.estado = 1
                ORDER BY m.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':idCurso' => $idCurso]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el número total de períodos asignados por materia para un curso específico.
     * Retorna un arreglo asociativo: [idMateria => totalPeriodosAsignados]
     */
    public function obtenerConteoMateriasHorario(int $idCurso): array
    {
        $sql = "SELECT idMateria, COUNT(*) AS total
                FROM horario_curso
                WHERE idCurso = :idCurso AND estado = 1
                GROUP BY idMateria";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':idCurso' => $idCurso]);
        $rows = $stmt->fetchAll();

        $conteo = [];
        foreach ($rows as $row) {
            $conteo[(int)$row['idMateria']] = (int)$row['total'];
        }
        return $conteo;
    }

    /**
     * Valida si la asignación de una materia a una celda del horario no supera la carga horaria semanal.
     * Retorna ['ok' => bool, 'mensaje' => string].
     */
    public function validarCargaHorariaMateria(int $idCurso, int $idMateria, string $diaSemana, int $idPeriodo): array
    {
        $sqlMalla = "SELECT mc.cargaHoraria, m.nombre AS materiaNombre, m.sigla AS materiaSigla
                     FROM materia m
                     INNER JOIN malla_curricular mc ON m.idMateria = mc.idMateria AND mc.idCurso = :idCurso AND mc.estado = 1
                     WHERE m.idMateria = :idMateria LIMIT 1";
        $stmtMalla = $this->db->prepare($sqlMalla);
        $stmtMalla->execute([':idCurso' => $idCurso, ':idMateria' => $idMateria]);
        $malla = $stmtMalla->fetch();

        if (!$malla) {
            return [
                'ok' => false,
                'mensaje' => 'La materia seleccionada no está registrada en la malla curricular de este curso.'
            ];
        }

        $cargaHoraria = $malla['cargaHoraria'] !== null ? (int)$malla['cargaHoraria'] : null;
        if ($cargaHoraria === null || $cargaHoraria <= 0) {
            return ['ok' => true, 'mensaje' => ''];
        }

        $sqlConteo = "SELECT COUNT(*) AS total
                      FROM horario_curso
                      WHERE idCurso = :idCurso 
                        AND idMateria = :idMateria 
                        AND NOT (diaSemana = :diaSemana AND idPeriodo = :idPeriodo)
                        AND estado = 1";
        $stmtConteo = $this->db->prepare($sqlConteo);
        $stmtConteo->execute([
            ':idCurso'   => $idCurso,
            ':idMateria' => $idMateria,
            ':diaSemana' => $diaSemana,
            ':idPeriodo' => $idPeriodo
        ]);
        $totalOtrasCeldas = (int)($stmtConteo->fetch()['total'] ?? 0);

        if ($totalOtrasCeldas >= $cargaHoraria) {
            $nombreMateria = '[' . ($malla['materiaSigla'] ?? '') . '] ' . ($malla['materiaNombre'] ?? '');
            return [
                'ok' => false,
                'mensaje' => "La materia {$nombreMateria} ya alcanzó su límite máximo de carga horaria semanal ({$cargaHoraria} períodos)."
            ];
        }

        return ['ok' => true, 'mensaje' => ''];
    }


    /**
     * Obtiene la lista de docentes activos con su nombre completo concatenado.
     */
    public function listarDocentes(): array
    {
        $sql = "SELECT idDocente, CONCAT(apPaterno, ' ', apMaterno, ' ', nombre) AS nombreCompleto 
                FROM docente 
                WHERE estado = 1 
                ORDER BY apPaterno, apMaterno, nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Obtiene la lista de períodos escolares activos.
     */
    public function listarPeriodos(): array
    {
        $sql = "SELECT idPeriodo, numero, horaInicio, horaFin, esRecreo 
                FROM periodo 
                WHERE estado = 1 
                ORDER BY horaInicio ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Obtiene todas las asignaciones horarias vigentes de un curso específico.
     */
    public function obtenerHorarioCurso(int $idCurso): array
    {
        $sql = "SELECT hc.idHorarioCurso, hc.idCurso, hc.diaSemana, hc.idPeriodo, hc.idMateria, hc.idDocente, hc.aula,
                      m.nombre AS materiaNombre, m.sigla AS materiaSigla,
                      CONCAT(d.apPaterno, ' ', d.apMaterno, ' ', d.nombre) AS docenteNombre
               FROM horario_curso hc
               INNER JOIN materia m ON hc.idMateria = m.idMateria
               LEFT JOIN docente d ON hc.idDocente = d.idDocente
               WHERE hc.idCurso = :idCurso AND hc.estado = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':idCurso' => $idCurso]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene las asignaciones de docentes activas para un curso específico.
     */
    public function obtenerAsignacionesDocente(int $idCurso): array
    {
        $sql = "SELECT ad.idMateria, ad.idDocente, 
                       CONCAT(d.apPaterno, ' ', d.apMaterno, ' ', d.nombre) AS nombreCompleto
                FROM asignacion_docente ad
                INNER JOIN docente d ON ad.idDocente = d.idDocente
                WHERE ad.idCurso = :idCurso AND ad.estado = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':idCurso' => $idCurso]);
        
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row['idMateria']] = [
                'idDocente' => (int)$row['idDocente'],
                'nombreCompleto' => $row['nombreCompleto']
            ];
        }
        return $result;
    }

    /**
     * Obtiene el mapeo de docentes a sus aulas asignadas (tutorías).
     */
    public function obtenerAulasDocente(): array
    {
        $sql = "SELECT idDocente, nombre 
                FROM aula 
                WHERE idDocente IS NOT NULL AND idDocente > 0 AND estado = 1";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Obtiene los datos de la gestión escolar actual/activa.
     */
    public function obtenerGestionActual(): array
    {
        $sql = "SELECT idGestion, anio FROM gestion WHERE estado = 1 ORDER BY anio DESC LIMIT 1";
        $row = $this->db->query($sql)->fetch();
        return $row ? [
            'idGestion' => (int)$row['idGestion'],
            'anio' => $row['anio']
        ] : [];
    }

    /**
     * Inserta o actualiza una celda horaria del curso (idCurso, diaSemana, idPeriodo).
     * Se beneficia de la clave única uq_curso_dia_periodo.
     */
    public function guardarCelda(array $d): bool
    {
        try {
            $sql = "INSERT INTO horario_curso (idCurso, diaSemana, idPeriodo, idMateria, idDocente, aula, estado) 
                    VALUES (:idCurso, :diaSemana, :idPeriodo, :idMateria, :idDocente, :aula, 1) 
                    ON DUPLICATE KEY UPDATE 
                        idMateria = VALUES(idMateria), 
                        idDocente = VALUES(idDocente), 
                        aula = VALUES(aula), 
                        estado = 1";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':idCurso'   => $d['idCurso'],
                ':diaSemana' => $d['diaSemana'],
                ':idPeriodo' => $d['idPeriodo'],
                ':idMateria' => $d['idMateria'],
                ':idDocente' => $d['idDocente'] ?: null,
                ':aula'      => $d['aula'] !== '' ? $d['aula'] : null
            ]);
        } catch (Throwable $e) {
            error_log('[HORARIO_CURSO] guardarCelda: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina físicamente una celda horaria de un curso.
     */
    public function eliminarCelda(int $idCurso, string $diaSemana, int $idPeriodo): bool
    {
        try {
            $sql = "DELETE FROM horario_curso 
                    WHERE idCurso = :idCurso AND diaSemana = :diaSemana AND idPeriodo = :idPeriodo";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':idCurso'   => $idCurso,
                ':diaSemana' => $diaSemana,
                ':idPeriodo' => $idPeriodo
            ]);
        } catch (Throwable $e) {
            error_log('[HORARIO_CURSO] eliminarCelda: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina físicamente una asignación horaria por su ID único.
     */
    public function eliminarPorId(int $idHorarioCurso): bool
    {
        try {
            $sql = "DELETE FROM horario_curso WHERE idHorarioCurso = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $idHorarioCurso]);
        } catch (Throwable $e) {
            error_log('[HORARIO_CURSO] eliminarPorId: ' . $e->getMessage());
            return false;
        }
    }
}
