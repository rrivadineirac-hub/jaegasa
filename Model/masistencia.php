<?php
/**
 * ============================================================
 * masistencia.php  —  Modelo del CRUD Asistencia (General)
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Gestiona el paso de asistencia diaria de los alumnos
 * en la tabla asistencia_general.
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloAsistencia
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function listarCursos(): array
    {
        $sql = "SELECT c.idCurso,
                       c.nombre AS cursoNombre,
                       c.idNivel, n.nombre AS nivelNombre,
                       c.idTurno, t.nombre AS turnoNombre,
                       c.idParalelo, p.nombre AS paraleloNombre,
                       c.idGestion, g.anio
                FROM Curso c
                INNER JOIN Gestion g  ON g.idGestion  = c.idGestion
                INNER JOIN Nivel n    ON n.idNivel    = c.idNivel
                INNER JOIN Turno t    ON t.idTurno    = c.idTurno
                INNER JOIN Paralelo p ON p.idParalelo = c.idParalelo
                WHERE c.estado = 1
                ORDER BY g.anio DESC, n.nombre, t.nombre, p.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Obtiene el idAdministrativo asociado al idUsuario logueado.
     */
    public function obtenerAdministrativoPorUsuario(int $idUsuario): ?int
    {
        $stmt = $this->db->prepare(
            "SELECT idAdministrativo FROM Administrativo WHERE idUsuario = :idUsuario AND estado = 1 LIMIT 1"
        );
        $stmt->execute([':idUsuario' => $idUsuario]);
        $res = $stmt->fetch();
        return $res ? (int) $res['idAdministrativo'] : null;
    }

    /**
     * Obtiene la lista de estudiantes inscritos en un curso y su respectivo
     * estado de asistencia general para una fecha específica.
     */
    public function listarEstudiantesPorCursoYFecha(int $idCurso, string $fecha): array
    {
        $sql = "SELECT i.idInscripcion,
                       e.idEstudiante,
                       e.rude,
                       e.ci,
                       CONCAT(e.apPaterno, ' ', e.apMaterno, ' ', e.nombre) AS estudianteNombre,
                       ag.idAsistenciaGeneral,
                       ag.estado,
                       ag.horaLlegada,
                       ag.observacion,
                       ag.notificado
                FROM Inscripcion i
                INNER JOIN Estudiante e ON e.idEstudiante = i.idEstudiante
                LEFT JOIN asistencia_general ag ON ag.idInscripcion = i.idInscripcion AND ag.fecha = :fecha
                WHERE i.idCurso = :idCurso
                  AND i.estado = 1
                  AND e.estado = 1
                ORDER BY e.apPaterno, e.apMaterno, e.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':idCurso' => $idCurso,
            ':fecha'   => $fecha
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Guarda masivamente la asistencia de un grupo de alumnos.
     * Utiliza una transacción para asegurar la integridad de la operación.
     */
    public function guardarAsistenciaMasiva(array $asistencias, string $fecha, int $idAdministrativo): array
    {
        try {
            $this->db->beginTransaction();

            $sqlDelete = "DELETE FROM asistencia_general WHERE idInscripcion = :idInscripcion AND fecha = :fecha";
            $stmtDelete = $this->db->prepare($sqlDelete);

            $sql = "INSERT INTO asistencia_general (idInscripcion, fecha, estado, horaLlegada, observacion, idAdministrativo)
                    VALUES (:idInscripcion, :fecha, :estado, :horaLlegada, :observacion, :idAdministrativo)
                    ON DUPLICATE KEY UPDATE
                        estado = VALUES(estado),
                        horaLlegada = VALUES(horaLlegada),
                        observacion = VALUES(observacion),
                        idAdministrativo = VALUES(idAdministrativo)";

            $stmt = $this->db->prepare($sql);

            foreach ($asistencias as $idInscripcion => $a) {
                // Preparar datos individuales
                $estado = $a['estado'] ?? ($a['estado_existente'] ?? null);

                // Si no se marcó ningún estado (desmarcado / en blanco), eliminar registro en BD si existía
                if (empty($estado)) {
                    $stmtDelete->execute([
                        ':idInscripcion' => (int) $idInscripcion,
                        ':fecha'          => $fecha
                    ]);
                    continue;
                }

                $horaLlegada = ($estado === 'R' && !empty($a['horaLlegada'])) ? $a['horaLlegada'] : null;
                $observacion = !empty($a['observacion']) ? trim($a['observacion']) : null;

                $stmt->execute([
                    ':idInscripcion'   => (int) $idInscripcion,
                    ':fecha'            => $fecha,
                    ':estado'           => $estado,
                    ':horaLlegada'      => $horaLlegada,
                    ':observacion'      => $observacion,
                    ':idAdministrativo' => $idAdministrativo
                ]);
            }

            $this->db->commit();
            return ['ok' => true, 'mensaje' => 'Asistencias guardadas correctamente.'];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[ASISTENCIA] guardarAsistenciaMasiva: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo guardar el control de asistencia.'];
        }
    }

    /**
     * Consolida la asistencia cargando los registros tomados por los docentes por periodo en la fecha.
     * Mantiene los estados de Retraso (R) y Licencia (L) existentes en la asistencia general.
     */
    public function cargarAsistenciaDesdeDocentes(int $idCurso, string $fecha, int $idAdmin): array
    {
        try {
            $sqlEst = "SELECT i.idInscripcion 
                       FROM Inscripcion i 
                       INNER JOIN Estudiante e ON e.idEstudiante = i.idEstudiante
                       WHERE i.idCurso = :idCurso AND i.estado = 1 AND e.estado = 1";
            $stmtEst = $this->db->prepare($sqlEst);
            $stmtEst->execute([':idCurso' => $idCurso]);
            $estudiantes = $stmtEst->fetchAll(PDO::FETCH_COLUMN);

            if (empty($estudiantes)) {
                return ['ok' => false, 'mensaje' => 'No hay estudiantes inscritos en este curso.'];
            }

            $sqlDoc = "SELECT a.idInscripcion, ea.abreviatura, a.horaAsistencia
                       FROM asistencia a
                       INNER JOIN horario_curso h ON h.idHorarioCurso = a.idHorario
                       INNER JOIN estado_asistencia ea ON ea.idEstadoAsistencia = a.idEstadoAsistencia
                       WHERE h.idCurso = :idCurso
                         AND a.dAsistencia = :fecha
                         AND a.estado = 1";
            $stmtDoc = $this->db->prepare($sqlDoc);
            $stmtDoc->execute([':idCurso' => $idCurso, ':fecha' => $fecha]);
            $registrosDocentes = $stmtDoc->fetchAll();

            if (empty($registrosDocentes)) {
                $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                $ts = strtotime($fecha);
                $nombreDia = $dias[(int) date('w', $ts)] ?? '';
                $diaNum    = date('j', $ts);
                $mesNum    = (int) date('n', $ts);
                $anioNum   = date('Y', $ts);
                $nombreMes = $meses[$mesNum] ?? '';

                $fechaTexto = "{$nombreDia} {$diaNum} de {$nombreMes} de {$anioNum}";

                return [
                    'ok' => false,
                    'tipo' => 'warning_centro',
                    'mensaje' => "Ningún Docente llamó Lista todavía en el día {$fechaTexto}"
                ];
            }

            $asistDocentePorInscripcion = [];
            foreach ($registrosDocentes as $r) {
                $idIns = (int) $r['idInscripcion'];
                $abrev = strtoupper(trim((string)$r['abreviatura']));
                if (!isset($asistDocentePorInscripcion[$idIns])) {
                    $asistDocentePorInscripcion[$idIns] = [];
                }
                $asistDocentePorInscripcion[$idIns][] = [
                    'abrev' => $abrev,
                    'hora'  => $r['horaAsistencia']
                ];
            }

            $sqlGen = "SELECT idInscripcion, estado, horaLlegada 
                       FROM asistencia_general 
                       WHERE fecha = :fecha";
            $stmtGen = $this->db->prepare($sqlGen);
            $stmtGen->execute([':fecha' => $fecha]);
            $asistGeneralesExistentes = [];
            foreach ($stmtGen->fetchAll() as $g) {
                $asistGeneralesExistentes[(int)$g['idInscripcion']] = $g;
            }

            $this->db->beginTransaction();

            $sqlUpsert = "INSERT INTO asistencia_general (idInscripcion, fecha, estado, horaLlegada, idAdministrativo)
                          VALUES (:idInscripcion, :fecha, :estado, :horaLlegada, :idAdmin)
                          ON DUPLICATE KEY UPDATE
                              estado = VALUES(estado),
                              horaLlegada = COALESCE(VALUES(horaLlegada), horaLlegada),
                              idAdministrativo = VALUES(idAdministrativo)";
            $stmtUpsert = $this->db->prepare($sqlUpsert);

            $actualizadosA = 0;
            $actualizadosF = 0;

            foreach ($estudiantes as $idIns) {
                $idIns = (int) $idIns;
                $genExistente = $asistGeneralesExistentes[$idIns] ?? null;
                $estadoActualGen = $genExistente ? $genExistente['estado'] : null;

                if ($estadoActualGen === 'R' || $estadoActualGen === 'L') {
                    continue;
                }

                $periodosDocente = $asistDocentePorInscripcion[$idIns] ?? [];
                $tuvoPresenciaDocente = false;
                $horaPrimeraPresencia = null;

                foreach ($periodosDocente as $p) {
                    if ($p['abrev'] === 'P' || $p['abrev'] === 'T') {
                        $tuvoPresenciaDocente = true;
                        if (!$horaPrimeraPresencia && !empty($p['hora'])) {
                            $horaPrimeraPresencia = substr($p['hora'], 0, 5);
                        }
                    }
                }

                if ($tuvoPresenciaDocente) {
                    $stmtUpsert->execute([
                        ':idInscripcion' => $idIns,
                        ':fecha'          => $fecha,
                        ':estado'         => 'A',
                        ':horaLlegada'    => $horaPrimeraPresencia,
                        ':idAdmin'        => $idAdmin
                    ]);
                    $actualizadosA++;
                } else {
                    $stmtUpsert->execute([
                        ':idInscripcion' => $idIns,
                        ':fecha'          => $fecha,
                        ':estado'         => 'F',
                        ':horaLlegada'    => null,
                        ':idAdmin'        => $idAdmin
                    ]);
                    $actualizadosF++;
                }
            }

            $this->db->commit();

            return [
                'ok' => true, 
                'mensaje' => "Asistencia del curso cargada exitosamente desde los docentes. Se registraron {$actualizadosA} estudiante(s) como Asistió (A) y {$actualizadosF} como Falta (F), manteniendo Retrasos (R) y Licencias (L) previas."
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[ASISTENCIA] cargarAsistenciaDesdeDocentes: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'Error al consolidar asistencia de docentes.'];
        }
    }

    /**
     * Obtiene el estudiante y su inscripción activa en una gestión (año) mediante su código RUDE.
     */
    public function obtenerInscripcionActivaPorRude(string $rude, int $anio): ?array
    {
        $sql = "SELECT i.idInscripcion,
                       c.idCurso,
                       e.idEstudiante,
                       e.rude,
                       e.foto,
                       CONCAT(e.apPaterno, ' ', e.apMaterno, ' ', e.nombre) AS estudianteNombre,
                       c.nombre AS cursoNombre,
                       p.nombre AS paraleloNombre,
                       n.nombre AS nivelNombre,
                       t.nombre AS turnoNombre,
                       g.anio
                FROM Inscripcion i
                INNER JOIN Estudiante e ON e.idEstudiante = i.idEstudiante
                INNER JOIN Curso c      ON c.idCurso      = i.idCurso
                INNER JOIN Paralelo p   ON p.idParalelo   = c.idParalelo
                INNER JOIN Nivel n      ON n.idNivel      = c.idNivel
                INNER JOIN Turno t      ON t.idTurno      = c.idTurno
                INNER JOIN Gestion g    ON g.idGestion    = c.idGestion
                WHERE e.rude = :rude
                  AND e.estado = 1
                  AND i.estado = 1
                  AND g.anio = :anio
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':rude' => $rude,
            ':anio' => $anio
        ]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtiene la asistencia de una inscripción para una fecha específica.
     */
    public function obtenerAsistenciaPorInscripcionYFecha(int $idInscripcion, string $fecha): ?array
    {
        $sql = "SELECT idAsistenciaGeneral, estado, horaLlegada, observacion 
                FROM asistencia_general 
                WHERE idInscripcion = :idInscripcion AND fecha = :fecha 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':idInscripcion' => $idInscripcion,
            ':fecha'         => $fecha
        ]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Registra la asistencia individual de un estudiante (idInscripcion).
     */
    public function registrarAsistenciaIndividual(int $idInscripcion, string $fecha, string $estado, ?string $horaLlegada, int $idAdministrativo): bool
    {
        try {
            $sql = "INSERT INTO asistencia_general (idInscripcion, fecha, estado, horaLlegada, idAdministrativo)
                    VALUES (:idInscripcion, :fecha, :estado, :horaLlegada, :idAdministrativo)
                    ON DUPLICATE KEY UPDATE
                        estado = VALUES(estado),
                        horaLlegada = VALUES(horaLlegada),
                        idAdministrativo = VALUES(idAdministrativo)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':idInscripcion'   => $idInscripcion,
                ':fecha'            => $fecha,
                ':estado'           => $estado,
                ':horaLlegada'      => $horaLlegada,
                ':idAdministrativo' => $idAdministrativo
            ]);
        } catch (Throwable $e) {
            error_log('[ASISTENCIA] registrarAsistenciaIndividual: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca un estudiante en todo el colegio por su RUDE y obtiene su curso actual.
     */
    public function buscarEstudiantePorRudeGeneral(string $rude, int $anio): ?array
    {
        $sql = "SELECT e.idEstudiante,
                       e.rude,
                       CONCAT(e.apPaterno, ' ', e.apMaterno, ' ', e.nombre) AS estudianteNombre,
                       c.nombre AS cursoNombre,
                       p.nombre AS paraleloNombre,
                       n.nombre AS nivelNombre,
                       t.nombre AS turnoNombre,
                       g.anio
                FROM Estudiante e
                LEFT JOIN Inscripcion i ON i.idEstudiante = e.idEstudiante AND i.estado = 1
                LEFT JOIN Curso c       ON c.idCurso      = i.idCurso
                LEFT JOIN Paralelo p   ON p.idParalelo   = c.idParalelo
                LEFT JOIN Nivel n      ON n.idNivel      = c.idNivel
                LEFT JOIN Turno t      ON t.idTurno      = c.idTurno
                LEFT JOIN Gestion g    ON g.idGestion    = c.idGestion AND g.anio = :anio
                WHERE e.rude = :rude
                  AND e.estado = 1
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':rude' => $rude,
            ':anio' => $anio
        ]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Guarda masivamente la asistencia desde la API Flutter (JSON payload).
     */
    public function guardarAsistenciaLoteJson(array $asistencias, string $fecha, int $idAdministrativo, string $curso = ''): array
    {
        try {
            $this->db->beginTransaction();

            $sqlUpsert = "INSERT INTO asistencia_general (idInscripcion, fecha, estado, horaLlegada, observacion, idAdministrativo)
                          VALUES (:idInscripcion, :fecha, :estado, :horaLlegada, :observacion, :idAdministrativo)
                          ON DUPLICATE KEY UPDATE
                              estado = VALUES(estado),
                              horaLlegada = COALESCE(VALUES(horaLlegada), horaLlegada),
                              observacion = VALUES(observacion),
                              idAdministrativo = VALUES(idAdministrativo)";
            $stmtUpsert = $this->db->prepare($sqlUpsert);

            $sqlInsc = "SELECT idInscripcion FROM Inscripcion WHERE idEstudiante = :idEstudiante AND estado = 1 ORDER BY fechaRegistro DESC LIMIT 1";
            $stmtInsc = $this->db->prepare($sqlInsc);

            $guardados = 0;
            $horaActual = date('H:i:s');

            foreach ($asistencias as $keyId => $value) {
                $estado = is_array($value) ? ($value['estado'] ?? '') : (string) $value;
                $estado = strtoupper(trim($estado));

                if (empty($estado)) {
                    continue;
                }

                if ($estado === 'PRESENTE' || $estado === 'ASISTENCIA') $estado = 'A';
                if ($estado === 'ATRASO' || $estado === 'RETRASO') $estado = 'R';
                if ($estado === 'FALTO' || $estado === 'FALTA') $estado = 'F';
                if ($estado === 'LICENCIA') $estado = 'L';

                if (!in_array($estado, ['A', 'R', 'F', 'L'], true)) {
                    continue;
                }

                $idInscripcion = (int) $keyId;

                $stmtChk = $this->db->prepare("SELECT idInscripcion FROM Inscripcion WHERE idInscripcion = :id LIMIT 1");
                $stmtChk->execute([':id' => $idInscripcion]);
                if (!$stmtChk->fetch()) {
                    $stmtInsc->execute([':idEstudiante' => (int) $keyId]);
                    $rowInsc = $stmtInsc->fetch();
                    if ($rowInsc) {
                        $idInscripcion = (int) $rowInsc['idInscripcion'];
                    } else {
                        continue;
                    }
                }

                $horaLlegada = ($estado === 'A' || $estado === 'R') ? $horaActual : null;
                $observacion = is_array($value) ? ($value['observacion'] ?? null) : null;

                $stmtUpsert->execute([
                    ':idInscripcion'   => $idInscripcion,
                    ':fecha'            => $fecha,
                    ':estado'           => $estado,
                    ':horaLlegada'      => $horaLlegada,
                    ':observacion'      => $observacion,
                    ':idAdministrativo' => $idAdministrativo,
                ]);

                $guardados++;
            }

            $this->db->commit();

            return [
                'ok' => true,
                'status' => 'success',
                'mensaje' => "Se guardó correctamente la asistencia de {$guardados} estudiante(s) en la base de datos.",
                'guardados' => $guardados
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[ASISTENCIA API] guardarAsistenciaLoteJson: ' . $e->getMessage());
            return [
                'ok' => false,
                'status' => 'error',
                'mensaje' => 'Error al guardar la asistencia en la base de datos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lista las asistencias tomadas hoy para la API.
     */
    public function listarAsistenciasHoyApi(): array
    {
        $sql = "SELECT ag.idAsistenciaGeneral AS id,
                       e.idEstudiante AS estudiante_id,
                       CONCAT(e.apPaterno, ' ', e.apMaterno, ' ', e.nombre) AS nombre_estudiante,
                       CONCAT(c.nombre, ' ', p.nombre, ' - ', n.nombre) AS curso,
                       CONCAT(ag.fecha, ' ', COALESCE(ag.horaLlegada, '00:00:00')) AS fecha_hora,
                       CASE ag.estado 
                           WHEN 'A' THEN 'Presente' 
                           WHEN 'R' THEN 'Atraso' 
                           WHEN 'F' THEN 'Falta' 
                           WHEN 'L' THEN 'Licencia' 
                           ELSE ag.estado 
                       END AS estado,
                       'Manual' AS metodo
                FROM asistencia_general ag
                INNER JOIN Inscripcion i ON i.idInscripcion = ag.idInscripcion
                INNER JOIN Estudiante e ON e.idEstudiante = i.idEstudiante
                INNER JOIN Curso c ON c.idCurso = i.idCurso
                INNER JOIN Paralelo p ON p.idParalelo = c.idParalelo
                INNER JOIN Nivel n ON n.idNivel = c.idNivel
                WHERE ag.fecha = CURRENT_DATE()
                ORDER BY ag.idAsistenciaGeneral DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene mapa [idEstudiante => estado] guardado para curso y fecha.
     */
    public function obtenerEstadosAsistenciaCursoYFecha(string $cursoNombre, string $fecha): array
    {
        $sql = "SELECT e.idEstudiante, ag.estado
                FROM asistencia_general ag
                INNER JOIN Inscripcion i ON i.idInscripcion = ag.idInscripcion
                INNER JOIN Estudiante e ON e.idEstudiante = i.idEstudiante
                INNER JOIN Curso c ON c.idCurso = i.idCurso
                INNER JOIN Paralelo p ON p.idParalelo = c.idParalelo
                INNER JOIN Nivel n ON n.idNivel = c.idNivel
                WHERE (c.nombre = :c1 OR CONCAT(c.nombre, ' ', n.nombre, ' ', p.nombre) = :c2 OR CONCAT(c.nombre, ' ', p.nombre) = :c3 OR CONCAT(c.nombre, ' ', n.nombre) = :c4)
                  AND ag.fecha = :fecha";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':c1' => $cursoNombre,
            ':c2' => $cursoNombre,
            ':c3' => $cursoNombre,
            ':c4' => $cursoNombre,
            ':fecha' => $fecha
        ]);

        $resultados = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $resultados[(string)$row['idEstudiante']] = (string)$row['estado'];
        }
        return $resultados;
    }
}


