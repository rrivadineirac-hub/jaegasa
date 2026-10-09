<?php
/**
 * ============================================================
 * mlogin.php  —  Modelo de Login
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Encapsula el acceso a datos relacionado con la autenticación
 * de usuarios (tabla Usuario + Roles).
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloLogin
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Busca un usuario activo por su nombre de acceso e incluye
     * el nombre del rol asociado.
     *
     * @param string $nombre Nombre de usuario
     * @return array|null Datos del usuario o null si no existe
     */
    public function buscarPorNombre(string $nombre): ?array
    {
        $sql = "SELECT u.idUsuario,
                       u.idRoles,
                       u.nombre,
                       u.password,
                       u.estado,
                       r.nombre AS rol
                FROM Usuario u
                INNER JOIN Roles r ON r.idRoles = u.idRoles
                WHERE u.nombre = :nombre
                  AND u.estado = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':nombre' => $nombre]);

        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Valida las credenciales de un usuario.
     *
     * @param string $nombre   Nombre de usuario
     * @param string $password Contraseña en texto plano
     * @return array|null Datos del usuario (sin password) si son válidas, null si no
     */
    public function validar(string $nombre, string $password): ?array
    {
        $usuario = $this->buscarPorNombre($nombre);

        if ($usuario === null) {
            return null;
        }

        if (!password_verify($password, $usuario['password'])) {
            return null;
        }

        // No exponer el hash de la contraseña fuera del modelo
        unset($usuario['password']);
        return $usuario;
    }

    /**
     * Recupera los datos del perfil (Administrativo/Docente) vinculado
     * al usuario para mostrar nombre y foto en el panel.
     *
     * @param int $idUsuario
     * @return array|null
     */
    public function perfil(int $idUsuario): ?array
    {
        // Se busca primero como administrativo, luego como docente.
        // Nota: se usan placeholders distintos (:idA / :idD) porque con
        // prepared statements nativos (EMULATE_PREPARES=false) MySQL no
        // permite reutilizar el mismo parámetro con nombre.
        $sql = "SELECT CONCAT(apPaterno,' ',apMaterno,' ', nombre) AS nombreCompleto, foto, cargo
                FROM Administrativo WHERE idUsuario = :idA AND estado = 1
                UNION
                SELECT CONCAT(apPaterno,' ',apMaterno,' ', nombre) AS nombreCompleto, foto, 'Docente' AS cargo
                FROM Docente WHERE idUsuario = :idD AND estado = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':idA' => $idUsuario, ':idD' => $idUsuario]);

        $fila = $stmt->fetch();
        return $fila ?: null;
    }
}
