<?php
/**
 * ============================================================
 * inscripcion.controller.php  —  Controlador de Inscripciones
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Acciones: index, guardar (inscribir), eliminar
 * Solo accesible para el rol Administrador (idRoles = 1).
 * ============================================================
 */

require_once BASE_PATH . '/Model/minscripcion.php';

if ((int) ($_SESSION['idRoles'] ?? 0) !== 1) {
    http_response_code(403);
    die('Acceso denegado.');
}

$accion = $_GET['a'] ?? 'index';
$modelo = new ModeloInscripcion();
$urlModulo = BASE_URL . 'index.php?c=inscripcion';

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
    // EXPORTAR COMPROBANTE EN PDF
    // --------------------------------------------------------
    case 'pdf':
        $id = (int) ($_GET['id'] ?? 0);
        $ins = $modelo->obtenerPorId($id);

        if (!$ins) {
            die('Inscripción no encontrada.');
        }

        require_once BASE_PATH . '/vendor/setasign/fpdf/fpdf.php';

        $pdf = new \FPDF('P', 'mm', 'Letter');
        $pdf->AddPage();
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(true, 8);

        $w = 200; // Ancho utilizable

        // --- ENCABEZADO MINISTERIAL ---
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(31, 41, 55);
        $pdf->Cell($w, 3.5, utf8_decode('ESTADO PLURINACIONAL DE BOLIVIA - MINISTERIO DE EDUCACIÓN'), 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(29, 138, 181);
        $pdf->Cell($w, 5, utf8_decode('FORMULARIO RUDE - REGISTRO ÚNICO DE ESTUDIANTES'), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 6.5);
        $pdf->SetTextColor(107, 114, 128);
        $pdf->Cell($w, 3, utf8_decode('LA INFORMACIÓN RECABADA EN EL RUDE ES CONSIDERADA COMO UNA DECLARACIÓN JURADA'), 0, 1, 'C');
        $pdf->Ln(1);

        // --- CÓDIGO RUDE ---
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(31, 41, 55);
        $pdf->Cell(55, 5, utf8_decode('(*) CÓDIGO RUDE DEL ESTUDIANTE:'), 0, 0, 'L');

        $rudeCode = str_pad($ins['rude'] ?? '', 16, ' ');
        for ($k = 0; $k < 16; $k++) {
            $char = substr($rudeCode, $k, 1);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(6.5, 5, utf8_decode($char), 1, 0, 'C');
        }
        $pdf->Ln(6.5);

        // --- 1. DATOS DEL CENTRO DE EDUCACIÓN ---
        $pdf->SetFillColor(243, 244, 246);
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(29, 138, 181);
        $pdf->Cell($w, 4.5, utf8_decode(' 1. DATOS DE LA UNIDAD EDUCATIVA (*) CÓDIGO SIE: 80122379'), 1, 1, 'L', true);

        $pdf->SetTextColor(31, 41, 55);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(120, 4, utf8_decode('1.1 NOMBRE DE LA UNIDAD EDUCATIVA'), 1, 0, 'L');
        $pdf->Cell(80, 4, utf8_decode('1.2 LOCALIDAD / DIRECCIÓN'), 1, 1, 'L');

        $pdf->SetFont('Arial', '', 7.5);
        $pdf->Cell(120, 4.5, utf8_decode('UNIDAD EDUCATIVA PROF. JAIME ALFONSO ESCALANTE GUTIÉRREZ "A"'), 1, 0, 'L');
        $pdf->Cell(80, 4.5, utf8_decode('SANTA CRUZ - BOLIVIA'), 1, 1, 'L');

        $pdf->Ln(2);

        // --- 2. DATOS DE LA O EL ESTUDIANTE ---
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(29, 138, 181);
        $pdf->Cell($w, 4.5, utf8_decode(' 2. DATOS DE LA O EL ESTUDIANTE / PARTICIPANTE'), 1, 1, 'L', true);

        $pdf->SetTextColor(31, 41, 55);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(65, 4, utf8_decode('2.1 APELLIDO PATERNO'), 1, 0, 'L');
        $pdf->Cell(65, 4, utf8_decode('APELLIDO MATERNO'), 1, 0, 'L');
        $pdf->Cell(70, 4, utf8_decode('NOMBRE(S)'), 1, 1, 'L');

        $pdf->SetFont('Arial', '', 7.5);
        $pdf->Cell(65, 4.5, utf8_decode($ins['apPaterno'] ?: '—'), 1, 0, 'L');
        $pdf->Cell(65, 4.5, utf8_decode($ins['apMaterno'] ?: '—'), 1, 0, 'L');
        $pdf->Cell(70, 4.5, utf8_decode($ins['nombre'] ?: '—'), 1, 1, 'L');

        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(45, 4, utf8_decode('2.2 C.I.'), 1, 0, 'L');
        $pdf->Cell(30, 4, utf8_decode('2.3 FECHA NAC.'), 1, 0, 'L');
        $pdf->Cell(35, 4, utf8_decode('2.4 SEXO'), 1, 0, 'L');
        $pdf->Cell(45, 4, utf8_decode('2.5 ESTADO CIVIL'), 1, 0, 'L');
        $pdf->Cell(45, 4, utf8_decode('2.6 CERT. NACIMIENTO'), 1, 1, 'L');

        $pdf->SetFont('Arial', '', 7.5);
        $pdf->Cell(45, 4.5, utf8_decode($ins['estudianteCi'] ?: '—'), 1, 0, 'L');
        $pdf->Cell(30, 4.5, utf8_decode($ins['fechaNacimiento'] ?: '—'), 1, 0, 'L');
        $sexoStr = ($ins['genero'] === 'M') ? 'MASCULINO [X]' : (($ins['genero'] === 'F') ? 'FEMENINO [X]' : 'F [  ]  M [  ]');
        $pdf->Cell(35, 4.5, utf8_decode($sexoStr), 1, 0, 'L');
        $pdf->Cell(45, 4.5, utf8_decode('Soltero/a [X]  Otro [  ]'), 1, 0, 'L');
        $pdf->Cell(45, 4.5, utf8_decode('Ofic: __ Lib: __ Part: __ Folio: __'), 1, 1, 'L');

        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell($w, 4, utf8_decode('2.7 LUGAR DE NACIMIENTO: País: ' . ($ins['nacionalidad'] ?: 'Bolivia') . '  |  Depto: ' . ($ins['depNac'] ?: '—') . '  |  Provincia: ' . ($ins['provNac'] ?: '—')), 1, 1, 'L');

        $pdf->Ln(2);

        // --- 3. DISCAPACIDAD Y TEA ---
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(29, 138, 181);
        $pdf->Cell($w, 4.5, utf8_decode(' 3. DATOS SOBRE DISCAPACIDAD Y TRASTORNO DEL ESPECTRO AUTISTA (TEA)'), 1, 1, 'L', true);

        $pdf->SetTextColor(31, 41, 55);
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell($w, 4, utf8_decode('3.1 ¿Presenta alguna discapacidad?:   Sí [  ]   No [X]   |   Carnet CODEPEDIS/IBC N°: ________________'), 1, 1, 'L');
        $pdf->Cell($w, 4, utf8_decode('Tipos: Auditiva [ ]  Visual [ ]  Intelectual [ ]  Física-Motora [ ]  Mental [ ]  Múltiple [ ]  |  Grado: Leve [ ] Moderado [ ] Grave [ ]'), 1, 1, 'L');
        $pdf->Cell($w, 4, utf8_decode('3.2 ¿Tiene diagnóstico TEA?:   Sí [  ]   No [X]   |   Tipo 1 [ ]   Tipo 2 [ ]   Tipo 3 [ ]'), 1, 1, 'L');

        $pdf->Ln(2);

        // --- 4. DATOS DEL PADRE, MADRE O TUTOR(A) ---
        $padre = $ins['padre'] ?? null;
        $madre = $ins['madre'] ?? null;
        $tutor = $ins['tutorOtro'] ?? null;

        $padreNom = $padre ? trim(($padre['apPaterno']??'').' '.($padre['apMaterno']??'').' '.($padre['nombre']??'')) : '—';
        $padreCi  = $padre['ci'] ?? '—';
        $padreOcu = $padre['ocupacion'] ?? '—';
        $padreTel = $padre['telefono'] ?? '—';

        $madreNom = $madre ? trim(($madre['apPaterno']??'').' '.($madre['apMaterno']??'').' '.($madre['nombre']??'')) : '—';
        $madreCi  = $madre['ci'] ?? '—';
        $madreOcu = $madre['ocupacion'] ?? '—';
        $madreTel = $madre['telefono'] ?? '—';

        $tutorNom = $tutor ? trim(($tutor['apPaterno']??'').' '.($tutor['apMaterno']??'').' '.($tutor['nombre']??'')) : '—';
        $tutorCi  = $tutor['ci'] ?? '—';
        $tutorPar = ($tutor['relParentesco'] ?? 'Tutor') . ($tutor['ocupacion'] ? ' - ' . $tutor['ocupacion'] : '');
        $tutorTel = $tutor['telefono'] ?? '—';

        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(29, 138, 181);
        $pdf->Cell($w, 4.5, utf8_decode(' 4. DATOS DEL PADRE, MADRE O TUTOR(A)'), 1, 1, 'L', true);

        $pdf->SetTextColor(31, 41, 55);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(30, 4, utf8_decode('TIPO'), 1, 0, 'L');
        $pdf->Cell(70, 4, utf8_decode('APELLIDOS Y NOMBRES'), 1, 0, 'L');
        $pdf->Cell(30, 4, utf8_decode('C.I.'), 1, 0, 'L');
        $pdf->Cell(35, 4, utf8_decode('OCUPACIÓN'), 1, 0, 'L');
        $pdf->Cell(35, 4, utf8_decode('TELÉFONO / CELULAR'), 1, 1, 'L');

        $pdf->SetFont('Arial', '', 7.5);
        // 4.1 Padre
        $pdf->Cell(30, 4.5, utf8_decode('4.1 PADRE'), 1, 0, 'L');
        $pdf->Cell(70, 4.5, utf8_decode($padreNom), 1, 0, 'L');
        $pdf->Cell(30, 4.5, utf8_decode($padreCi), 1, 0, 'L');
        $pdf->Cell(35, 4.5, utf8_decode($padreOcu), 1, 0, 'L');
        $pdf->Cell(35, 4.5, utf8_decode($padreTel), 1, 1, 'L');

        // 4.2 Madre
        $pdf->Cell(30, 4.5, utf8_decode('4.2 MADRE'), 1, 0, 'L');
        $pdf->Cell(70, 4.5, utf8_decode($madreNom), 1, 0, 'L');
        $pdf->Cell(30, 4.5, utf8_decode($madreCi), 1, 0, 'L');
        $pdf->Cell(35, 4.5, utf8_decode($madreOcu), 1, 0, 'L');
        $pdf->Cell(35, 4.5, utf8_decode($madreTel), 1, 1, 'L');

        // 4.3 Tutor/Apoderado
        $pdf->Cell(30, 4.5, utf8_decode('4.3 TUTOR(A)'), 1, 0, 'L');
        $pdf->Cell(70, 4.5, utf8_decode($tutorNom), 1, 0, 'L');
        $pdf->Cell(30, 4.5, utf8_decode($tutorCi), 1, 0, 'L');
        $pdf->Cell(35, 4.5, utf8_decode($tutorPar ?: '—'), 1, 0, 'L');
        $pdf->Cell(35, 4.5, utf8_decode($tutorTel), 1, 1, 'L');

        $pdf->Ln(2);

        // --- 5. DIRECCIÓN ACTUAL ---
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(29, 138, 181);
        $pdf->Cell($w, 4.5, utf8_decode(' 5. DIRECCIÓN ACTUAL DE LA O EL ESTUDIANTE'), 1, 1, 'L', true);

        $pdf->SetTextColor(31, 41, 55);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(45, 4, utf8_decode('DEPARTAMENTO'), 1, 0, 'L');
        $pdf->Cell(45, 4, utf8_decode('LOCALIDAD / MUNICIPIO'), 1, 0, 'L');
        $pdf->Cell(55, 4, utf8_decode('ZONA / BARRIO'), 1, 0, 'L');
        $pdf->Cell(55, 4, utf8_decode('AVENIDA / CALLE Y NRO'), 1, 1, 'L');

        $pdf->SetFont('Arial', '', 7.5);
        $pdf->Cell(45, 4.5, utf8_decode($ins['departamentoDir'] ?: 'Santa Cruz'), 1, 0, 'L');
        $pdf->Cell(45, 4.5, utf8_decode($ins['localidad'] ?: '—'), 1, 0, 'L');
        $pdf->Cell(55, 4.5, utf8_decode($ins['barrio'] ?: '—'), 1, 0, 'L');
        $calleNro = trim(($ins['calle'] ?? '') . ' ' . ($ins['numeroDir'] ?? ''));
        $pdf->Cell(55, 4.5, utf8_decode($calleNro ?: '—'), 1, 1, 'L');

        $pdf->Ln(2);

        // --- 6. EDUCACIÓN DIVERSA ---
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(29, 138, 181);
        $pdf->Cell($w, 4.5, utf8_decode(' 6. EDUCACIÓN DIVERSA'), 1, 1, 'L', true);

        $pdf->SetTextColor(31, 41, 55);
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell($w, 4, utf8_decode('Contexto diverso: Soldados/Marineros [ ]  Encierro [ ]  Trabajadoras Hogar [ ]  Otros [ ]   |   Especificar: ____________________'), 1, 1, 'L');

        $pdf->Ln(2);

        // --- 7. ASPECTOS SOCIOECONÓMICOS ---
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(29, 138, 181);
        $pdf->Cell($w, 4.5, utf8_decode(' 7. ASPECTOS SOCIOECONÓMICOS DE LA O EL ESTUDIANTE'), 1, 1, 'L', true);

        $pdf->SetTextColor(31, 41, 55);
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell($w, 4, utf8_decode('7.1 Idioma niñez: Castellano [X]  Otro: _____  |  Pertenencia cultural: Ninguno [X]  Aymara [ ]  Quechua [ ]  Guaraní [ ]'), 1, 1, 'L');
        $pdf->Cell($w, 4, utf8_decode('7.2 Salud: Centro de Salud en zona: Sí [X] No [ ]  |  Seguro: Sí [ ] No [X]  |  Acude a: Posta/Hosp. Público [X]'), 1, 1, 'L');
        $pdf->Cell($w, 4, utf8_decode('7.3 Ocupación: Estudiante [X]  Trabaja: No [X]  |  Acceso a Internet: Celular [X]  Vivienda [X]  |  Frecuencia: Diaria [X]'), 1, 1, 'L');
        $pdf->Cell($w, 4, utf8_decode('7.4 Transporte: A pie [ ]  Microbús/Trufi [X]  Demora: 15 min  |  Servicios Básicos: Agua [X]  Alcantarillado [X]  Electricidad [X]'), 1, 1, 'L');

        $pdf->Ln(2);

        // --- 8. INSCRIPCIÓN GESTIÓN ACTUAL ---
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(29, 138, 181);
        $pdf->Cell($w, 4.5, utf8_decode(' 8. DATOS DE INSCRIPCIÓN DE LA GESTIÓN ACTUAL'), 1, 1, 'L', true);

        $pdf->SetTextColor(31, 41, 55);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(40, 4, utf8_decode('NIVEL'), 1, 0, 'L');
        $pdf->Cell(45, 4, utf8_decode('CURSO / GRADO'), 1, 0, 'L');
        $pdf->Cell(35, 4, utf8_decode('PARALELO'), 1, 0, 'L');
        $pdf->Cell(40, 4, utf8_decode('TURNO'), 1, 0, 'L');
        $pdf->Cell(40, 4, utf8_decode('GESTIÓN'), 1, 1, 'L');

        $pdf->SetFont('Arial', '', 7.5);
        $pdf->Cell(40, 4.5, utf8_decode($ins['nivelNombre'] ?: '—'), 1, 0, 'L');
        $pdf->Cell(45, 4.5, utf8_decode($ins['cursoGrado'] ?: '—'), 1, 0, 'L');
        $pdf->Cell(35, 4.5, utf8_decode($ins['paraleloNombre'] ?: '—'), 1, 0, 'L');
        $pdf->Cell(40, 4.5, utf8_decode($ins['turnoNombre'] ?: '—'), 1, 0, 'L');
        $pdf->Cell(40, 4.5, utf8_decode($ins['gestionAnio'] ?: date('Y')), 1, 1, 'L');

        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(120, 4.5, utf8_decode('REGISTRADO POR (ADMINISTRATIVO): ' . ($ins['registrado'] ?: '—')), 1, 0, 'L');
        $pdf->Cell(80, 4.5, utf8_decode('FECHA REGISTRO: ' . $ins['fechaRegistro']), 1, 1, 'L');

        $pdf->Ln(14);

        // --- FIRMAS Y SELLOS ---
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->Cell(85, 3.5, utf8_decode('----------------------------------------------------'), 0, 0, 'C');
        $pdf->Cell(30, 3.5, '', 0, 0);
        $pdf->Cell(85, 3.5, utf8_decode('----------------------------------------------------'), 0, 1, 'C');

        $pdf->Cell(85, 3.5, utf8_decode('Firma de la/el Estudiante o Tutor(a)'), 0, 0, 'C');
        $pdf->Cell(30, 3.5, '', 0, 0);
        $pdf->Cell(85, 3.5, utf8_decode('Sello y Firma del Directora/or de la U.E.'), 0, 1, 'C');

        if (ob_get_length()) {
            ob_end_clean();
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Formulario_RUDE_' . ($ins['rude'] ?: $id) . '.pdf"');
        $pdf->Output('I', 'Formulario_RUDE_' . ($ins['rude'] ?: $id) . '.pdf');
        exit;

    // --------------------------------------------------------
    // GUARDAR (inscribir)
    // --------------------------------------------------------
    case 'guardar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $idEstudiante = (int) ($_POST['idEstudiante'] ?? 0);
        $idCurso      = (int) ($_POST['idCurso'] ?? 0);

        if ($idEstudiante <= 0 || $idCurso <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'Debe seleccionar un estudiante y un curso.');
        }

        // El administrativo que registra = usuario en sesión
        $idAdministrativo = $modelo->idAdministrativoDeUsuario((int) ($_SESSION['idUsuario'] ?? 0));
        if ($idAdministrativo <= 0) {
            redirigirConMensaje($urlModulo, 'danger', 'Tu usuario no está vinculado a un administrativo; no puedes registrar inscripciones.');
        }

        $r = $modelo->inscribir($idEstudiante, $idCurso, $idAdministrativo);
        if (!empty($r['ok'])) {
            $urlInscripcion = BASE_URL . 'index.php?c=inscripcion&modoBusqueda=curso&idCurso=' . $idCurso;
            redirigirConMensaje($urlInscripcion, 'success', $r['mensaje']);
        } else {
            redirigirConMensaje($urlModulo, 'danger', $r['mensaje']);
        }
        break;

    // --------------------------------------------------------
    // ELIMINAR (baja lógica)
    // --------------------------------------------------------
    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }
        $r = $modelo->eliminar((int) ($_POST['idInscripcion'] ?? 0));
        redirigirConMensaje($urlModulo, $r['ok'] ? 'success' : 'danger', $r['mensaje']);
        break;

    // --------------------------------------------------------
    // VISTA PRINCIPAL
    // --------------------------------------------------------
    case 'index':
    default:
        $buscar       = trim($_GET['q'] ?? '');
        $idCurso      = (int) ($_GET['idCurso'] ?? 0);
        $grado        = trim($_GET['grado'] ?? '');
        $paralelo     = trim($_GET['paralelo'] ?? '');
        $nivel        = trim($_GET['nivel'] ?? '');
        $turno        = trim($_GET['turno'] ?? '');
        $modoBusqueda = trim($_GET['modoBusqueda'] ?? 'estudiante');

        if ($modoBusqueda === 'estudiante') {
            $idCurso = 0;
        } else {
            $buscar = '';
        }

        $realizoBusqueda = ($buscar !== '' || $idCurso > 0 || $grado !== '' || $paralelo !== '' || $nivel !== '' || $turno !== '');

        if ($realizoBusqueda) {
            $inscripciones = $modelo->listar($buscar, $idCurso);
        } else {
            // Cargar lista completa para permitir búsqueda instantánea JS desde la primera letra tipeada
            $inscripciones = $modelo->listar('', 0);
        }

        $estudiantes  = $modelo->listarEstudiantes();
        $cursos       = $modelo->listarCursos();

        $cursoSeleccionado = null;
        if ($idCurso > 0 && !empty($cursos)) {
            foreach ($cursos as $c) {
                if ((int) $c['idCurso'] === $idCurso) {
                    $cursoSeleccionado = $c;
                    break;
                }
            }
        }

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vinscripcion.php';
        break;
}
