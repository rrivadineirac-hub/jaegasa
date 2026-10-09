<?php
/**
 * ============================================================
 * mestudiante.php  —  Modelo del CRUD Estudiante
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Usa los stored procedures de la base de datos:
 *   InsertarEstudiante  -> crear (Dirección + Tutor + Estudiante)
 *   EditarEstudiante    -> editar (Estudiante + Dirección)
 *   EliminarEstudiante  -> eliminar (baja lógica)
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloEstudiante
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Lista los estudiantes activos (con filtro de búsqueda opcional).
     */
    public function listar(string $buscar = '', string $grado = '', string $paralelo = '', string $nivel = '', string $turno = ''): array
    {
        $sql = "SELECT e.idEstudiante, e.rude, e.apPaterno, e.apMaterno, e.nombre,
                       e.ci, e.genero, e.fechaNacimiento, e.foto,
                       e.nacionalidad, e.qr, e.fechaRegistro,
                       (SELECT CONCAT(t.apPaterno,' ',t.apMaterno,' ',t.nombre)
                          FROM Estudiante_Tutor et
                          INNER JOIN Tutor t ON t.idTutor = et.idTutor
                         WHERE et.idEstudiante = e.idEstudiante LIMIT 1) AS tutor,
                       ins.cursoNombre, ins.cursoNivel, ins.cursoParalelo, ins.cursoTurno, ins.cursoAnio
                FROM Estudiante e
                LEFT JOIN (
                    SELECT x.idEstudiante, c.nombre AS cursoNombre, n.nombre AS cursoNivel,
                           p.nombre AS cursoParalelo, t.nombre AS cursoTurno, g.anio AS cursoAnio,
                           c.idParalelo, c.idNivel, c.idTurno
                    FROM Inscripcion x
                    INNER JOIN Curso c    ON c.idCurso    = x.idCurso
                    INNER JOIN Nivel n    ON n.idNivel    = c.idNivel
                    INNER JOIN Paralelo p ON p.idParalelo = c.idParalelo
                    INNER JOIN Turno t    ON t.idTurno    = c.idTurno
                    INNER JOIN Gestion g  ON g.idGestion  = c.idGestion
                    WHERE x.estado = 1
                      AND x.idInscripcion = (
                          SELECT x2.idInscripcion FROM Inscripcion x2
                          WHERE x2.idEstudiante = x.idEstudiante AND x2.estado = 1
                          ORDER BY x2.fechaRegistro DESC LIMIT 1)
                ) ins ON ins.idEstudiante = e.idEstudiante
                WHERE e.estado = 1";

        $params = [];
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


        if ($grado !== '') {
            $mapGrados = [
                '1ro' => 'Primero', '1°' => 'Primero', '1' => 'Primero',
                '2do' => 'Segundo', '2°' => 'Segundo', '2' => 'Segundo',
                '3ro' => 'Tercero', '3°' => 'Tercero', '3' => 'Tercero',
                '4to' => 'Cuarto',  '4°' => 'Cuarto',  '4' => 'Cuarto',
                '5to' => 'Quinto',  '5°' => 'Quinto',  '5' => 'Quinto',
                '6to' => 'Sexto',   '6°' => 'Sexto',   '6' => 'Sexto',
            ];
            $gradoNormalizado = $grado;
            foreach ($mapGrados as $key => $val) {
                if (mb_stripos($gradoNormalizado, $key) === 0 || str_contains($gradoNormalizado, $key . ' ')) {
                    $gradoNormalizado = preg_replace('/\b' . preg_quote($key, '/') . '\b/ui', $val, $gradoNormalizado);
                    break;
                }
            }

            $sql .= " AND (
                ins.cursoNombre = :grado
                OR ins.cursoNombre = :grado_norm
                OR CONCAT_WS(' ', ins.cursoNombre, ins.cursoNivel, ins.cursoParalelo) = :grado_full
                OR CONCAT_WS(' ', ins.cursoNombre, ins.cursoNivel, ins.cursoParalelo) = :grado_full_norm
                OR CONCAT_WS(' ', ins.cursoNombre, ins.cursoParalelo) = :grado_paralelo
                OR CONCAT_WS(' ', ins.cursoNombre, ins.cursoParalelo) = :grado_paralelo_norm
            )";
            $params[':grado']               = $grado;
            $params[':grado_norm']          = $gradoNormalizado;
            $params[':grado_full']          = $grado;
            $params[':grado_full_norm']     = $gradoNormalizado;
            $params[':grado_paralelo']      = $grado;
            $params[':grado_paralelo_norm'] = $gradoNormalizado;
        }
        if ($paralelo !== '') {
            $sql .= " AND (ins.idParalelo = :paralelo_id OR ins.cursoParalelo = :paralelo_str)";
            $params[':paralelo_id']  = (int) $paralelo;
            $params[':paralelo_str'] = $paralelo;
        }
        if ($nivel !== '') {
            $sql .= " AND (ins.idNivel = :nivel_id OR ins.cursoNivel = :nivel_str)";
            $params[':nivel_id']  = (int) $nivel;
            $params[':nivel_str'] = $nivel;
        }
        if ($turno !== '') {
            $sql .= " AND (ins.idTurno = :turno_id OR ins.cursoTurno = :turno_str)";
            $params[':turno_id']  = (int) $turno;
            $params[':turno_str'] = $turno;
        }

        $sql .= " ORDER BY e.apPaterno, e.apMaterno";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un estudiante por su id (con dirección y tutor principal).
     */
    public function obtener(int $id): ?array
    {
        $sql = "SELECT e.*,
                       COALESCE(e.departamento, d.estado) AS departamento,
                       e.provincia,
                       e.nacionalidad,
                       d.barrio, d.calle, d.numero AS numeroDir, d.ubicacion_gps,
                       d.estado AS estadoDir, d.estado AS departamentoDir,
                       t.idTutor,
                       CONCAT(t.apPaterno,' ',t.apMaterno,' ',t.nombre) AS tutorNombre,
                       t.telefono AS tutorTelefono, t.parentesco AS tutorParentesco
                FROM Estudiante e
                LEFT JOIN Direccion d ON d.idDireccion = e.idDireccion
                LEFT JOIN Estudiante_Tutor et ON et.idEstudiante = e.idEstudiante
                LEFT JOIN Tutor t ON t.idTutor = et.idTutor
                WHERE e.idEstudiante = :id AND e.estado = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Verifica si un CI ya está en uso (excluye un id opcional).
     */
    public function ciEnUso(string $ci, int $exceptoId = 0): bool
    {
        if ($ci === '') return false;
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM Estudiante
             WHERE ci = :ci AND estado = 1 AND idEstudiante <> :id"
        );
        $stmt->execute([':ci' => $ci, ':id' => $exceptoId]);
        return (int) ($stmt->fetch()['n'] ?? 0) > 0;
    }

    /**
     * Verifica si un RUDE ya está en uso (excluye un id opcional).
     */
    public function rudeEnUso(string $rude, int $exceptoId = 0): bool
    {
        if ($rude === '') return false;
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM Estudiante
             WHERE rude = :r AND estado = 1 AND idEstudiante <> :id"
        );
        $stmt->execute([':r' => $rude, ':id' => $exceptoId]);
        return (int) ($stmt->fetch()['n'] ?? 0) > 0;
    }

    // =========================================================
    // CREAR  ->  InsertarEstudiante
    // =========================================================
    public function crear(array $d): array
    {
        try {
            $sql = "CALL InsertarEstudiante(
                        :barrio, :localidad, :calle, :numero, :ubicacionGps,
                        :rude, :apPaterno, :apMaterno, :nombre, :genero, :ci, :fechaNac,
                        :provincia, :departamento, :nacionalidad, :qr,
                        :tutorApPaterno, :tutorApMaterno, :tutorNombre, :tutorCi,
                        :tutorTelefono, :tutorCorreo, :tutorParentesco, :tutorTipo,
                        @idEstudiante, @idTutor, @mensaje
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':barrio'          => $d['barrio']       ?: null,
                ':localidad'       => $d['localidad']    ?: null,
                ':calle'           => $d['calle']        ?: null,
                ':numero'          => $d['numero']       ?: null,
                ':ubicacionGps'    => $d['ubicacionGps'] ?: null,
                ':rude'            => $d['rude']         ?: null,
                ':apPaterno'       => $d['apPaterno'],
                ':apMaterno'       => $d['apMaterno'],
                ':nombre'          => $d['nombre'],
                ':genero'          => $d['genero']       ?: null,
                ':ci'              => $d['ci']           ?: null,
                ':fechaNac'        => $d['fechaNac']     ?: null,
                ':provincia'       => $d['provincia']    ?: null,
                ':departamento'    => $d['departamento'] ?: null,
                ':nacionalidad'    => $d['nacionalidad'] ?: null,
                ':qr'              => $d['qr']           ?: null,
                ':tutorApPaterno'  => $d['tutorApPaterno'],
                ':tutorApMaterno'  => $d['tutorApMaterno'],
                ':tutorNombre'     => $d['tutorNombre'],
                ':tutorCi'         => $d['tutorCi']         ?: null,
                ':tutorTelefono'   => $d['tutorTelefono']   ?: null,
                ':tutorCorreo'     => $d['tutorCorreo']     ?: null,
                ':tutorParentesco' => $d['tutorParentesco'] ?: null,
                ':tutorTipo'       => $d['tutorTipo']       ?: null,
            ]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @idEstudiante AS id, @mensaje AS mensaje")->fetch();
            $id      = (int) ($out['id'] ?? 0);
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok      = $id > 0;

            // Si se subió foto, guardarla en el registro recién creado
            if ($ok && !empty($d['foto'])) {
                $this->db->prepare("UPDATE Estudiante SET foto = :f WHERE idEstudiante = :id")
                         ->execute([':f' => $d['foto'], ':id' => $id]);
            }

            return [
                'ok'      => $ok,
                'id'      => $id,
                'mensaje' => $ok ? 'Estudiante registrado correctamente.'
                                 : ($mensaje ?: 'No se pudo registrar el estudiante.'),
            ];

        } catch (Throwable $e) {
            error_log('[ESTUDIANTE] crear: ' . $e->getMessage());
            return ['ok' => false, 'id' => 0, 'mensaje' => 'No se pudo registrar. Verifique que el CI/RUDE no estén duplicados.'];
        }
    }

    // =========================================================
    // EDITAR  ->  EditarEstudiante
    // =========================================================
    public function actualizar(int $id, array $d): array
    {
        $actual = $this->obtener($id);
        if ($actual === null) {
            return ['ok' => false, 'mensaje' => 'El estudiante no existe.'];
        }

        try {
            $sql = "CALL EditarEstudiante(
                        :idEstudiante,
                        :barrio, :localidad, :calle, :numero, :ubicacionGps,
                        :rude, :apPaterno, :apMaterno, :nombre, :genero, :ci, :fechaNac,
                        :provincia, :departamento, :nacionalidad, :qr,
                        @mensaje
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':idEstudiante' => $id,
                ':barrio'       => $d['barrio']       ?: null,
                ':localidad'    => $d['localidad']    ?: null,
                ':calle'        => $d['calle']        ?: null,
                ':numero'       => $d['numero']       ?: null,
                ':ubicacionGps' => $d['ubicacionGps'] ?: null,
                ':rude'         => $d['rude']         ?: null,
                ':apPaterno'    => $d['apPaterno'],
                ':apMaterno'    => $d['apMaterno'],
                ':nombre'       => $d['nombre'],
                ':genero'       => $d['genero']       ?: null,
                ':ci'           => $d['ci']           ?: null,
                ':fechaNac'     => $d['fechaNac']     ?: null,
                ':provincia'    => $d['provincia']    ?: null,
                ':departamento' => $d['departamento'] ?: null,
                ':nacionalidad' => $d['nacionalidad'] ?: null,
                ':qr'           => $d['qr']           ?: null,
            ]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @mensaje AS mensaje")->fetch();
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok = str_starts_with($mensaje, 'OK');

            // Foto: si se subió una nueva, actualizarla y borrar la anterior
            if ($ok && !empty($d['foto'])) {
                $fotoVieja = $actual['foto'] ?? '';
                if ($fotoVieja !== '' && $fotoVieja !== 'default.png' && defined('BASE_PATH')) {
                    $rutaVieja = BASE_PATH . '/inagenes/Estudiante/' . $fotoVieja;
                    if (is_file($rutaVieja)) {
                        @unlink($rutaVieja);
                    }
                }
                $this->db->prepare("UPDATE Estudiante SET foto = :f WHERE idEstudiante = :id")
                         ->execute([':f' => $d['foto'], ':id' => $id]);
            }

            return [
                'ok'      => $ok,
                'mensaje' => $ok ? 'Estudiante actualizado correctamente.' : ($mensaje ?: 'No se pudo actualizar.'),
            ];

        } catch (Throwable $e) {
            error_log('[ESTUDIANTE] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar. Verifique los datos (CI/RUDE duplicado).'];
        }
    }

    // =========================================================
    // ELIMINAR (baja lógica)  ->  EliminarEstudiante
    // =========================================================
    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL EliminarEstudiante(:id, @mensaje)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @mensaje AS mensaje")->fetch();
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok = str_starts_with($mensaje, 'OK');

            return [
                'ok'      => $ok,
                'mensaje' => $ok ? 'Estudiante eliminado correctamente.' : ($mensaje ?: 'No se pudo eliminar.'),
            ];

        } catch (Throwable $e) {
            error_log('[ESTUDIANTE] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar el estudiante.'];
        }
    }

    // =========================================================
    // CATÁLOGOS DE SALUD (para el paso 2 del wizard)
    // =========================================================
    public function listarDiscapacidades(): array
    {
        $sql = "SELECT idDiscapacidad, tipoDiscapacidad
                FROM Discapacidad WHERE estado = 1
                ORDER BY tipoDiscapacidad";
        return $this->db->query($sql)->fetchAll();
    }

    public function listarDificultades(): array
    {
        $sql = "SELECT idDificultad, tipoDificultad, gado
                FROM Dificultad_Aprendizaje WHERE estado = 1
                ORDER BY tipoDificultad";
        return $this->db->query($sql)->fetchAll();
    }

    public function listarCondiciones(): array
    {
        $sql = "SELECT idCondicion, condicion
                FROM Condicion_Medica WHERE estado = 1
                ORDER BY condicion";
        return $this->db->query($sql)->fetchAll();
    }

    // =========================================================
    // GUARDAR RELACIONES DE SALUD (paso 2)
    //   Inserta en las tablas puente. Best-effort: si falla una,
    //   se registra en el log pero no rompe el alta del estudiante.
    // =========================================================
    public function guardarSalud(int $idEstudiante, array $discapacidades, array $dificultades, array $condiciones, array $extra = []): void
    {
        $disOtro     = trim((string) ($extra['discapacidadOtro'] ?? ''));
        $difOtro     = trim((string) ($extra['dificultadOtro'] ?? ''));
        $diagnostico = !empty($extra['diagnostico']) ? 1 : 0;

        // "Otros": crea (o reutiliza) la entrada de catálogo y agrega su id
        if ($disOtro !== '') {
            $idNuevo = $this->obtenerOCrearCatalogo('Discapacidad', 'idDiscapacidad', 'tipoDiscapacidad', $disOtro);
            if ($idNuevo > 0) $discapacidades[] = $idNuevo;
        }
        if ($difOtro !== '') {
            $idNuevo = $this->obtenerOCrearCatalogo('Dificultad_Aprendizaje', 'idDificultad', 'tipoDificultad', $difOtro);
            if ($idNuevo > 0) $dificultades[] = $idNuevo;
        }

        // Discapacidad y Condición (relaciones simples)
        $this->insertarRelacionesSimple('Estudiante_Discapacidad', 'idDiscapacidad', $idEstudiante, $discapacidades);
        $this->insertarRelacionesSimple('Estudiante_Condicion',    'idCondicion',    $idEstudiante, $condiciones);

        // Dificultad de aprendizaje (con indicador de diagnóstico)
        foreach (array_unique(array_map('intval', $dificultades)) as $idDif) {
            if ($idDif <= 0) continue;
            try {
                $this->db->prepare(
                    "INSERT INTO Estudiante_Dificultad (idEstudiante, idDificultad, diagnostico)
                     VALUES (:est, :dif, :diag)
                     ON DUPLICATE KEY UPDATE diagnostico = VALUES(diagnostico)"
                )->execute([':est' => $idEstudiante, ':dif' => $idDif, ':diag' => $diagnostico]);
            } catch (Throwable $e) {
                // Respaldo si la columna diagnostico aún no existe en la BD
                try {
                    $this->db->prepare(
                        "INSERT IGNORE INTO Estudiante_Dificultad (idEstudiante, idDificultad) VALUES (:est, :dif)"
                    )->execute([':est' => $idEstudiante, ':dif' => $idDif]);
                } catch (Throwable $e2) {
                    error_log('[ESTUDIANTE] guardarSalud (dificultad): ' . $e2->getMessage());
                }
            }
        }
    }

    /**
     * Inserta relaciones simples (idEstudiante + idCatalogo) ignorando duplicados.
     */
    private function insertarRelacionesSimple(string $tabla, string $col, int $idEst, array $ids): void
    {
        $stmt = $this->db->prepare("INSERT IGNORE INTO {$tabla} (idEstudiante, {$col}) VALUES (:est, :cat)");
        foreach (array_unique(array_map('intval', $ids)) as $idCat) {
            if ($idCat <= 0) continue;
            try {
                $stmt->execute([':est' => $idEst, ':cat' => $idCat]);
            } catch (Throwable $e) {
                error_log("[ESTUDIANTE] insertarRelacionesSimple ({$tabla}): " . $e->getMessage());
            }
        }
    }

    /**
     * Devuelve el id de una entrada de catálogo por su nombre; si no existe la crea.
     */
    private function obtenerOCrearCatalogo(string $tabla, string $idCol, string $nombreCol, string $valor): int
    {
        try {
            $q = $this->db->prepare("SELECT {$idCol} FROM {$tabla} WHERE {$nombreCol} = :v AND estado = 1 LIMIT 1");
            $q->execute([':v' => $valor]);
            $id = (int) ($q->fetchColumn() ?: 0);
            if ($id > 0) return $id;

            $this->db->prepare("INSERT INTO {$tabla} ({$nombreCol}) VALUES (:v)")->execute([':v' => $valor]);
            return (int) $this->db->lastInsertId();
        } catch (Throwable $e) {
            error_log("[ESTUDIANTE] obtenerOCrearCatalogo ({$tabla}): " . $e->getMessage());
            return 0;
        }
    }

    // =========================================================
    // GUARDAR TUTORES ADICIONALES (paso 3)
    //   Cada tutor: [apPaterno, apMaterno, nombre, ci, telefono,
    //                correo, parentesco, tipo]
    //   Busca por CI (si existe) o crea, y lo vincula al estudiante.
    // =========================================================
    public function guardarTutores(int $idEstudiante, array $tutores): void
    {
        foreach ($tutores as $t) {
            $apPaterno = trim($t['apPaterno'] ?? '');
            $nombre    = trim($t['nombre'] ?? '');
            // Bloque vacío -> se ignora
            if ($apPaterno === '' && $nombre === '') continue;

            try {
                $idTutor = 0;
                $ci = trim($t['ci'] ?? '');

                if ($ci !== '') {
                    $q = $this->db->prepare("SELECT idTutor FROM Tutor WHERE ci = :ci AND estado = 1 LIMIT 1");
                    $q->execute([':ci' => $ci]);
                    $idTutor = (int) ($q->fetchColumn() ?: 0);
                }

                if ($idTutor <= 0) {
                    $ins = $this->db->prepare(
                        "INSERT INTO Tutor (apPaterno, apMaterno, nombre, ci, telefono, correo, parentesco, tipo)
                         VALUES (:ap, :am, :no, :ci, :tel, :co, :pa, :ti)"
                    );
                    $ins->execute([
                        ':ap' => $apPaterno,
                        ':am' => trim($t['apMaterno'] ?? ''),
                        ':no' => $nombre,
                        ':ci' => $ci ?: null,
                        ':tel' => trim($t['telefono'] ?? '') ?: null,
                        ':co' => trim($t['correo'] ?? '') ?: null,
                        ':pa' => trim($t['parentesco'] ?? '') ?: null,
                        ':ti' => trim($t['tipo'] ?? '') ?: null,
                    ]);
                    $idTutor = (int) $this->db->lastInsertId();
                }

                if ($idTutor > 0) {
                    $this->db->prepare(
                        "INSERT IGNORE INTO Estudiante_Tutor (idEstudiante, idTutor) VALUES (:est, :tut)"
                    )->execute([':est' => $idEstudiante, ':tut' => $idTutor]);
                }

            } catch (Throwable $e) {
                error_log('[ESTUDIANTE] guardarTutores: ' . $e->getMessage());
            }
        }
    }

    // =========================================================
    // LEER RELACIONES DE SALUD DE UN ESTUDIANTE (para editar)
    //   Devuelve arrays de ids ya marcados.
    // =========================================================
    public function saludDeEstudiante(int $id): array
    {
        $ids = function (string $sql) use ($id): array {
            $s = $this->db->prepare($sql);
            $s->execute([':id' => $id]);
            return array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
        };

        // ¿Alguna dificultad marcada con diagnóstico? (tolerante si la columna no existe)
        $diagnostico = false;
        try {
            $s = $this->db->prepare(
                "SELECT COALESCE(MAX(diagnostico), 0) FROM Estudiante_Dificultad WHERE idEstudiante = :id"
            );
            $s->execute([':id' => $id]);
            $diagnostico = ((int) $s->fetchColumn()) === 1;
        } catch (Throwable $e) {
            // La columna diagnostico aún no existe: se ignora
        }

        return [
            'discapacidades' => $ids("SELECT idDiscapacidad FROM Estudiante_Discapacidad WHERE idEstudiante = :id"),
            'dificultades'   => $ids("SELECT idDificultad   FROM Estudiante_Dificultad   WHERE idEstudiante = :id"),
            'condiciones'    => $ids("SELECT idCondicion    FROM Estudiante_Condicion    WHERE idEstudiante = :id"),
            'diagnostico'    => $diagnostico,
        ];
    }

    // =========================================================
    // LEER TUTORES DE UN ESTUDIANTE (para editar)
    // =========================================================
    public function tutoresDeEstudiante(int $id): array
    {
        $sql = "SELECT t.idTutor, t.apPaterno, t.apMaterno, t.nombre, t.ci,
                       t.telefono, t.correo, t.parentesco, t.tipo
                FROM Estudiante_Tutor et
                INNER JOIN Tutor t ON t.idTutor = et.idTutor
                WHERE et.idEstudiante = :id AND t.estado = 1
                ORDER BY t.idTutor";
        $s = $this->db->prepare($sql);
        $s->execute([':id' => $id]);
        return $s->fetchAll();
    }

    // =========================================================
    // REEMPLAZAR RELACIONES DE SALUD (al editar)
    //   Borra las actuales y vuelve a insertar las marcadas.
    // =========================================================
    public function reemplazarSalud(int $idEstudiante, array $discapacidades, array $dificultades, array $condiciones, array $extra = []): void
    {
        foreach (['Estudiante_Discapacidad', 'Estudiante_Dificultad', 'Estudiante_Condicion'] as $tabla) {
            try {
                $this->db->prepare("DELETE FROM {$tabla} WHERE idEstudiante = :id")
                         ->execute([':id' => $idEstudiante]);
            } catch (Throwable $e) {
                error_log("[ESTUDIANTE] reemplazarSalud ({$tabla}): " . $e->getMessage());
            }
        }
        $this->guardarSalud($idEstudiante, $discapacidades, $dificultades, $condiciones, $extra);
    }

    // =========================================================
    // REEMPLAZAR TUTORES (al editar)
    //   Quita los vínculos actuales (sin borrar los Tutor, que
    //   pueden ser compartidos) y vuelve a vincular los enviados.
    // =========================================================
    public function reemplazarTutores(int $idEstudiante, array $tutores): void
    {
        try {
            $this->db->prepare("DELETE FROM Estudiante_Tutor WHERE idEstudiante = :id")
                     ->execute([':id' => $idEstudiante]);
        } catch (Throwable $e) {
            error_log('[ESTUDIANTE] reemplazarTutores (delete): ' . $e->getMessage());
        }
        $this->guardarTutores($idEstudiante, $tutores);
    }

    // =========================================================
    // CURSO / INSCRIPCIÓN (para editar el curso del estudiante)
    // =========================================================

    /**
     * Cursos activos con descripción legible (para el <select> combinado).
     * Se mantiene por compatibilidad, pero la vista actual usa
     * listarCursosDetallado() para los selects separados de
     * Nivel / Turno / Paralelo.
     */
    public function listarCursos(): array
    {
        $sql = "SELECT c.idCurso,
                       CONCAT(IF(c.nombre IS NULL OR c.nombre = '', '', CONCAT(c.nombre, ' · ')),
                              n.nombre,' ',p.nombre,' - ',t.nombre,' (',g.anio,')') AS descripcion
                FROM Curso c
                INNER JOIN Gestion g  ON g.idGestion  = c.idGestion
                INNER JOIN Nivel n    ON n.idNivel    = c.idNivel
                INNER JOIN Turno t    ON t.idTurno    = c.idTurno
                INNER JOIN Paralelo p ON p.idParalelo = c.idParalelo
                WHERE c.estado = 1
                ORDER BY g.anio DESC, n.nombre, p.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Cursos activos con sus atributos separados (idNivel/nivelNombre,
     * idTurno/turnoNombre, idParalelo/paraleloNombre, idGestion/anio),
     * para armar en la vista los <select> independientes de
     * Nivel / Turno / Paralelo y resolver el idCurso real en el navegador.
     */
    public function listarCursosDetallado(): array
    {
        $sql = "SELECT c.idCurso,
                       c.nombre AS cursoNombre,
                       c.idNivel, n.nombre AS nivelNombre,
                       c.idTurno, t.nombre AS turnoNombre,
                       c.idParalelo, p.nombre AS paraleloNombre,
                       c.idGestion, g.anio,
                       COALESCE(c.cupo, 36) AS cupo,
                       (SELECT COUNT(*) FROM Inscripcion i WHERE i.idCurso = c.idCurso AND i.estado = 1) AS inscritos
                FROM Curso c
                INNER JOIN Nivel n    ON n.idNivel    = c.idNivel
                INNER JOIN Turno t    ON t.idTurno    = c.idTurno
                INNER JOIN Paralelo p ON p.idParalelo = c.idParalelo
                INNER JOIN Gestion g  ON g.idGestion  = c.idGestion
                WHERE c.estado = 1
                ORDER BY g.anio DESC, n.nombre, t.nombre, p.nombre";

        $rows = $this->db->query($sql)->fetchAll();
        foreach ($rows as &$r) {
            $r['idCurso']    = (int) $r['idCurso'];
            $r['idNivel']    = (int) $r['idNivel'];
            $r['idTurno']    = (int) $r['idTurno'];
            $r['idParalelo'] = (int) $r['idParalelo'];
            $r['idGestion']  = (int) $r['idGestion'];
            $r['anio']       = (string) $r['anio'];
            $r['cupo']       = (int) ($r['cupo'] ?? 36);
            $r['inscritos']  = (int) ($r['inscritos'] ?? 0);
        }
        return $rows;
    }

    /**
     * Verifica si un curso tiene cupos disponibles.
     */
    public function hayCupoDisponible(int $idCurso): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(c.cupo, 36) AS cupo,
                    (SELECT COUNT(*) FROM Inscripcion i WHERE i.idCurso = c.idCurso AND i.estado = 1) AS inscritos
             FROM Curso c
             WHERE c.idCurso = :id AND c.estado = 1"
        );
        $stmt->execute([':id' => $idCurso]);
        $row = $stmt->fetch();
        if (!$row) return false;
        return ((int) $row['inscritos']) < ((int) $row['cupo']);
    }

    /**
     * Año de la gestión activa: la más reciente marcada con estado = 1.
     * Si ninguna gestión está marcada como activa, se usa la más
     * reciente que exista (para no dejar el sistema sin opciones).
     */
    public function obtenerGestionActivaAnio(): string
    {
        $anio = $this->db->query(
            "SELECT anio FROM Gestion WHERE estado = 1 ORDER BY anio DESC LIMIT 1"
        )->fetchColumn();

        if ($anio === false) {
            $anio = $this->db->query(
                "SELECT anio FROM Gestion ORDER BY anio DESC LIMIT 1"
            )->fetchColumn();
        }

        return (string) ($anio ?: date('Y'));
    }

    /**
     * Cursos activos con sus atributos separados, para la cascada
     * Curso(nombre) -> Paralelo -> Nivel -> Turno.
     * (Se mantiene por compatibilidad con otras vistas que la usen.)
     */
    public function listarCursosParaSelect(): array
{
    $sql = "SELECT c.idCurso AS id,
                   COALESCE(NULLIF(c.nombre,''),'') AS grado,
                   c.idNivel, n.nombre AS nivel,
                   c.idParalelo, p.nombre AS paralelo,
                   c.idTurno, t.nombre AS turno,
                   c.idGestion, g.anio
            FROM Curso c
            INNER JOIN Nivel n    ON n.idNivel    = c.idNivel
            INNER JOIN Paralelo p ON p.idParalelo = c.idParalelo
            INNER JOIN Turno t    ON t.idTurno    = c.idTurno
            INNER JOIN Gestion g  ON g.idGestion  = c.idGestion
            WHERE c.estado = 1
            ORDER BY g.anio DESC, n.nombre, t.nombre, p.nombre";

    $rows = $this->db->query($sql)->fetchAll();
    foreach ($rows as &$r) {
        $r['id']         = (int) $r['id'];
        $r['idNivel']    = (int) $r['idNivel'];
        $r['idParalelo'] = (int) $r['idParalelo'];
        $r['idTurno']    = (int) $r['idTurno'];
        $r['idGestion']  = (int) $r['idGestion'];
        $r['anio']       = (string) $r['anio'];
    }
    return $rows;
}

    /**
     * idCurso de la inscripción activa más reciente del estudiante (0 si ninguna).
     */
    public function cursoActualDe(int $idEstudiante): int
    {
        $stmt = $this->db->prepare(
            "SELECT idCurso FROM Inscripcion
             WHERE idEstudiante = :id AND estado = 1
             ORDER BY fechaRegistro DESC LIMIT 1"
        );
        $stmt->execute([':id' => $idEstudiante]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function cursoActualDetalle(int $idEstudiante): ?array
    {
        $sql = "SELECT c.idCurso, c.nombre AS cursoNombre,
                       c.idGestion, c.idNivel, c.idTurno, c.idParalelo,
                       n.nombre AS nivel, p.nombre AS paralelo,
                       t.nombre AS turno, g.anio
                FROM Inscripcion i
                INNER JOIN Curso c    ON c.idCurso    = i.idCurso
                INNER JOIN Nivel n    ON n.idNivel    = c.idNivel
                INNER JOIN Paralelo p ON p.idParalelo = c.idParalelo
                INNER JOIN Turno t    ON t.idTurno    = c.idTurno
                INNER JOIN Gestion g  ON g.idGestion  = c.idGestion
                WHERE i.idEstudiante = :id AND i.estado = 1
                ORDER BY i.fechaRegistro DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idEstudiante]);
        return $stmt->fetch() ?: null;
    }

    /**
     * idAdministrativo vinculado a un usuario (0 si no hay).
     */
    public function idAdministrativoDeUsuario(int $idUsuario): int
    {
        $stmt = $this->db->prepare(
            "SELECT idAdministrativo FROM Administrativo WHERE idUsuario = :id AND estado = 1 LIMIT 1"
        );
        $stmt->execute([':id' => $idUsuario]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    /**
     * Asigna/cambia el curso del estudiante gestionando la inscripción.
     *   - idCurso = 0  -> lo deja sin curso (desactiva inscripciones activas)
     *   - mismo curso  -> no hace nada
     *   - curso nuevo  -> desactiva el anterior y activa/crea el nuevo
     */
    public function asignarCurso(int $idEstudiante, int $idCurso, int $idAdministrativo): void
    {
        try {
            $actual = $this->cursoActualDe($idEstudiante);

            if ($idCurso <= 0) {
                $this->db->prepare("UPDATE Inscripcion SET estado = 0 WHERE idEstudiante = :e AND estado = 1")
                         ->execute([':e' => $idEstudiante]);
                return;
            }

            if ($actual === $idCurso) return; // sin cambios

            // Desactivar inscripciones activas anteriores
            $this->db->prepare("UPDATE Inscripcion SET estado = 0 WHERE idEstudiante = :e AND estado = 1")
                     ->execute([':e' => $idEstudiante]);

            // ¿Ya existe una inscripción (aunque inactiva) para ese curso?
            $q = $this->db->prepare(
                "SELECT idInscripcion FROM Inscripcion WHERE idEstudiante = :e AND idCurso = :c LIMIT 1"
            );
            $q->execute([':e' => $idEstudiante, ':c' => $idCurso]);
            $idInscripcion = (int) ($q->fetchColumn() ?: 0);

            if ($idInscripcion > 0) {
                $this->db->prepare("UPDATE Inscripcion SET estado = 1 WHERE idInscripcion = :id")
                         ->execute([':id' => $idInscripcion]);
            } elseif ($idAdministrativo > 0) {
                $this->db->prepare(
                    "INSERT INTO Inscripcion (idEstudiante, idCurso, idAdministrativo) VALUES (:e, :c, :a)"
                )->execute([':e' => $idEstudiante, ':c' => $idCurso, ':a' => $idAdministrativo]);
            } else {
                error_log('[ESTUDIANTE] asignarCurso: sin idAdministrativo para crear la inscripción.');
            }
        } catch (Throwable $e) {
            error_log('[ESTUDIANTE] asignarCurso: ' . $e->getMessage());
        }
    }
}