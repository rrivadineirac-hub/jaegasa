<?php
/**
 * ============================================================
 * Conexion.php
 * Conexión PDO a la base de datos jaegasa_db_fluter (patrón Singleton)
 * Sistema Académico JAEGASA
 * ============================================================
 */

class Conexion
{
    // Parámetros de conexión (ajustar según el entorno XAMPP)
    private const HOST    = 'localhost';
    private const DB      = 'jaegasa_db_fluter';
    private const USER    = 'root';
    private const PASS    = '';            // Por defecto en XAMPP la contraseña de root está vacía
    private const CHARSET = 'utf8mb4';

    // Instancia única de la conexión
    private static ?PDO $instancia = null;

    /**
     * Devuelve la única instancia de PDO.
     * Si no existe, la crea.
     */
    public static function obtener(): PDO
    {
        if (self::$instancia === null) {
            $dsn = 'mysql:host=' . self::HOST
                 . ';dbname='   . self::DB
                 . ';charset='  . self::CHARSET;

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Lanza excepciones ante errores
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Arreglos asociativos por defecto
                PDO::ATTR_EMULATE_PREPARES   => false,                   // Prepared statements reales
            ];

            try {
                self::$instancia = new PDO($dsn, self::USER, self::PASS, $opciones);
            } catch (PDOException $e) {
                // En producción no se debe mostrar el detalle; aquí ayuda al desarrollo.
                http_response_code(500);
                die('Error de conexión con la base de datos: ' . $e->getMessage());
            }
        }

        return self::$instancia;
    }

    // Evita que se instancie o clone la clase
    private function __construct() {}
    private function __clone() {}
}
