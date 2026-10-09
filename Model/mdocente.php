<?php
/**
 * ============================================================
 * mdocente.php  —  Modelo del CRUD Docente
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Usa los stored procedures de la base de datos:
 *   InsertarDocente  -> crear
 *   EditarDocente    -> editar
 *   EliminarDocente  -> eliminar (baja lógica)
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloDocente
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Lista los docentes activos (con filtro de búsqueda opcional).
     */
    public function listar(string $buscar = ''): array
    {
        $sql = "SELECT d.idDocente,
                       d.apPaterno, d.apMaterno, d.nombre,
                       d.ci, d.numeroItem, d.rda, d.genero,
                       d.correo, d.telefono, d.direccion,
                       d.nacionalidad, d.departamento, d.provincia,
                       d.fechaNacimiento, d.foto,
                       d.fechaRegistro, d.idUsuario, d.idCategoria,
                       c.nombre AS categoria,
                       u.nombre AS usuario
                FROM Docente d
                LEFT JOIN Categoria c ON c.idCategoria = d.idCategoria
                LEFT JOIN Usuario   u ON u.idUsuario   = d.idUsuario
                WHERE d.estado = 1";

        $params = [];
        if ($buscar !== '') {
            $palabras = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($buscar))));
            $condicionesPalabras = [];
            $k = 0;
            foreach ($palabras as $p) {
                $sub = [];
                $campos = ["d.nombre", "d.apPaterno", "d.apMaterno", "d.ci", "u.nombre"];
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
                $sub[] = ($esEnne ? "BINARY " : "") . "CONCAT_WS(' ', d.apPaterno, d.apMaterno, d.nombre) LIKE {$paramKey3}";
                $params[$paramKey3] = $p . '%';

                $k++;
                $paramKey4 = ":qpk{$k}d";
                $sub[] = ($esEnne ? "BINARY " : "") . "CONCAT_WS(' ', d.nombre, d.apPaterno, d.apMaterno) LIKE {$paramKey4}";
                $params[$paramKey4] = $p . '%';

                $condicionesPalabras[] = "(" . implode(" OR ", $sub) . ")";
            }
            if (!empty($condicionesPalabras)) {
                $sql .= " AND " . implode(" AND ", $condicionesPalabras);
            }
        }

        $sql .= " ORDER BY d.apPaterno, d.apMaterno";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un docente por su id.
     */
    public function obtener(int $id): ?array
    {
        $sql = "SELECT d.*, c.nombre AS categoria, u.nombre AS usuario
                FROM Docente d
                LEFT JOIN Categoria c ON c.idCategoria = d.idCategoria
                LEFT JOIN Usuario   u ON u.idUsuario   = d.idUsuario
                WHERE d.idDocente = :id AND d.estado = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Lista las categorías activas (para el formulario).
     */
    public function listarCategorias(): array
    {
        $sql = "SELECT idCategoria, nombre FROM Categoria WHERE estado = 1 ORDER BY CASE LOWER(TRIM(nombre)) WHEN 'quinta' THEN 1 WHEN 'cuarta' THEN 2 WHEN 'tercera' THEN 3 WHEN 'segunda' THEN 4 WHEN 'primera' THEN 5 WHEN 'cero' THEN 6 WHEN 'mérito' THEN 7 WHEN 'merito' THEN 7 ELSE 8 END, idCategoria ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Verifica si un CI ya está en uso (opcionalmente excluye un id).
     */
    public function ciEnUso(string $ci, int $exceptoId = 0): bool
    {
        $sql = "SELECT COUNT(*) AS n FROM Docente
                WHERE ci = :ci AND estado = 1 AND idDocente <> :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ci' => $ci, ':id' => $exceptoId]);
        return (int) ($stmt->fetch()['n'] ?? 0) > 0;
    }

    /**
     * Obtiene los datos de un docente activo buscando por su CI.
     */
    public function obtenerPorCi(string $ci): ?array
    {
        $sql = "SELECT d.idDocente,
                       d.apPaterno, d.apMaterno, d.nombre,
                       d.ci, d.numeroItem, d.rda, d.genero,
                       d.correo, d.telefono, d.direccion,
                       d.nacionalidad, d.departamento, d.provincia,
                       d.fechaNacimiento AS fechaNac, d.foto,
                       d.idCategoria,
                       c.nombre AS categoria,
                       u.nombre AS usuario
                FROM Docente d
                LEFT JOIN Categoria c ON c.idCategoria = d.idCategoria
                LEFT JOIN Usuario   u ON u.idUsuario   = d.idUsuario
                WHERE d.ci = :ci AND d.estado = 1
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ci' => $ci]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Verifica si un nombre de usuario ya existe.
     */
    public function usuarioEnUso(string $nombreUsuario, int $exceptoIdUsuario = 0): bool
    {
        $sql = "SELECT COUNT(*) AS n FROM Usuario
                WHERE nombre = :n AND idUsuario <> :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':n' => $nombreUsuario, ':id' => $exceptoIdUsuario]);
        return (int) ($stmt->fetch()['n'] ?? 0) > 0;
    }

    // =========================================================
    // CREAR  ->  InsertarDocente
    // =========================================================
    public function crear(array $d): array
    {
        $hash = password_hash($d['password'], PASSWORD_DEFAULT);

        try {
            $sql = "CALL InsertarDocente(
                        :nombreUsuario, :password,
                        :apPaterno, :apMaterno, :nombre, :ci,
                        :numeroItem, :rda, :categoria, :genero, :fechaNac,
                        :correo, :telefono, :direccion, :nacionalidad,
                        :departamento, :provincia,
                        @idUsuario, @idDocente, @mensaje
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nombreUsuario' => $d['usuario'],
                ':password'      => $hash,
                ':apPaterno'     => $d['apPaterno'],
                ':apMaterno'     => $d['apMaterno'],
                ':nombre'        => $d['nombre'],
                ':ci'            => $d['ci'],
                ':numeroItem'    => $d['numeroItem']   ?: null,
                ':rda'           => $d['rda']          ?: null,
                ':categoria'     => $d['categoria']    ?: null,
                ':genero'        => $d['genero']       ?: null,
                ':fechaNac'      => $d['fechaNac']     ?: null,
                ':correo'        => $d['correo']       ?: null,
                ':telefono'      => $d['telefono']     ?: null,
                ':direccion'     => $d['direccion']    ?: null,
                ':nacionalidad'  => $d['nacionalidad'] ?: null,
                ':departamento'  => $d['departamento'] ?: null,
                ':provincia'     => $d['provincia']    ?: null,
            ]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @idDocente AS id, @mensaje AS mensaje")->fetch();
            $id      = (int) ($out['id'] ?? 0);
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok      = $id > 0;

            // Si se subió foto, guardarla en el registro recién creado
            if ($ok && !empty($d['foto'])) {
                $this->db->prepare("UPDATE Docente SET foto = :f WHERE idDocente = :id")
                         ->execute([':f' => $d['foto'], ':id' => $id]);
            }

            return [
                'ok'      => $ok,
                'mensaje' => $ok ? 'Docente registrado correctamente.'
                                 : ($mensaje ?: 'No se pudo registrar el docente.'),
            ];

        } catch (Throwable $e) {
            error_log('[DOCENTE] crear: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar. Verifique que el CI/usuario no estén duplicados.'];
        }
    }

    // =========================================================
    // EDITAR  ->  EditarDocente
    // =========================================================
    public function actualizar(int $id, array $d): array
    {
        $actual = $this->obtener($id);
        if ($actual === null) {
            return ['ok' => false, 'mensaje' => 'El docente no existe.'];
        }

        // Contraseña: hash si se envió; vacío si no (el SP no la cambia)
        $hashPass = '';
        if (!empty($d['password'])) {
            $hashPass = password_hash($d['password'], PASSWORD_DEFAULT);
        }

        try {
            $sql = "CALL EditarDocente(
                        :idDocente,
                        :nombreUsuario, :password,
                        :apPaterno, :apMaterno, :nombre, :ci,
                        :numeroItem, :rda, :categoria, :genero, :fechaNac,
                        :correo, :telefono, :direccion, :nacionalidad,
                        :departamento, :provincia,
                        @mensaje
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':idDocente'     => $id,
                ':nombreUsuario' => $d['usuario'],
                ':password'      => $hashPass,
                ':apPaterno'     => $d['apPaterno'],
                ':apMaterno'     => $d['apMaterno'],
                ':nombre'        => $d['nombre'],
                ':ci'            => $d['ci'],
                ':numeroItem'    => $d['numeroItem']   ?: null,
                ':rda'           => $d['rda']          ?: null,
                ':categoria'     => $d['categoria']    ?: null,
                ':genero'        => $d['genero']       ?: null,
                ':fechaNac'      => $d['fechaNac']     ?: null,
                ':correo'        => $d['correo']       ?: null,
                ':telefono'      => $d['telefono']     ?: null,
                ':direccion'     => $d['direccion']    ?: null,
                ':nacionalidad'  => $d['nacionalidad'] ?: null,
                ':departamento'  => $d['departamento'] ?: null,
                ':provincia'     => $d['provincia']    ?: null,
            ]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @mensaje AS mensaje")->fetch();
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok = str_starts_with($mensaje, 'OK');

            // Foto: si se subió una nueva, actualizarla y borrar la anterior
            if ($ok && !empty($d['foto'])) {
                $fotoVieja = $actual['foto'] ?? '';
                if ($fotoVieja !== '' && $fotoVieja !== 'default.png' && defined('BASE_PATH')) {
                    $rutaVieja = BASE_PATH . '/inagenes/Docente/' . $fotoVieja;
                    if (is_file($rutaVieja)) {
                        @unlink($rutaVieja);
                    }
                }
                $this->db->prepare("UPDATE Docente SET foto = :f WHERE idDocente = :id")
                         ->execute([':f' => $d['foto'], ':id' => $id]);
            }

            return [
                'ok'      => $ok,
                'mensaje' => $ok ? 'Docente actualizado correctamente.' : ($mensaje ?: 'No se pudo actualizar.'),
            ];

        } catch (Throwable $e) {
            error_log('[DOCENTE] actualizar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar. Verifique los datos (CI/usuario duplicado).'];
        }
    }

    // =========================================================
    // ELIMINAR (baja lógica)  ->  EliminarDocente
    // =========================================================
    public function eliminar(int $id): array
    {
        try {
            $stmt = $this->db->prepare("CALL EliminarDocente(:id, @mensaje)");
            $stmt->execute([':id' => $id]);
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @mensaje AS mensaje")->fetch();
            $mensaje = (string) ($out['mensaje'] ?? '');
            $ok = str_starts_with($mensaje, 'OK');

            return [
                'ok'      => $ok,
                'mensaje' => $ok ? 'Docente eliminado correctamente.' : ($mensaje ?: 'No se pudo eliminar.'),
            ];

        } catch (Throwable $e) {
            error_log('[DOCENTE] eliminar: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar el docente.'];
        }
    }
}
