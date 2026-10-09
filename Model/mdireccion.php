<?php
/**
 * ============================================================
 * mdireccion.php  —  Modelo del CRUD Dirección
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Usa los stored procedures de la base de datos (SIN prefijo sp_):
 *   InsertarDireccion  -> crear
 *   EditarDireccion    -> editar
 *   EliminarDireccion  -> eliminar (baja lógica con estadoReg = 0)
 *
 * OJO: en la tabla Direccion la columna "estado" es VARCHAR
 * (departamento geográfico); la bandera de activo es "estadoReg".
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloDireccion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Lista las direcciones activas (con filtro de búsqueda opcional).
     */
    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT idDireccion, barrio, numero, calle, ubicacion_gps, fechaRegistro
                FROM Direccion
                WHERE estadoReg = 1";

        $params = [];
        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            foreach ($palabras as $p) {
                $sub = [];
                $campos = ["barrio", "calle"];
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

        $sql .= " ORDER BY barrio, calle";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una dirección por su id.
     */
    public function obtener(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT idDireccion, barrio, numero, calle, ubicacion_gps, fechaRegistro
             FROM Direccion WHERE idDireccion = :id AND estadoReg = 1 LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    // =========================================================
    // CREAR
    // =========================================================
    public function crear(array $d): array
    {
        try {
            $sql = "INSERT INTO Direccion (barrio, numero, calle, ubicacion_gps)
                    VALUES (:barrio, :numero, :calle, :ubicacionGps)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':barrio'       => $d['barrio']       ?: null,
                ':numero'       => $d['numero']       ?: null,
                ':calle'        => $d['calle']        ?: null,
                ':ubicacionGps' => $d['ubicacionGps'] ?: null,
            ]);
            $id = (int) $this->db->lastInsertId();
            return [
                'ok'      => true,
                'id'      => $id,
                'mensaje' => 'Dirección registrada correctamente.',
            ];

        } catch (Throwable $e) {
            error_log('[DIRECCION] crear: ' . $e->getMessage());
            return ['ok' => false, 'id' => 0, 'mensaje' => 'No se pudo registrar la dirección.'];
        }
    }

    // =========================================================
    // EDITAR
    // =========================================================
    public function actualizar(int $id, array $d): array
    {
        try {
            $sql = "UPDATE Direccion 
                    SET barrio = :barrio, numero = :numero, calle = :calle, ubicacion_gps = :ubicacionGps
                    WHERE idDireccion = :id AND estadoReg = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id'           => $id,
                ':barrio'       => $d['barrio']       ?: null,
                ':numero'       => $d['numero']       ?: null,
                ':calle'        => $d['calle']        ?: null,
                ':ubicacionGps' => $d['ubicacionGps'] ?: null,
            ]);

            return [
                'ok'      => true,
                'mensaje' => 'Dirección actualizada correctamente.',
            ];

        } catch (Throwable $e) {
            error_log('[DIRECCION] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar la dirección.'];
        }
    }

    // =========================================================
    // ELIMINAR (baja lógica)
    // =========================================================
    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("UPDATE Direccion SET estadoReg = 0 WHERE idDireccion = :id");
            $stmt->execute([':id' => $id]);

            return [
                'ok'      => true,
                'mensaje' => 'Dirección eliminada correctamente.',
            ];

        } catch (Throwable $e) {
            error_log('[DIRECCION] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar la dirección.'];
        }
    }

    /**
     * Lista estudiantes con su dirección registrada, filtrados por Curso, Paralelo, Nivel, Turno o búsqueda.
     */
    public function listarEstudiantesConDireccion(string $buscar = '', string $grado = '', string $paralelo = '', string $nivel = '', string $turno = ''): array
    {
        $sql = "SELECT e.idEstudiante, e.rude, e.apPaterno, e.apMaterno, e.nombre, e.ci,
                       ins.cursoNombre, ins.cursoNivel, ins.cursoParalelo, ins.cursoTurno, ins.cursoAnio,
                       
                       d.idDireccion, d.barrio, d.numero, d.calle, d.ubicacion_gps

                FROM Estudiante e
                LEFT JOIN (
                    SELECT x.idEstudiante, c.idCurso, c.nombre AS cursoNombre, n.nombre AS cursoNivel,
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

                LEFT JOIN Direccion d ON d.idDireccion = e.idDireccion AND d.estadoReg = 1

                WHERE e.estado = 1";

        $params = [];
        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            $esSingleLetter = (mb_strlen(trim($buscar)) === 1 && !is_numeric(trim($buscar)));

            foreach ($palabras as $p) {
                $sub = [];
                $campos = ["e.nombre", "e.apPaterno", "e.apMaterno", "e.ci", "d.barrio", "d.calle"];
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
            $sql .= " AND ins.cursoNombre = :grado";
            $params[':grado'] = $grado;
        }

        if ($paralelo !== '') {
            if (is_numeric($paralelo)) {
                $sql .= " AND ins.idParalelo = :paralelo";
                $params[':paralelo'] = (int) $paralelo;
            } else {
                $sql .= " AND ins.cursoParalelo = :paralelo";
                $params[':paralelo'] = $paralelo;
            }
        }
        if ($nivel !== '') {
            if (is_numeric($nivel)) {
                $sql .= " AND ins.idNivel = :nivel";
                $params[':nivel'] = (int) $nivel;
            } else {
                $sql .= " AND ins.cursoNivel = :nivel";
                $params[':nivel'] = $nivel;
            }
        }
        if ($turno !== '') {
            if (is_numeric($turno)) {
                $sql .= " AND ins.idTurno = :turno";
                $params[':turno'] = (int) $turno;
            } else {
                $sql .= " AND ins.cursoTurno = :turno";
                $params[':turno'] = $turno;
            }
        }

        $sql .= " ORDER BY FIELD(ins.cursoNombre, 'Primero', 'Segundo', 'Tercero', 'Cuarto', 'Quinto', 'Sexto'), ins.cursoNombre, ins.cursoParalelo, e.apPaterno, e.apMaterno, e.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
