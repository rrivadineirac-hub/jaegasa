-- ================================================================================
-- SCRIPT DE CONSULTAS Y REPORTES PARA MYSQL WORKBENCH
-- Sistema Académico JAEGASA
-- ================================================================================
-- Descripción: Este documento contiene la colección completa de reportes y 
-- consultas analíticas del Sistema Académico JAEGASA, listo para ejecutarse 
-- directamente en MySQL Workbench.
-- 
-- Instrucciones de uso en MySQL Workbench:
-- 1. Abra este archivo en MySQL Workbench (File -> Open SQL Script).
-- 2. Seleccione la base de datos `jaegasa_db_fluter`.
-- 3. Para ejecutar un reporte específico, ajuste los parámetros al inicio 
--    de la sección (ej. SET @idGestion = 1;) y presione Ctrl + Enter sobre la consulta.
-- ================================================================================

USE jaegasa_db_fluter;


-- ================================================================================
-- REPORTE 1: DASHBOARD DE MÉTRICAS INSTITUCIONALES Y CONTEO GENERAL
-- ================================================================================
-- DESCRIPCIÓN: Muestra un resumen cuantitativo de la institución (total de 
-- estudiantes activos, docentes, administrativos, tutores, cursos e inscripciones).
-- USO: Para directivos y administradores del sistema.
-- ================================================================================

SELECT 
    (SELECT COUNT(*) FROM Estudiante WHERE estado = 1)     AS Total_Estudiantes_Activos,
    (SELECT COUNT(*) FROM Docente WHERE estado = 1)        AS Total_Docentes_Activos,
    (SELECT COUNT(*) FROM Administrativo WHERE estado = 1) AS Total_Administrativos_Activos,
    (SELECT COUNT(*) FROM Tutor WHERE estado = 1)          AS Total_Tutores_Registrados,
    (SELECT COUNT(*) FROM Curso WHERE estado = 1)          AS Total_Cursos_Activos,
    (SELECT COUNT(*) FROM Inscripcion WHERE estado = 1)    AS Total_Inscripciones_Activas;


-- ================================================================================
-- REPORTE 2: PADRÓN GENERAL DE ESTUDIANTES (ROSTER COMPLETO)
-- ================================================================================
-- DESCRIPCIÓN: Lista detallada de todos los estudiantes registrados en el sistema,
-- incluyendo RUDE, C.I., nombres completos, género, fecha de nacimiento, edad 
-- aproximada, nacionalidad, dirección de residencia y tutor responsable.
-- ================================================================================

SELECT 
    e.idEstudiante,
    e.rude AS RUDE,
    e.ci AS CI,
    e.apPaterno AS Apellido_Paterno,
    e.apMaterno AS Apellido_Materno,
    e.nombre AS Nombres,
    CONCAT(e.apPaterno, ' ', e.apMaterno, ' ', e.nombre) AS Nombre_Completo,
    CASE e.genero 
        WHEN 'M' THEN 'Masculino' 
        WHEN 'F' THEN 'Femenino' 
        ELSE e.genero 
    END AS Genero,
    e.fechaNacimiento AS Fecha_Nacimiento,
    TIMESTAMPDIFF(YEAR, e.fechaNacimiento, CURDATE()) AS Edad,
    COALESCE(e.nacionalidad, 'Boliviana') AS Nacionalidad,
    COALESCE(e.departamento, 'Santa Cruz') AS Departamento_Nacimiento,
    CONCAT_WS(', ', dir.barrio, dir.calle, dir.numero) AS Dirección_Domicilio,
    CONCAT_WS(' ', tut.apPaterno, tut.apMaterno, tut.nombre) AS Nombre_Tutor,
    tut.telefono AS Telefono_Tutor,
    tut.parentesco AS Parentesco_Tutor
FROM Estudiante e
LEFT JOIN Direccion dir ON dir.idDireccion = e.idDireccion
LEFT JOIN Estudiante_Tutor et ON et.idEstudiante = e.idEstudiante
LEFT JOIN Tutor tut ON tut.idTutor = et.idTutor AND tut.estado = 1
WHERE e.estado = 1
ORDER BY e.apPaterno ASC, e.apMaterno ASC, e.nombre ASC;


-- ================================================================================
-- REPORTE 3: LISTA DE ESTUDIANTES INSCRITOS POR CURSO Y GESTIÓN ESCOLAR
-- ================================================================================
-- DESCRIPCIÓN: Genera la nómina oficial de alumnos matriculados por curso, 
-- paralelo, turno, nivel y gestión académica específica.
-- PARÁMETROS: Ajustar @idGestion (opcional 0 para todas) y @idCurso (opcional 0 para todos).
-- ================================================================================

SET @idGestion = 0; -- Colocar ID de la gestión (ej. 1) o 0 para ver todas las gestiones
SET @idCurso   = 0; -- Colocar ID del curso o 0 para ver todos

SELECT 
    g.anio AS Gestion_Escolar,
    n.nombre AS Nivel_Educativo,
    c.nombre AS Grado_Curso,
    p.nombre AS Paralelo,
    t.nombre AS Turno,
    CONCAT(c.nombre, ' ', p.nombre, ' de ', n.nombre) AS Curso_Completo,
    e.rude AS RUDE,
    e.ci AS CI,
    e.apPaterno AS Apellido_Paterno,
    e.apMaterno AS Apellido_Materno,
    e.nombre AS Nombres,
    CONCAT(e.apPaterno, ' ', e.apMaterno, ' ', e.nombre) AS Estudiante,
    i.fechaRegistro AS Fecha_Inscripcion,
    CONCAT(adm.apPaterno, ' ', adm.nombre) AS Registrado_Por
FROM Inscripcion i
INNER JOIN Estudiante e    ON e.idEstudiante = i.idEstudiante
INNER JOIN Curso c         ON c.idCurso      = i.idCurso
INNER JOIN Gestion g       ON g.idGestion    = c.idGestion
INNER JOIN Nivel n         ON n.idNivel      = c.idNivel
INNER JOIN Turno t         ON t.idTurno      = c.idTurno
INNER JOIN Paralelo p      ON p.idParalelo   = c.idParalelo
LEFT JOIN Administrativo adm ON adm.idAdministrativo = i.idAdministrativo
WHERE i.estado = 1 
  AND e.estado = 1
  AND (@idGestion = 0 OR c.idGestion = @idGestion)
  AND (@idCurso = 0 OR c.idCurso = @idCurso)
ORDER BY g.anio DESC, n.idNivel ASC, c.nombre ASC, p.nombre ASC, e.apPaterno ASC, e.apMaterno ASC;


-- ================================================================================
-- REPORTE 4: PARTE DIARIO DE ASISTENCIA GENERAL DE ALUMNOS
-- ================================================================================
-- DESCRIPCIÓN: Muestra la asistencia registrada de los estudiantes para una 
-- fecha determinada, indicando si asistió (A), faltó (F), tuvo retraso (R) o licencia (L).
-- PARÁMETROS: Cambiar @fechaReporte por la fecha deseada ('YYYY-MM-DD').
-- ================================================================================

SET @fechaReporte = CURDATE(); -- O especificar '2026-03-15'

SELECT 
    ag.fecha AS Fecha,
    g.anio AS Gestion,
    CONCAT(c.nombre, ' ', p.nombre, ' - ', n.nombre) AS Curso,
    e.rude AS RUDE,
    CONCAT(e.apPaterno, ' ', e.apMaterno, ' ', e.nombre) AS Estudiante,
    CASE ag.estado
        WHEN 'A' THEN 'PRESENTE'
        WHEN 'R' THEN 'RETRASO'
        WHEN 'F' THEN 'FALTA'
        WHEN 'L' THEN 'LICENCIA'
        ELSE ag.estado
    END AS Estado_Asistencia,
    COALESCE(TIME_FORMAT(ag.horaLlegada, '%H:%i:%s'), '-') AS Hora_Llegada,
    COALESCE(ag.observacion, '-') AS Observaciones,
    IF(ag.notificado = 1, 'SI', 'NO') AS Notificado_Tutor
FROM asistencia_general ag
INNER JOIN Inscripcion i ON i.idInscripcion = ag.idInscripcion
INNER JOIN Estudiante e   ON e.idEstudiante  = i.idEstudiante
INNER JOIN Curso c        ON c.idCurso       = i.idCurso
INNER JOIN Gestion g      ON g.idGestion     = c.idGestion
INNER JOIN Nivel n        ON n.idNivel       = c.idNivel
INNER JOIN Paralelo p     ON p.idParalelo    = c.idParalelo
WHERE ag.fecha = @fechaReporte
ORDER BY c.nombre ASC, p.nombre ASC, e.apPaterno ASC, e.apMaterno ASC;


-- ================================================================================
-- REPORTE 5: HISTORIAL ACUMULADO Y PORCENTAJE DE ASISTENCIA POR ALUMNO
-- ================================================================================
-- DESCRIPCIÓN: Resumen de asistencias, atrasos, faltas y licencias acumuladas 
-- por cada estudiante en una gestión escolar determinada.
-- PARÁMETROS: Ajustar @anioGestion (ej. 2026).
-- ================================================================================

SET @anioGestion = 2026;

SELECT 
    e.rude AS RUDE,
    CONCAT(e.apPaterno, ' ', e.apMaterno, ' ', e.nombre) AS Estudiante,
    CONCAT(c.nombre, ' ', p.nombre, ' (', n.nombre, ')') AS Curso,
    COUNT(ag.idAsistenciaGeneral) AS Total_Dias_Evaluados,
    SUM(IF(ag.estado = 'A', 1, 0)) AS Total_Presentes,
    SUM(IF(ag.estado = 'R', 1, 0)) AS Total_Retrasos,
    SUM(IF(ag.estado = 'F', 1, 0)) AS Total_Faltas,
    SUM(IF(ag.estado = 'L', 1, 0)) AS Total_Licencias,
    ROUND((SUM(IF(ag.estado IN ('A', 'R'), 1, 0)) / NULLIF(COUNT(ag.idAsistenciaGeneral), 0)) * 100, 2) AS Porcentaje_Puntualidad
FROM Inscripcion i
INNER JOIN Estudiante e ON e.idEstudiante = i.idEstudiante
INNER JOIN Curso c      ON c.idCurso      = i.idCurso
INNER JOIN Gestion g    ON g.idGestion    = c.idGestion
INNER JOIN Nivel n      ON n.idNivel      = c.idNivel
INNER JOIN Paralelo p   ON p.idParalelo   = c.idParalelo
LEFT JOIN asistencia_general ag ON ag.idInscripcion = i.idInscripcion
WHERE i.estado = 1 
  AND e.estado = 1
  AND g.anio   = @anioGestion
GROUP BY i.idInscripcion, e.rude, Estudiante, Curso
ORDER BY Total_Faltas DESC, Total_Retrasos DESC, e.apPaterno ASC;


-- ================================================================================
-- REPORTE 6: CARGA HORARIA Y PLANILLA DE ASIGNACIÓN DOCENTE
-- ================================================================================
-- DESCRIPCIÓN: Muestra la asignación oficial de materias a profesores, especificando 
-- el curso, nivel, paralelo, turno y datos del docente asignado.
-- PARÁMETROS: @anioGestion (ej. 2026).
-- ================================================================================

SET @anioGestion = 2026;

SELECT 
    g.anio AS Gestion,
    CONCAT(d.apPaterno, ' ', d.apMaterno, ' ', d.nombre) AS Profesor,
    d.ci AS CI_Docente,
    d.telefono AS Telefono_Contacto,
    d.correo AS Correo_Electronico,
    m.nombre AS Materia,
    m.sigla AS Sigla_Materia,
    CONCAT(c.nombre, ' ', p.nombre) AS Curso_Paralelo,
    n.nombre AS Nivel,
    t.nombre AS Turno
FROM asignacion_docente ad
INNER JOIN gestion g   ON ad.idGestion = g.idGestion
INNER JOIN docente d   ON ad.idDocente = d.idDocente
INNER JOIN materia m   ON ad.idMateria = m.idMateria
INNER JOIN curso c     ON ad.idCurso   = c.idCurso
INNER JOIN nivel n     ON c.idNivel    = n.idNivel
INNER JOIN paralelo p  ON c.idParalelo = p.idParalelo
INNER JOIN turno t     ON c.idTurno    = t.idTurno
WHERE ad.estado = 1 
  AND d.estado  = 1 
  AND g.anio    = @anioGestion
ORDER BY Profesor ASC, m.nombre ASC, c.nombre ASC;


-- ================================================================================
-- REPORTE 7: HORARIO GENERAL DE CLASES POR CURSO Y DÍA
-- ================================================================================
-- DESCRIPCIÓN: Reconstruye la grilla de horarios de clases asignada a un curso, 
-- detallando día de la semana, período horario, materia, docente y aula física.
-- PARÁMETROS: Definir @idCursoDeseado (ej. 1).
-- ================================================================================

SET @idCursoDeseado = 1;

SELECT 
    CONCAT(c.nombre, ' ', p.nombre, ' - ', n.nombre) AS Curso,
    hc.diaSemana AS Dia,
    per.numeroPeriodo AS Num_Periodo,
    CONCAT(TIME_FORMAT(per.horaInicio, '%H:%i'), ' - ', TIME_FORMAT(per.horaFin, '%H:%i')) AS Horario,
    m.nombre AS Materia,
    m.sigla AS Sigla,
    CONCAT(d.apPaterno, ' ', d.nombre) AS Profesor,
    COALESCE(a.nombre, 'Sin Aula Asignada') AS Aula_Ambiente
FROM horario_curso hc
INNER JOIN curso c     ON c.idCurso     = hc.idCurso
INNER JOIN paralelo p  ON p.idParalelo  = c.idParalelo
INNER JOIN nivel n     ON n.idNivel     = c.idNivel
INNER JOIN materia m   ON m.idMateria   = hc.idMateria
INNER JOIN docente d   ON d.idDocente   = hc.idDocente
INNER JOIN periodo per ON per.idPeriodo = hc.idPeriodo
LEFT JOIN aula a       ON a.idAula      = hc.idAula
WHERE hc.idCurso = @idCursoDeseado
ORDER BY FIELD(hc.diaSemana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'), per.numeroPeriodo ASC;


-- ================================================================================
-- REPORTE 8: PROGRAMACIÓN Y ESTADO DE LOS 3 TRIMESTRES ACADÉMICOS
-- ================================================================================
-- DESCRIPCIÓN: Muestra la configuración manual de inicio, fin, duración en días 
-- y estado (`Pendiente`, `En Curso`, `Cerrado`) de los 3 trimestres por gestión.
-- ================================================================================

SELECT 
    g.anio AS Gestion_Escolar,
    t.numero AS Num_Trimestre,
    t.nombre AS Trimestre,
    COALESCE(DATE_FORMAT(t.fechaInicio, '%d/%m/%Y'), 'No asignada') AS Fecha_Inicio,
    COALESCE(DATE_FORMAT(t.fechaFin, '%d/%m/%Y'), 'No asignada') AS Fecha_Fin,
    DATEDIFF(t.fechaFin, t.fechaInicio) + 1 AS Duracion_Dias_Lectivos,
    t.estadoTrimestre AS Estado_Actual,
    COALESCE(t.observacion, '-') AS Observaciones
FROM Trimestre t
INNER JOIN Gestion g ON g.idGestion = t.idGestion
WHERE t.estado = 1 AND g.estado = 1
ORDER BY g.anio DESC, t.numero ASC;


-- ================================================================================
-- REPORTE 9: MALLA CURRICULAR INSTITUCIONAL Y CARGA HORARIA SEMANAL
-- ================================================================================
-- DESCRIPCIÓN: Detalla la malla curricular aprobada por curso y nivel, indicando 
-- las materias que se imparten y la carga horaria semanal asignada.
-- ================================================================================

SELECT 
    g.anio AS Gestion,
    n.nombre AS Nivel,
    c.nombre AS Curso,
    p.nombre AS Paralelo,
    m.sigla AS Sigla_Materia,
    m.nombre AS Materia,
    mc.cargaHoraria AS Carga_Horaria_Semanal_Horas
FROM malla_curricular mc
INNER JOIN curso c     ON c.idCurso   = mc.idCurso
INNER JOIN gestion g   ON g.idGestion = c.idGestion
INNER JOIN nivel n     ON n.idNivel   = c.idNivel
INNER JOIN paralelo p  ON p.idParalelo = c.idParalelo
INNER JOIN materia m   ON m.idMateria = mc.idMateria
WHERE mc.estado = 1 AND c.estado = 1 AND m.estado = 1
ORDER BY g.anio DESC, n.idNivel ASC, c.nombre ASC, p.nombre ASC, m.nombre ASC;


-- ================================================================================
-- REPORTE 10: ASISTENCIA Y PUNTUALIDAD DEL PERSONAL DOCENTE
-- ================================================================================
-- DESCRIPCIÓN: Registro de marcado de asistencia diaria de los profesores, horas 
-- de ingreso y observaciones.
-- ================================================================================

SELECT 
    ad.fecha AS Fecha,
    d.ci AS CI,
    CONCAT(d.apPaterno, ' ', d.apMaterno, ' ', d.nombre) AS Profesor,
    CASE ad.estado
        WHEN 'A' THEN 'PRESENTE'
        WHEN 'R' THEN 'RETRASO'
        WHEN 'F' THEN 'FALTA'
        WHEN 'L' THEN 'LICENCIA'
        ELSE ad.estado
    END AS Estado_Asistencia,
    COALESCE(TIME_FORMAT(ad.horaLlegada, '%H:%i:%s'), '-') AS Hora_Marcado,
    COALESCE(ad.observacion, '-') AS Observaciones
FROM asistencia_docente ad
INNER JOIN docente d ON d.idDocente = ad.idDocente
WHERE d.estado = 1
ORDER BY ad.fecha DESC, d.apPaterno ASC, d.apMaterno ASC;


-- ================================================================================
-- REPORTE 11: DIRECTORIO COMPLETO DE TUTORES / PADRES Y SUS ESTUDIANTES A CARGO
-- ================================================================================
-- DESCRIPCIÓN: Relación de tutores registrados en el colegio con la lista de 
-- estudiantes a su cargo, teléfonos de contacto y tipo de parentesco.
-- ================================================================================

SELECT 
    t.idTutor,
    t.ci AS CI_Tutor,
    CONCAT(t.apPaterno, ' ', t.apMaterno, ' ', t.nombre) AS Nombre_Tutor,
    t.telefono AS Telefono_Contacto,
    t.correo AS Correo,
    t.parentesco AS Parentesco,
    e.rude AS RUDE_Estudiante,
    CONCAT(e.apPaterno, ' ', e.apMaterno, ' ', e.nombre) AS Estudiante_A_Cargo,
    COALESCE(CONCAT(c.nombre, ' ', p.nombre), 'Sin inscripción activa') AS Curso_Estudiante
FROM Tutor t
INNER JOIN Estudiante_Tutor et ON et.idTutor = t.idTutor
INNER JOIN Estudiante e ON e.idEstudiante = et.idEstudiante
LEFT JOIN Inscripcion i ON i.idEstudiante = e.idEstudiante AND i.estado = 1
LEFT JOIN Curso c ON c.idCurso = i.idCurso
LEFT JOIN Paralelo p ON p.idParalelo = c.idParalelo
WHERE t.estado = 1 AND e.estado = 1
ORDER BY t.apPaterno ASC, t.apMaterno ASC, e.apPaterno ASC;


-- ================================================================================
-- REPORTE 12: REGISTRO DE DISCAPACIDADES, DIFICULTADES Y CONDICIONES MÉDICAS
-- ================================================================================
-- DESCRIPCIÓN: Reporte de salud y necesidades educativas especiales para la 
-- atención psicopedagógica y médica de los alumnos.
-- ================================================================================

SELECT 
    e.rude AS RUDE,
    CONCAT(e.apPaterno, ' ', e.apMaterno, ' ', e.nombre) AS Estudiante,
    CONCAT(c.nombre, ' ', p.nombre) AS Curso,
    disc.tipo AS Tipo_Discapacidad,
    disc.descripcion AS Detalle_Discapacidad,
    dif.tipo AS Tipo_Dificultad_Aprendizaje,
    dif.descripcion AS Detalle_Dificultad,
    cond.descripcion AS Condicion_Medica_Alergia
FROM Estudiante e
LEFT JOIN Inscripcion i ON i.idEstudiante = e.idEstudiante AND i.estado = 1
LEFT JOIN Curso c ON c.idCurso = i.idCurso
LEFT JOIN Paralelo p ON p.idParalelo = c.idParalelo
LEFT JOIN discapacidad disc ON disc.idEstudiante = e.idEstudiante
LEFT JOIN dificultad dif     ON dif.idEstudiante  = e.idEstudiante
LEFT JOIN condicion cond     ON cond.idEstudiante = e.idEstudiante
WHERE e.estado = 1 
  AND (disc.idDiscapacidad IS NOT NULL OR dif.idDificultad IS NOT NULL OR cond.idCondicion IS NOT NULL)
ORDER BY e.apPaterno ASC, e.apMaterno ASC;


-- ================================================================================
-- REPORTE 13: AUDITORÍA DE CREDENCIALES Y CÓDIGOS QR DE ESTUDIANTES
-- ================================================================================
-- DESCRIPCIÓN: Lista de control para la impresión de credenciales escolares con 
-- código QR único asignado para el marcado digital.
-- ================================================================================

SELECT 
    e.idEstudiante,
    e.rude AS RUDE,
    e.ci AS CI,
    CONCAT(e.apPaterno, ' ', e.apMaterno, ' ', e.nombre) AS Estudiante,
    e.qr AS Codigo_QR_Generado,
    IF(e.qr IS NOT NULL AND e.qr != '', 'GENERADO', 'PENDIENTE') AS Estado_QR,
    COALESCE(CONCAT(c.nombre, ' ', p.nombre, ' - ', n.nombre), 'Sin Curso') AS Curso_Actual
FROM Estudiante e
LEFT JOIN Inscripcion i ON i.idInscripcion = (
    SELECT idInscripcion FROM Inscripcion WHERE idEstudiante = e.idEstudiante AND estado = 1 ORDER BY idInscripcion DESC LIMIT 1
)
LEFT JOIN Curso c ON c.idCurso = i.idCurso
LEFT JOIN Nivel n ON n.idNivel = c.idNivel
LEFT JOIN Paralelo p ON p.idParalelo = c.idParalelo
WHERE e.estado = 1
ORDER BY c.idCurso ASC, e.apPaterno ASC, e.apMaterno ASC;

-- ================================================================================
-- FIN DEL SCRIPT DE REPORTES DE JAEGASA
-- ================================================================================
