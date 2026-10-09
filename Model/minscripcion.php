<?php
/**
 * ============================================================
 * minscripcion.php  —  Modelo del módulo Inscripción
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Usa los stored procedures:
 *   InscribirEstudiante   -> inscribir (Estudiante + Curso)
 *   EliminarInscripcion   -> eliminar (baja lógica)
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloInscripcion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function listar(string $buscar = '', int $idCurso = 0): array
    {
        $sql = "SELECT i.idInscripcion, i.idEstudiante, i.idCurso, i.fechaRegistro,
                       e.nombre, e.apPaterno, e.apMaterno,
                       CONCAT(e.apPaterno,' ',e.apMaterno,' ',e.nombre) AS estudiante,
                       e.rude, e.ci AS estudianteCi, e.genero, e.fechaNacimiento, e.nacionalidad,
                       COALESCE(NULLIF(TRIM(e.provincia), ''), 'Andrés Ibáñez') AS provNac,
                       COALESCE(NULLIF(TRIM(e.departamento), ''), d.estado, 'Santa Cruz') AS depNac,
                       d.barrio, d.localidad, d.calle, d.numero AS numeroDir, d.estado AS departamentoDir,
                       c.nombre AS cursoGrado, n.nombre AS nivelNombre, t.nombre AS turnoNombre, p.nombre AS paraleloNombre, g.anio AS gestionAnio,
                       CONCAT(c.nombre, ' ', p.nombre, ' de ', n.nombre) AS curso,
                       CONCAT(adm.apPaterno,' ',adm.apMaterno,' ',adm.nombre) AS registrado
                FROM Inscripcion i
                INNER JOIN Estudiante e ON e.idEstudiante = i.idEstudiante
                LEFT JOIN Direccion d   ON d.idDireccion  = e.idDireccion
                INNER JOIN Curso c      ON c.idCurso      = i.idCurso
                INNER JOIN Gestion g    ON g.idGestion    = c.idGestion
                INNER JOIN Nivel n      ON n.idNivel      = c.idNivel
                INNER JOIN Turno t      ON t.idTurno      = c.idTurno
                INNER JOIN Paralelo p   ON p.idParalelo   = c.idParalelo
                LEFT  JOIN Administrativo adm ON adm.idAdministrativo = i.idAdministrativo
                WHERE i.estado = 1";

        $params = [];
        if ($idCurso > 0) {
            $sql .= " AND i.idCurso = :idCurso";
            $params[':idCurso'] = $idCurso;
        }

        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            $esSingleLetter = (mb_strlen(trim($buscar)) === 1 && !is_numeric(trim($buscar)));

            foreach ($palabras as $p) {
                $sub = [];
                $campos = ["e.nombre", "e.apPaterno", "e.apMaterno", "e.ci"];
                if (!$esSingleLetter || preg_match('/[0-9]/', $p)) {
                    $campos[] = "e.rude";
                }

                $esEnne = (mb_strtolower($p) === 'ñ');

                foreach ($campos as $campo) {
                    $k++;
                    $paramKey1 = ":qpk{$k}a";
                    $paramKey2 = ":qpk{$k}b";
                    if ($esEnne) {
                        $sub[] = "(BINARY {$campo} LIKE {$paramKey1} OR BINARY {$campo} LIKE {$paramKey2})";
                    } else {
                        $sub[] = "({$campo} LIKE {$paramKey1} OR {$campo} LIKE {$paramKey2})";
                    }
                    $params[$paramKey1] = $p . '%';
                    $params[$paramKey2] = '% ' . $p . '%';
                }
                $k++;
                $paramKey3 = ":qpk{$k}c";
                $sub[] = ($esEnne ? "BINARY " : "") . "CONCAT_WS(' ', e.apPaterno, e.apMaterno, e.nombre) LIKE {$paramKey3}";
                $params[$paramKey3] = $p . '%';

                $k++;
                $paramKey4 = ":qpk{$k}d";
                $sub[] = ($esEnne ? "BINARY " : "") . "CONCAT_WS(' ', e.nombre, e.apPaterno, e.apMaterno) LIKE {$paramKey4}";
                $params[$paramKey4] = $p . '%';

                $condicionesPalabras[] = "(" . implode(" OR ", $sub) . ")";
            }
            if (!empty($condicionesPalabras)) {
                $sql .= " AND " . implode(" AND ", $condicionesPalabras);
            }
        }

        $sql .= " ORDER BY e.apPaterno, e.apMaterno";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $filas = $stmt->fetchAll();

        foreach ($filas as &$r) {
            $idEst = (int) $r['idEstudiante'];
            $sqlT = "SELECT t.*, t.parentesco AS relParentesco
                     FROM Estudiante_Tutor et
                     INNER JOIN Tutor t ON t.idTutor = et.idTutor
                     WHERE et.idEstudiante = :idEst";
            $stT = $this->db->prepare($sqlT);
            $stT->execute([':idEst' => $idEst]);
            $tutores = $stT->fetchAll();

            $padre = null;
            $madre = null;
            $tutorOtro = null;

            foreach ($tutores as $tut) {
                $parentesco = mb_strtolower(trim($tut['relParentesco'] ?? ''));
                if (str_contains($parentesco, 'padre') || str_contains($parentesco, 'papá') || str_contains($parentesco, 'papa')) {
                    $padre = $tut;
                } elseif (str_contains($parentesco, 'madre') || str_contains($parentesco, 'mamá') || str_contains($parentesco, 'mama')) {
                    $madre = $tut;
                } else {
                    $tutorOtro = $tut;
                }
            }

            if (!$padre && !$madre && !$tutorOtro && !empty($tutores[0])) {
                $tutorOtro = $tutores[0];
            }

            $r['padre'] = $padre;
            $r['madre'] = $madre;
            $r['tutorOtro'] = $tutorOtro;

            // Cargar Salud (Discapacidad, Dificultad, Condición Médica)
            try {
                $stDisc = $this->db->prepare("SELECT d.tipoDiscapacidad FROM Estudiante_Discapacidad ed INNER JOIN Discapacidad d ON d.idDiscapacidad = ed.idDiscapacidad WHERE ed.idEstudiante = :idEst");
                $stDisc->execute([':idEst' => $idEst]);
                $r['discapacidades'] = $stDisc->fetchAll(PDO::FETCH_COLUMN);

                $stDif = $this->db->prepare("SELECT da.tipoDificultad, COALESCE(da.diagnostico, 0) AS diagnostico FROM Estudiante_Dificultad ed INNER JOIN Dificultad_Aprendizaje da ON da.idDificultad = ed.idDificultad WHERE ed.idEstudiante = :idEst");
                $stDif->execute([':idEst' => $idEst]);
                $r['dificultades'] = $stDif->fetchAll(PDO::FETCH_ASSOC);

                $stCond = $this->db->prepare("SELECT cm.condicion FROM Estudiante_Condicion ec INNER JOIN condicion_medica cm ON cm.idCondicion = ec.idCondicion WHERE ec.idEstudiante = :idEst");
                $stCond->execute([':idEst' => $idEst]);
                $r['condiciones'] = $stCond->fetchAll(PDO::FETCH_COLUMN);
            } catch (Throwable $ex) {
                error_log('[MINSCRIPCION] Error al cargar salud (listar): ' . $ex->getMessage());
                $r['discapacidades'] = [];
                $r['dificultades'] = [];
                $r['condiciones'] = [];
            }
        }
        unset($r);

        return $filas;
    }

    /**
     * Obtiene una inscripción por su ID con datos del estudiante y curso.
     */
    public function obtenerPorId(int $idInscripcion): ?array
    {
        $sql = "SELECT i.idInscripcion, i.idEstudiante, i.idCurso, i.fechaRegistro,
                       e.nombre, e.apPaterno, e.apMaterno,
                       CONCAT(e.apPaterno,' ',e.apMaterno,' ',e.nombre) AS estudiante,
                       e.rude, e.ci AS estudianteCi, e.genero, e.fechaNacimiento, e.nacionalidad,
                       COALESCE(NULLIF(TRIM(e.provincia), ''), 'Andrés Ibáñez') AS provNac,
                       COALESCE(NULLIF(TRIM(e.departamento), ''), d.estado, 'Santa Cruz') AS depNac, e.foto,
                       d.barrio, d.localidad, d.calle, d.numero AS numeroDir, d.estado AS departamentoDir,
                       c.nombre AS cursoGrado, n.nombre AS nivelNombre, t2.nombre AS turnoNombre, p.nombre AS paraleloNombre, g.anio AS gestionAnio,
                       CONCAT(c.nombre, ' ', p.nombre, ' de ', n.nombre) AS curso,
                       CONCAT(adm.apPaterno,' ',adm.apMaterno,' ',adm.nombre) AS registrado
                FROM Inscripcion i
                INNER JOIN Estudiante e ON e.idEstudiante = i.idEstudiante
                LEFT JOIN Direccion d   ON d.idDireccion  = e.idDireccion
                INNER JOIN Curso c      ON c.idCurso      = i.idCurso
                INNER JOIN Gestion g    ON g.idGestion    = c.idGestion
                INNER JOIN Nivel n      ON n.idNivel      = c.idNivel
                INNER JOIN Turno t2     ON t2.idTurno     = c.idTurno
                INNER JOIN Paralelo p   ON p.idParalelo   = c.idParalelo
                LEFT  JOIN Administrativo adm ON adm.idAdministrativo = i.idAdministrativo
                WHERE i.idInscripcion = :id AND i.estado = 1 LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idInscripcion]);
        $row = $stmt->fetch();
        if (!$row) return null;

        // Cargar tutores del estudiante por separado
        $idEstudiante = (int) $row['idEstudiante'];
        $sqlTutores = "SELECT t.*, t.parentesco AS relParentesco
                       FROM Estudiante_Tutor et
                       INNER JOIN Tutor t ON t.idTutor = et.idTutor
                       WHERE et.idEstudiante = :idEst";
        $stmtT = $this->db->prepare($sqlTutores);
        $stmtT->execute([':idEst' => $idEstudiante]);
        $tutores = $stmtT->fetchAll();

        $padre = null;
        $madre = null;
        $tutorOtro = null;

        foreach ($tutores as $tut) {
            $parentesco = mb_strtolower(trim($tut['relParentesco'] ?? ''));
            if (str_contains($parentesco, 'padre') || str_contains($parentesco, 'papá') || str_contains($parentesco, 'papa')) {
                $padre = $tut;
            } elseif (str_contains($parentesco, 'madre') || str_contains($parentesco, 'mamá') || str_contains($parentesco, 'mama')) {
                $madre = $tut;
            } else {
                $tutorOtro = $tut;
            }
        }

        if (!$padre && !$madre && !$tutorOtro && !empty($tutores[0])) {
            $tutorOtro = $tutores[0];
        }

        $row['padre'] = $padre;
        $row['madre'] = $madre;
        $row['tutorOtro'] = $tutorOtro;

        $tutPrincipal = $padre ?: ($madre ?: $tutorOtro);
        if ($tutPrincipal) {
            $row['tutorNombre'] = trim(($tutPrincipal['apPaterno'] ?? '') . ' ' . ($tutPrincipal['apMaterno'] ?? '') . ' ' . ($tutPrincipal['nombre'] ?? ''));
            $row['tutorCi'] = $tutPrincipal['ci'] ?? '';
            $row['tutorParentesco'] = $tutPrincipal['relParentesco'] ?? 'Tutor';
            $row['tutorTelefono'] = $tutPrincipal['telefono'] ?? '';
            $row['tutorOcupacion'] = $tutPrincipal['ocupacion'] ?? '';
        }

        // Cargar Salud (Discapacidad, Dificultad, Condición Médica)
        try {
            $stDisc = $this->db->prepare("SELECT d.tipoDiscapacidad FROM Estudiante_Discapacidad ed INNER JOIN Discapacidad d ON d.idDiscapacidad = ed.idDiscapacidad WHERE ed.idEstudiante = :idEst");
            $stDisc->execute([':idEst' => $idEstudiante]);
            $row['discapacidades'] = $stDisc->fetchAll(PDO::FETCH_COLUMN);

            $stDif = $this->db->prepare("SELECT da.tipoDificultad, COALESCE(da.diagnostico, 0) AS diagnostico FROM Estudiante_Dificultad ed INNER JOIN Dificultad_Aprendizaje da ON da.idDificultad = ed.idDificultad WHERE ed.idEstudiante = :idEst");
            $stDif->execute([':idEst' => $idEstudiante]);
            $row['dificultades'] = $stDif->fetchAll(PDO::FETCH_ASSOC);

            $stCond = $this->db->prepare("SELECT cm.condicion FROM Estudiante_Condicion ec INNER JOIN condicion_medica cm ON cm.idCondicion = ec.idCondicion WHERE ec.idEstudiante = :idEst");
            $stCond->execute([':idEst' => $idEstudiante]);
            $row['condiciones'] = $stCond->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $ex) {
            error_log('[MINSCRIPCION] Error al cargar salud (obtenerPorId): ' . $ex->getMessage());
            $row['discapacidades'] = [];
            $row['dificultades'] = [];
            $row['condiciones'] = [];
        }

        return $row;
    }

    /**
     * Estudiantes activos (para el <select>).
     */
    public function listarEstudiantes(): array
    {
        $sql = "SELECT idEstudiante,
                       CONCAT(apPaterno,' ',apMaterno,' ',nombre) AS nombre,
                       rude, ci
                FROM Estudiante WHERE estado = 1
                ORDER BY apPaterno, apMaterno";
        return $this->db->query($sql)->fetchAll();
    }

    public function listarCursos(): array
    {
        $sql = "SELECT c.idCurso,
                       c.nombre AS cursoNombre,
                       c.idNivel, n.nombre AS nivelNombre,
                       c.idTurno, t.nombre AS turnoNombre,
                       c.idParalelo, p.nombre AS paraleloNombre,
                       c.idGestion, g.anio,
                       CONCAT(n.nombre,' ',p.nombre,' - ',t.nombre,' (',g.anio,')') AS descripcion
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
     * Devuelve el idAdministrativo vinculado a un usuario (o 0 si no hay).
     */
    public function idAdministrativoDeUsuario(int $idUsuario): int
    {
        $stmt = $this->db->prepare(
            "SELECT idAdministrativo FROM Administrativo WHERE idUsuario = :id AND estado = 1 LIMIT 1"
        );
        $stmt->execute([':id' => $idUsuario]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    // =========================================================
    // INSCRIBIR  ->  InscribirEstudiante
    // =========================================================
    public function inscribir(int $idEstudiante, int $idCurso, int $idAdministrativo): array
    {
        try {
            $stmt = $this->db->prepare(
                "CALL InscribirEstudiante(:est, :curso, :adm, @idInscripcion, @mensaje)"
            );
            $stmt->execute([':est' => $idEstudiante, ':curso' => $idCurso, ':adm' => $idAdministrativo]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @idInscripcion AS id, @mensaje AS mensaje")->fetch();
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok = str_starts_with($mensaje, 'OK');

            return [
                'ok'      => $ok,
                'mensaje' => $ok ? 'Estudiante inscrito correctamente.' : ($mensaje ?: 'No se pudo inscribir.'),
            ];

        } catch (Throwable $e) {
            error_log('[INSCRIPCION] inscribir: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo inscribir al estudiante.'];
        }
    }

    // =========================================================
    // ELIMINAR (baja lógica)  ->  EliminarInscripcion
    // =========================================================
    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL EliminarInscripcion(:id, @mensaje)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @mensaje AS mensaje")->fetch();
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok = str_starts_with($mensaje, 'OK');

            return [
                'ok'      => $ok,
                'mensaje' => $ok ? 'Inscripción eliminada correctamente.' : ($mensaje ?: 'No se pudo eliminar.'),
            ];

        } catch (Throwable $e) {
            error_log('[INSCRIPCION] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar la inscripción.'];
        }
    }
}
