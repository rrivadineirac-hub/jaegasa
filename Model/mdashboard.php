<?php
/**
 * ============================================================
 * mdashboard.php  —  Modelo del Dashboard
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Provee los conteos y datos resumidos que se muestran en las
 * tarjetas del panel principal.
 * ============================================================
 */

require_once __DIR__ . '/../Config/Conexion.php';

class ModeloDashboard
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /**
     * Cuenta los registros activos (estado = 1) de una tabla.
     * El nombre de la tabla se valida contra una lista blanca
     * para evitar inyección SQL en el identificador.
     */
    private function contarActivos(string $tabla): int
    {
        $tablasPermitidas = [
            'Estudiante', 'Docente', 'Administrativo',
            'Tutor', 'Curso', 'Inscripcion',
        ];

        if (!in_array($tabla, $tablasPermitidas, true)) {
            return 0;
        }

        $sql  = "SELECT COUNT(*) AS total FROM {$tabla} WHERE estado = 1";
        $stmt = $this->db->query($sql);
        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    /**
     * Devuelve todas las estadísticas del dashboard en un solo arreglo.
     */
    public function estadisticas(): array
    {
        return [
            'estudiantes'    => $this->contarActivos('Estudiante'),
            'docentes'       => $this->contarActivos('Docente'),
            'administrativos'=> $this->contarActivos('Administrativo'),
            'tutores'        => $this->contarActivos('Tutor'),
            'cursos'         => $this->contarActivos('Curso'),
            'inscripciones'  => $this->contarActivos('Inscripcion'),
        ];
    }

    /**
     * Últimos estudiantes registrados (para la tabla de actividad reciente).
     */
    public function ultimosEstudiantes(int $limite = 5): array
    {
        $limite = max(1, min($limite, 50)); // acotar
        $sql = "SELECT idEstudiante, rude,
                       CONCAT(apPaterno,' ',apMaterno,' ',nombre) AS nombreCompleto,
                       fechaRegistro
                FROM Estudiante
                WHERE estado = 1
                ORDER BY fechaRegistro DESC
                LIMIT {$limite}";

        return $this->db->query($sql)->fetchAll();
    }
}
