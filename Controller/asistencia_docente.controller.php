<?php
/**
 * ============================================================
 * asistencia_docente.controller.php — Controlador CRUD Asistencia (Docente)
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * El docente pasa lista por materia/horario. Cada envío guarda
 * (o actualiza) las asistencias de todos los estudiantes de ese
 * curso para el horario y fecha seleccionados.
 * Accesible solo para Docente (idRoles = 2).
 * ============================================================
 */

require_once BASE_PATH . '/Model/masistencia_docente.php';

$rolSesion = (int) ($_SESSION['idRoles'] ?? 0);
if ($rolSesion !== 2) {
    http_response_code(403);
    die('Acceso denegado. Este módulo es exclusivo para docentes.');
}

$accion    = $_GET['a'] ?? 'index';
$modelo    = new ModeloAsistenciaDocente();
$urlModulo = BASE_URL . 'index.php?c=asistencia_docente';

if (!function_exists('redirigirConMensaje')) {
    function redirigirConMensaje(string $urlModulo, string $tipo, string $mensaje): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: ' . $urlModulo);
        exit;
    }
}

switch ($accion) {

    // --------------------------------------------------------
    // GUARDAR ASISTENCIAS DE UN HORARIO (MATERIA) EN UNA FECHA
    // --------------------------------------------------------
    case 'guardar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $idHorario = (int) ($_POST['idHorario'] ?? 0);
        $fecha     = date('Y-m-d'); // La fecha se fija estrictamente al día de hoy para docentes
        // Formato esperado: [idInscripcion => ['estado' => 'A|R|F|L', 'horaLlegada' => '08:15', 'observacion' => '...']]
        $asist     = $_POST['asistencias'] ?? [];
        foreach ($asist as $k => $v) {
            if (isset($v['observacion'])) {
                $asist[$k]['observacion'] = Seguridad::mayuscula($v['observacion']);
            }
        }


        if ($idHorario <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'Faltan datos obligatorios para registrar la asistencia.');
        }

        $idDocente = $modelo->obtenerDocentePorUsuario((int) ($_SESSION['idUsuario'] ?? 0));
        if ($idDocente === null) {
            redirigirConMensaje($urlModulo, 'danger', 'Tu usuario no está vinculado a ningún docente.');
        }

        // Seguridad: el horario debe pertenecer efectivamente a este docente
        if (!$modelo->horarioPerteneceADocente($idHorario, $idDocente)) {
            redirigirConMensaje($urlModulo, 'danger', 'No tienes permiso para registrar asistencia en ese horario.');
        }

        $resultado = $modelo->guardarAsistenciaMasiva($idHorario, $fecha, $idDocente, $asist);

        redirigirConMensaje(
            $urlModulo . "&idHorario={$idHorario}&fecha={$fecha}",
            $resultado['ok'] ? 'success' : 'danger',
            $resultado['mensaje']
        );
        break;

    // --------------------------------------------------------
    // VISTA PRINCIPAL: clases del día + planilla de un horario
    // --------------------------------------------------------
    case 'index':
    default:
        $fecha = date('Y-m-d'); // La fecha está fija para el día de hoy para el docente

        $idDocente = $modelo->obtenerDocentePorUsuario((int) ($_SESSION['idUsuario'] ?? 0));

        $clasesDelDia          = [];
        $estudiantes           = [];
        $horarioSemanalDocente = [];
        $periodosDocente       = [];
        $idHorario             = (int) ($_GET['idHorario'] ?? 0);

        if ($idDocente === null) {
            $flash = ['tipo' => 'danger', 'mensaje' => 'Tu usuario no está vinculado a ningún docente. Contacta al administrador.'];
        } else {
            $clasesDelDia          = $modelo->obtenerClasesDelDocente($idDocente, $fecha);
            $horarioSemanalDocente = $modelo->obtenerHorarioSemanalDocente($idDocente);
            $periodosDocente       = $modelo->listarPeriodos();

            if ($idHorario > 0 && $modelo->horarioPerteneceADocente($idHorario, $idDocente)) {
                $estudiantes = $modelo->listarEstudiantesPorHorarioFecha($idHorario, $fecha);
            } else {
                $idHorario = 0;
            }

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);
        }

        require_once BASE_PATH . '/View/vasistencia_docente.php';
        break;
}