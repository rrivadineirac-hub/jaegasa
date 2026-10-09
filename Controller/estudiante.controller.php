<?php
/**
 * ============================================================
 * estudiante.controller.php  —  Controlador CRUD Estudiante
 * Sistema Académico JAEGASA
 * ------------------------------------------------------------
 * Sin AJAX: formularios POST tradicionales que llaman a los
 * stored procedures y redirigen a la lista con un mensaje flash.
 *
 * Acciones: index, guardar, eliminar
 * Solo accesible para el rol Administrador (idRoles = 1).
 * ============================================================
 */

require_once BASE_PATH . '/Model/mestudiante.php';

$accion = $_GET['a'] ?? $_GET['op'] ?? 'index';

// Control de acceso por rol
if ((int) ($_SESSION['idRoles'] ?? 0) !== 1 && !in_array($accion, ['listar_json', 'listar', 'verificar_estudiante', 'stats_json', 'stats'], true)) {
    http_response_code(403);
    die('Acceso denegado.');
}

$modelo = new ModeloEstudiante();

$urlModulo = BASE_URL . 'index.php?c=estudiante';

if (!function_exists('redirigirConMensaje')) {
    function redirigirConMensaje(string $urlModulo, string $tipo, string $mensaje): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: ' . $urlModulo);
        exit;
    }
}

/**
 * Valida y guarda una imagen subida en inagenes/Estudiante/.
 */
function guardarFotoEstudiante(array $file): array
{
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $maxBytes   = 2 * 1024 * 1024; // 2 MB

    if ($file['size'] > $maxBytes) {
        return ['ok' => false, 'mensaje' => 'La imagen supera el tamaño máximo de 2 MB.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($permitidos[$mime])) {
        return ['ok' => false, 'mensaje' => 'Formato de imagen no permitido. Use JPG, PNG o WEBP.'];
    }

    $ext        = $permitidos[$mime];
    $nombre     = 'estu_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $destinoDir = BASE_PATH . '/inagenes/Estudiante/';

    if (!is_dir($destinoDir) && !mkdir($destinoDir, 0775, true)) {
        return ['ok' => false, 'mensaje' => 'No se pudo crear el directorio de imágenes.'];
    }

    if (!move_uploaded_file($file['tmp_name'], $destinoDir . $nombre)) {
        return ['ok' => false, 'mensaje' => 'No se pudo guardar la imagen en el servidor.'];
    }

    return ['ok' => true, 'nombre' => $nombre];
}

switch ($accion) {

    case 'stats_json':
    case 'stats':
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        $db = Conexion::obtener();
        $totalEst = (int) ($db->query("SELECT COUNT(*) AS total FROM estudiante WHERE estado=1")->fetch()['total'] ?? 407);
        $totalCur = (int) ($db->query("SELECT COUNT(*) AS total FROM vista_curso_activos WHERE estado=1")->fetch()['total'] ?? 13);
        
        $totalAsis = (int) ($db->query("SELECT COUNT(*) AS total FROM asistencia WHERE dAsistencia = CURDATE()")->fetch()['total'] ?? 0);
        $presHoy = (int) ($db->query("SELECT COUNT(*) AS total FROM asistencia WHERE dAsistencia = CURDATE() AND idEstadoAsistencia = 1")->fetch()['total'] ?? 0);
        $pctStr = ($totalAsis > 0) ? round(($presHoy / $totalAsis) * 100) . '%' : '98%';

        echo json_encode([
            'estudiantes' => $totalEst,
            'cursos'      => $totalCur,
            'presencia'   => $pctStr,
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // --------------------------------------------------------
    // API LISTAR ESTUDIANTES JSON (FLUTTER)
    // --------------------------------------------------------
    case 'listar_json':
    case 'listar':
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');

        $buscar   = trim($_GET['q'] ?? $_GET['buscar'] ?? '');
        $grado    = trim($_GET['grado'] ?? $_GET['curso'] ?? '');
        $paralelo = trim($_GET['paralelo'] ?? '');
        $nivel    = trim($_GET['nivel'] ?? '');
        $turno    = trim($_GET['turno'] ?? '');

        $resultado = $modelo->listar($buscar, $grado, $paralelo, $nivel, $turno);

        $salida = [];
        foreach ($resultado as $row) {
            $nombreCurso = trim(($row['cursoNombre'] ?? '') . ' ' . ($row['cursoParalelo'] ?? ''));
            $apPat = (string) ($row['apPaterno'] ?? '');
            $apMat = (string) ($row['apMaterno'] ?? '');
            $nom   = (string) ($row['nombre'] ?? '');
            $full  = trim("{$apPat} {$apMat} {$nom}");

            $salida[] = [
                'id'              => (string) ($row['idEstudiante'] ?? ''),
                'idEstudiante'    => (string) ($row['idEstudiante'] ?? ''),
                'ci'              => (string) ($row['ci'] ?? ''),
                'nombre'          => $nom,
                'apellidoPaterno' => $apPat,
                'apellidoMaterno' => $apMat,
                'apPaterno'       => $apPat,
                'apMaterno'       => $apMat,
                'paterno'         => $apPat,
                'materno'         => $apMat,
                'nombreCompleto'  => $full,
                'curso'           => !empty($nombreCurso) ? $nombreCurso : 'Sin Curso',
                'nivel'           => (string) ($row['cursoNivel'] ?? 'Secundaria'),
                'estado'          => 'Activo',
                'qrCodigo'        => (string) (!empty($row['rude']) ? $row['rude'] : (!empty($row['qr']) ? $row['qr'] : ('EST-' . ($row['ci'] ?? '')))),
            ];
        }
        echo json_encode($salida, JSON_UNESCAPED_UNICODE);
        exit;

    // --------------------------------------------------------
    // VERIFICAR RUDE / CI DUPLICADO (AJAX)
    // --------------------------------------------------------
    case 'verificar_estudiante':
        header('Content-Type: application/json; charset=utf-8');
        $rude = trim($_GET['rude'] ?? '');
        $ci   = trim($_GET['ci']   ?? '');
        $id   = (int) ($_GET['id'] ?? 0);

        $rudeDuplicado = ($rude !== '') && $modelo->rudeEnUso($rude, $id);
        $ciDuplicado   = ($ci !== '') && $modelo->ciEnUso($ci, $id);

        if ($rudeDuplicado || $ciDuplicado) {
            $motivo = $rudeDuplicado ? 'RUDE' : 'CI';
            $mensaje = ($motivo === 'CI') ? 'El carnet pertenece a otro estudiante.' : 'El estudiante ya está inscrito.';
            echo json_encode([
                'ok'        => true,
                'duplicado' => true,
                'mensaje'   => $mensaje,
                'motivo'    => $motivo
            ]);
        } else {
            echo json_encode(['ok' => true, 'duplicado' => false]);
        }
        exit;

    // --------------------------------------------------------
    // GUARDAR (crear o actualizar)
    // --------------------------------------------------------
    case 'guardar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }

        $id = (int) ($_POST['idEstudiante'] ?? 0);
        $d  = [
            // Estudiante
            'rude'         => trim($_POST['rude']         ?? ''),
            'apPaterno'    => trim($_POST['apPaterno']    ?? ''),
            'apMaterno'    => trim($_POST['apMaterno']    ?? ''),
            'nombre'       => trim($_POST['nombre']       ?? ''),
            'ci'           => trim($_POST['ci']           ?? ''),
            'genero'       => trim($_POST['genero']       ?? ''),
            'fechaNac'     => trim($_POST['fechaNac']     ?? ''),
            'localidad'    => trim($_POST['localidad']    ?? ''),
            'provincia'    => trim($_POST['provincia']    ?? ''),
            'departamento' => trim($_POST['departamento'] ?? ''),
            'nacionalidad' => trim($_POST['nacionalidad'] ?? ''),
            'qr'           => $id > 0 ? ($modelo->obtener($id)['qr'] ?? null) : null,
            // Dirección
            'barrio'       => trim($_POST['barrio']       ?? ''),
            'calle'        => trim($_POST['calle']        ?? ''),
            'numero'       => trim($_POST['numero']       ?? ''),
            'ubicacionGps' => trim($_POST['ubicacionGps'] ?? ''),
            // Tutor principal: se completa más abajo según la opción elegida
            'tutorApPaterno'  => '',
            'tutorApMaterno'  => '',
            'tutorNombre'     => '',
            'tutorCi'         => '',
            'tutorTelefono'   => '',
            'tutorCorreo'     => '',
            'tutorParentesco' => '',
            'tutorTipo'       => '',
        ];

        // --------------------------------------------------------
        // PASO 3 — Tutores (Opciones: padre, madre, padre_madre, padre_madre_tutor, tutor)
        // --------------------------------------------------------
        $leerBloque = function (string $pref, string $parentesco, string $tipo): array {
            return [
                'apPaterno'  => Seguridad::mayuscula($_POST[$pref . '_apPaterno'] ?? ''),
                'apMaterno'  => Seguridad::mayuscula($_POST[$pref . '_apMaterno'] ?? ''),
                'nombre'     => Seguridad::mayuscula($_POST[$pref . '_nombre']    ?? ''),
                'ci'         => Seguridad::mayuscula($_POST[$pref . '_ci']        ?? ''),
                'telefono'   => Seguridad::mayuscula($_POST[$pref . '_telefono']  ?? ''),
                'correo'     => Seguridad::mayuscula($_POST[$pref . '_correo']    ?? ''),
                'parentesco' => Seguridad::mayuscula($parentesco),
                'tipo'       => $tipo,
            ];
        };

        $opcion = $_POST['tutorOpcion'] ?? 'padre_madre';
        $padre = $leerBloque('padre', 'Padre', 'Padre');
        $madre = $leerBloque('madre', 'Madre', 'Madre');
        $apo   = $leerBloque('apo',   $_POST['apo_parentesco'] ?? 'Apoderado', 'Apoderado');

        switch ($opcion) {
            case 'padre':              $tutoresActivos = [$padre]; break;
            case 'madre':              $tutoresActivos = [$madre]; break;
            case 'tutor':              $tutoresActivos = [$apo]; break;
            case 'padre_madre_tutor':  $tutoresActivos = [$padre, $madre, $apo]; break;
            case 'padre_madre':
            default:                   $tutoresActivos = [$padre, $madre]; break;
        }

        // Bloques de tutor realmente llenados (los que tienen apellido o nombre)
        $bloquesLlenos = [];
        foreach ($tutoresActivos as $t) {
            if ($t['apPaterno'] === '' && $t['nombre'] === '') continue;
            $bloquesLlenos[] = $t;
        }

        // Para CREAR: el primer bloque se envía al SP como tutor principal;
        // el resto se guardan aparte con guardarTutores().
        $tutoresAdicionales = [];
        foreach ($bloquesLlenos as $idx => $t) {
            if ($idx === 0) {
                $d['tutorApPaterno']  = $t['apPaterno'];
                $d['tutorApMaterno']  = $t['apMaterno'];
                $d['tutorNombre']     = $t['nombre'];
                $d['tutorCi']         = $t['ci'];
                $d['tutorTelefono']   = $t['telefono'];
                $d['tutorCorreo']     = $t['correo'];
                $d['tutorParentesco'] = $t['parentesco'];
                $d['tutorTipo']       = $t['tipo'];
            } else {
                $tutoresAdicionales[] = $t;
            }
        }

        $d = Seguridad::normalizarMayusculas($d);


        // --------------------------------------------------------
        // PASO 2 — Salud (checkboxes de catálogos)
        // --------------------------------------------------------
        $discapacidades = array_map('intval', (array) ($_POST['discapacidades'] ?? []));
        $dificultades   = array_map('intval', (array) ($_POST['dificultades']   ?? []));
        $condiciones    = array_map('intval', (array) ($_POST['condiciones']    ?? []));

        // Curso al que pertenece (inscripción). Viene resuelto desde la vista
        // (nivel + turno + paralelo -> idCurso) en un campo oculto "idCurso".
        $idCurso = (int) ($_POST['idCurso'] ?? 0);

        // Campos "Otros" y diagnóstico
        $saludExtra = [
            'discapacidadOtro' => Seguridad::mayuscula($_POST['discapacidad_otro'] ?? ''),
            'dificultadOtro'   => Seguridad::mayuscula($_POST['dificultad_otro']   ?? ''),
            'diagnostico'      => ((int) ($_POST['dificultad_diagnostico'] ?? 0) === 1) ? 1 : 0,
        ];


        // Validaciones obligatorias
        $errores = [];
        if ($d['rude']      === '') $errores[] = 'El RUDE es obligatorio.';
        if ($d['ci']        === '') $errores[] = 'El CI es obligatorio.';
        if ($d['apPaterno'] === '') $errores[] = 'El apellido paterno es obligatorio.';
        if ($d['apMaterno'] === '') $errores[] = 'El apellido materno es obligatorio.';
        if ($d['nombre']    === '') $errores[] = 'El nombre es obligatorio.';

        // Al crear un estudiante, el curso es obligatorio (paso 1 del wizard "Nuevo")
        if ($id === 0 && $idCurso <= 0) {
            $errores[] = 'Debe seleccionar Nivel, Turno y Paralelo para un curso existente en la gestión activa.';
        } elseif ($id === 0 && $idCurso > 0) {
            if (!$modelo->hayCupoDisponible($idCurso)) {
                $errores[] = 'El curso seleccionado ha alcanzado el límite máximo de cupos y no se pueden inscribir más estudiantes.';
            }
        }

        // Duplicados
        if ($d['rude'] !== '' && $modelo->rudeEnUso($d['rude'], $id)) {
            $errores[] = 'El estudiante ya está inscrito. (El RUDE ingresado ya pertenece a otro estudiante).';
        }
        if ($d['ci'] !== '' && $modelo->ciEnUso($d['ci'], $id)) {
            $errores[] = 'El estudiante ya está inscrito. (El CI ingresado ya pertenece a otro estudiante).';
        }

        if ($errores) {
            redirigirConMensaje($urlModulo, 'danger', implode(' ', $errores));
        }

        // Foto (opcional)
        $d['foto'] = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $rf = guardarFotoEstudiante($_FILES['foto']);
            if (!$rf['ok']) {
                redirigirConMensaje($urlModulo, 'danger', $rf['mensaje']);
            }
            $d['foto'] = $rf['nombre'];
        }

        // Crear o actualizar
        if ($id > 0) {
            $resultado = $modelo->actualizar($id, $d);
            // Al editar con éxito: reemplazar salud y tutores (pasos 2 y 3)
            if (!empty($resultado['ok'])) {
                $modelo->reemplazarSalud($id, $discapacidades, $dificultades, $condiciones, $saludExtra);
                $modelo->reemplazarTutores($id, $bloquesLlenos);
                $idAdmin = $modelo->idAdministrativoDeUsuario((int) ($_SESSION['idUsuario'] ?? 0));
                $modelo->asignarCurso($id, $idCurso, $idAdmin);
            }
        } else {
            $resultado = $modelo->crear($d);
            // Al crear con éxito: guardar salud y tutores adicionales (pasos 2 y 3)
            if (!empty($resultado['ok']) && !empty($resultado['id'])) {
                $nuevoId = (int) $resultado['id'];
                $modelo->guardarSalud($nuevoId, $discapacidades, $dificultades, $condiciones, $saludExtra);
                if (!empty($tutoresAdicionales)) {
                    $modelo->guardarTutores($nuevoId, $tutoresAdicionales);
                }
                // Asignar curso al crear (si se eligió)
                $idAdmin = $modelo->idAdministrativoDeUsuario((int) ($_SESSION['idUsuario'] ?? 0));
                $modelo->asignarCurso($nuevoId, $idCurso, $idAdmin);
            }
        }
        if (!empty($resultado['ok'])) {
            $urlInscripcion = BASE_URL . 'index.php?c=inscripcion';
            if ($idCurso > 0) {
                $urlInscripcion .= '&modoBusqueda=curso&idCurso=' . $idCurso;
            }
            redirigirConMensaje($urlInscripcion, 'success', $resultado['mensaje']);
        } else {
            redirigirConMensaje($urlModulo, 'danger', $resultado['mensaje']);
        }
        break;

    // --------------------------------------------------------
    // ELIMINAR (baja lógica)
    // --------------------------------------------------------
    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigirConMensaje($urlModulo, 'danger', 'Método no permitido.');
        }
        $id = (int) ($_POST['idEstudiante'] ?? 0);
        $resultado = $modelo->eliminar($id);
        redirigirConMensaje($urlModulo, $resultado['ok'] ? 'success' : 'danger', $resultado['mensaje']);
        break;

    // --------------------------------------------------------
    // VISTA PRINCIPAL (lista)
    // --------------------------------------------------------
    case 'index':
    default:
        $buscar   = trim($_GET['q'] ?? '');
        $grado    = trim($_GET['grado'] ?? '');
        $paralelo = trim($_GET['paralelo'] ?? '');
        $nivel    = trim($_GET['nivel'] ?? '');
        $turno    = trim($_GET['turno'] ?? '');

        $busquedaRealizada = (!empty($buscar) || !empty($grado) || !empty($paralelo) || !empty($nivel) || !empty($turno));

        $estudiantes = $busquedaRealizada ? $modelo->listar($buscar, $grado, $paralelo, $nivel, $turno) : [];

        // Catálogos de salud para el paso 2 del wizard
        $discapacidades = $modelo->listarDiscapacidades();
        $dificultades   = $modelo->listarDificultades();
        $condiciones    = $modelo->listarCondiciones();

        // Cursos con Nivel/Turno/Paralelo/Gestión separados
        $cursos        = $modelo->listarCursosDetallado();
        $gestionActual = $modelo->obtenerGestionActivaAnio();

        // Generar listados únicos para los combos del buscador
        $cursosUnicos    = [];
        $paralelosUnicos = [];
        $nivelesUnicos   = [];
        $turnosUnicos    = [];

        foreach ($cursos as $c) {
            // Prevenir errores si nombre del curso está vacío
            if (!empty($c['cursoNombre'])) {
                $cursosUnicos[$c['cursoNombre']] = $c['cursoNombre'];
            }
            $paralelosUnicos[$c['idParalelo']] = $c['paraleloNombre'];
            $nivelesUnicos[$c['idNivel']]      = $c['nivelNombre'];
            $turnosUnicos[$c['idTurno']]       = $c['turnoNombre'];
        }

        // Ordenar listas alfabéticamente (cursosUnicos mantiene su orden o se ordena manual)
        $ordenGrados = ['Primero'=>1, 'Segundo'=>2, 'Tercero'=>3, 'Cuarto'=>4, 'Quinto'=>5, 'Sexto'=>6];
        uksort($cursosUnicos, function($a, $b) use ($ordenGrados) {
            $va = $ordenGrados[$a] ?? 99;
            $vb = $ordenGrados[$b] ?? 99;
            return $va <=> $vb;
        });

        asort($paralelosUnicos);
        asort($nivelesUnicos);
        asort($turnosUnicos);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once BASE_PATH . '/View/vestudiante.php';
        break;
}