<?php
/**
 * ============================================================
 * dashboard.controller.php  —  Controlador del Dashboard
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Prepara las estadísticas y actividad reciente, luego carga
 * la vista del panel principal.
 * ============================================================
 */

require_once BASE_PATH . '/Model/mdashboard.php';
require_once BASE_PATH . '/Model/masistencia_docente.php';

$modelo = new ModeloDashboard();
$rolSesion = (int) ($_SESSION['idRoles'] ?? 0);
$idUsuario = (int) ($_SESSION['idUsuario'] ?? 0);

$esDocente = ($rolSesion === 2);
$horarioSemanalDocente = [];
$periodosDocente       = [];
$clasesDelDiaDocente   = [];
$idDocenteSesion       = null;

if ($esDocente) {
    $modeloAsistDoc = new ModeloAsistenciaDocente();
    $idDocenteSesion = $modeloAsistDoc->obtenerDocentePorUsuario($idUsuario);
    if ($idDocenteSesion !== null) {
        $fechaHoy = date('Y-m-d');
        $horarioSemanalDocente = $modeloAsistDoc->obtenerHorarioSemanalDocente($idDocenteSesion);
        $periodosDocente       = $modeloAsistDoc->listarPeriodos();
        $clasesDelDiaDocente   = $modeloAsistDoc->obtenerClasesDelDocente($idDocenteSesion, $fechaHoy);
    }
}

// Datos que consumirá la vista de administración
$stats = $modelo->estadisticas();

require_once BASE_PATH . '/View/dashboard.php';
