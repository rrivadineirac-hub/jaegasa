<?php
/**
 * ============================================================
 * masistencia_docente.php — Modelo CRUD Asistencia (Docente)
 * Sistema Académico JAEGASA
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloAsistenciaDocente
{
    private PDO $db;

    /**
     * Mapa entre los códigos de letra que usa la interfaz (A/R/F/L, ya
     * definidos en asitencia.css/asistencia.js) y las abreviaturas reales
     * del catálogo estado_asistencia (P/T/A/J).
     */
    private const MAPA_ESTADO_UI_A_DB = [
        'A' => 'P', // Asistió   -> Presente
        'R' => 'T', // Retraso   -> Tardanza
        'F' => 'A', // Falta     -> Ausente
        'L' => 'J', // Licencia  -> Justificado
    ];

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Resuelve el idDocente a partir del usuario en sesión.
     */
    public function obtenerDocentePorUsuario(int $idUsuario): ?int
    {
        $stmt = $this->db->prepare(
            "SELECT idDocente FROM docente WHERE idUsuario = :idUsuario AND estado = 1 LIMIT 1"
        );
        $stmt->execute([':idUsuario' => $idUsuario]);
        $fila = $stmt->fetch();
        return $fila ? (int) $fila['idDocente'] : null;
    }

    /**
     * Verifica que un horario efectivamente pertenezca a ese docente (seguridad).
     */
    public function horarioPerteneceADocente(int $idHorario, int $idDocente): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM horario_curso WHERE idHorarioCurso = :idHorario AND idDocente = :idDocente AND estado = 1"
        );
        $stmt->execute([':idHorario' => $idHorario, ':idDocente' => $idDocente]);
        return (int) ($stmt->fetch()['n'] ?? 0) > 0;
    }

    /**
     * Lista los bloques de horario (clases) que le tocan al docente en una
     * fecha específica, con el conteo de estudiantes ya registrados (para
     * mostrar cuáles clases ya tienen asistencia tomada).
     */
    public function obtenerClasesDelDocente(int $idDocente, string $fecha): array
    {
        $diaSemana = $this->nombreDiaSemana($fecha);

        $sql = "SELECT h.idHorarioCurso AS idHorario, h.aula, p.numero AS periodoNumero, p.horaInicio, p.horaFin,
                       c.idCurso, c.nombre AS cursoNombre,
                       par.nombre AS paraleloNombre, niv.nombre AS nivelNombre,
                       m.idMateria, m.nombre AS materiaNombre, m.sigla AS materiaSigla,
                       (SELECT COUNT(*) FROM inscripcion i WHERE i.idCurso = h.idCurso AND i.estado = 1) AS totalEstudiantes,
                       (SELECT COUNT(*) FROM asistencia a WHERE a.idHorario = h.idHorarioCurso AND a.dAsistencia = :fecha AND a.estado = 1) AS totalRegistrados
                FROM horario_curso h
                INNER JOIN curso c      ON c.idCurso = h.idCurso
                INNER JOIN paralelo par ON par.idParalelo = c.idParalelo
                INNER JOIN nivel niv    ON niv.idNivel = c.idNivel
                INNER JOIN materia m    ON m.idMateria = h.idMateria
                INNER JOIN periodo p    ON p.idPeriodo = h.idPeriodo
                WHERE h.idDocente = :idDocente
                  AND h.diaSemana = :diaSemana
                  AND h.estado = 1
                ORDER BY p.numero ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':idDocente' => $idDocente,
            ':diaSemana' => $diaSemana,
            ':fecha'     => $fecha,
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Lista los estudiantes inscritos en el curso de un horario, con su
     * estado de asistencia ya guardado para esa fecha (si existe), para
     * precargar el formulario con lo que ya se había marcado.
     */
    public function listarEstudiantesPorHorarioFecha(int $idHorario, string $fecha): array
    {
        $sql = "SELECT i.idInscripcion, e.idEstudiante, e.rude, e.ci, e.foto,
                       CONCAT(e.apPaterno, ' ', e.apMaterno, ' ', e.nombre) AS estudianteNombre,
                       a.idAsistencia, ea.abreviatura AS estadoActual,
                       a.horaAsistencia, a.observacion
                FROM horario_curso h
                INNER JOIN inscripcion i ON i.idCurso = h.idCurso AND i.estado = 1
                INNER JOIN estudiante e  ON e.idEstudiante = i.idEstudiante
                LEFT JOIN asistencia a   ON a.idInscripcion = i.idInscripcion
                                         AND a.idHorario = h.idHorarioCurso
                                         AND a.dAsistencia = :fecha
                                         AND a.estado = 1
                LEFT JOIN estado_asistencia ea ON ea.idEstadoAsistencia = a.idEstadoAsistencia
                WHERE h.idHorarioCurso = :idHorario
                ORDER BY e.apPaterno, e.apMaterno, e.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':idHorario' => $idHorario, ':fecha' => $fecha]);
        $filas = $stmt->fetchAll();

        // Traduce el estado real (P/T/A/J) al código de letra que usa la UI (A/R/F/L)
        $mapaInverso = array_flip(self::MAPA_ESTADO_UI_A_DB);
        foreach ($filas as &$f) {
            $f['estadoUI'] = $f['estadoActual'] ? ($mapaInverso[$f['estadoActual']] ?? 'A') : null;
        }
        unset($f);

        return $filas;
    }

    /**
     * Guarda (inserta o actualiza) la asistencia de todos los estudiantes
     * de un horario+fecha en una sola transacción.
     */
    public function guardarAsistenciaMasiva(int $idHorario, string $fecha, int $idDocente, array $asistencias): array
    {
        if (empty($asistencias)) {
            return ['ok' => false, 'mensaje' => 'No se recibió ninguna asistencia para guardar.'];
        }

        try {
            $this->db->beginTransaction();

            $stmtEstado = $this->db->prepare(
                "SELECT idEstadoAsistencia FROM estado_asistencia WHERE abreviatura = :abrev LIMIT 1"
            );

            $sqlUpsert = "INSERT INTO asistencia
                              (idInscripcion, idHorario, idDocente, idEstadoAsistencia, dAsistencia, horaAsistencia, observacion)
                          VALUES
                              (:idInscripcion, :idHorario, :idDocente, :idEstado, :fecha, :hora, :observacion)
                          ON DUPLICATE KEY UPDATE
                              idEstadoAsistencia = VALUES(idEstadoAsistencia),
                              idDocente           = VALUES(idDocente),
                              horaAsistencia       = VALUES(horaAsistencia),
                              observacion          = VALUES(observacion)";
            $stmtUpsert = $this->db->prepare($sqlUpsert);

            foreach ($asistencias as $idInscripcion => $datos) {
                $codigoUI = $datos['estado'] ?? ($datos['estado_existente'] ?? 'F');
                $abrevDb  = self::MAPA_ESTADO_UI_A_DB[$codigoUI] ?? 'P';

                $stmtEstado->execute([':abrev' => $abrevDb]);
                $idEstado = $stmtEstado->fetch()['idEstadoAsistencia'] ?? null;
                if (!$idEstado) {
                    continue; // estado desconocido en el catálogo, se omite esta fila
                }

                $stmtUpsert->execute([
                    ':idInscripcion' => (int) $idInscripcion,
                    ':idHorario'     => $idHorario,
                    ':idDocente'     => $idDocente,
                    ':idEstado'      => $idEstado,
                    ':fecha'         => $fecha,
                    ':hora'          => !empty($datos['horaLlegada']) ? $datos['horaLlegada'] . ':00' : null,
                    ':observacion'   => $datos['observacion'] ?? null,
                ]);
            }

            $this->db->commit();
            return ['ok' => true, 'mensaje' => 'Asistencia registrada correctamente.'];
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('[ASISTENCIA DOCENTE] guardarAsistenciaMasiva: ' . $e->getMessage());
            // Si el error viene de un trigger (SIGNAL SQLSTATE), el texto real ya viene en getMessage()
            return ['ok' => false, 'mensaje' => 'No se pudo guardar la asistencia: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene todos los bloques del horario semanal asignados al docente,
     * indexados por [diaSemana][idPeriodo].
     */
    public function obtenerHorarioSemanalDocente(int $idDocente): array
    {
        $sql = "SELECT h.idHorarioCurso AS idHorario, h.diaSemana, h.idPeriodo, h.aula,
                       p.numero AS periodoNumero, p.horaInicio, p.horaFin,
                       c.idCurso, c.nombre AS cursoNombre,
                       par.nombre AS paraleloNombre, niv.nombre AS nivelNombre,
                       m.idMateria, m.nombre AS materiaNombre, m.sigla AS materiaSigla,
                       (SELECT COUNT(*) FROM inscripcion i WHERE i.idCurso = h.idCurso AND i.estado = 1) AS totalEstudiantes
                FROM horario_curso h
                INNER JOIN curso c      ON c.idCurso = h.idCurso
                INNER JOIN paralelo par ON par.idParalelo = c.idParalelo
                INNER JOIN nivel niv    ON niv.idNivel = c.idNivel
                INNER JOIN materia m    ON m.idMateria = h.idMateria
                INNER JOIN periodo p    ON p.idPeriodo = h.idPeriodo
                WHERE h.idDocente = :idDocente
                  AND h.estado = 1
                ORDER BY p.numero ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':idDocente' => $idDocente]);
        $rows = $stmt->fetchAll();

        $matriz = [];
        foreach ($rows as $r) {
            $dia = $r['diaSemana'];
            $per = (int) $r['idPeriodo'];
            $matriz[$dia][$per] = $r;
        }

        return $matriz;
    }

    /**
     * Lista los períodos escolares registrados en el catálogo.
     */
    public function listarPeriodos(): array
    {
        $sql = "SELECT idPeriodo, numero, horaInicio, horaFin, esRecreo 
                FROM periodo 
                WHERE estado = 1 
                ORDER BY horaInicio ASC";
        return $this->db->query($sql)->fetchAll();
    }

    private function nombreDiaSemana(string $fecha): string
    {
        $dias = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
        return $dias[(int) date('w', strtotime($fecha))];
    }
}