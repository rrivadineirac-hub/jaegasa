USE jaegasa_db_fluter;

DELIMITER $$

-- 1. InsertarEstudiante
DROP PROCEDURE IF EXISTS InsertarEstudiante$$
CREATE PROCEDURE InsertarEstudiante(
    IN p_barrio         VARCHAR(100),
    IN p_localidad      VARCHAR(100),
    IN p_calle          VARCHAR(150),
    IN p_numero         VARCHAR(20),
    IN p_ubicacionGps   VARCHAR(100),
    IN p_rude           VARCHAR(30),
    IN p_apPaterno      VARCHAR(80),
    IN p_apMaterno      VARCHAR(80),
    IN p_nombre         VARCHAR(80),
    IN p_genero         CHAR(1),
    IN p_ci             VARCHAR(20),
    IN p_fechaNac       DATE,
    IN p_provincia      VARCHAR(100),
    IN p_departamento   VARCHAR(100),
    IN p_nacionalidad   VARCHAR(80),
    IN p_qr             VARCHAR(100),
    IN p_tutorApPaterno VARCHAR(80),
    IN p_tutorApMaterno VARCHAR(80),
    IN p_tutorNombre    VARCHAR(80),
    IN p_tutorCi        VARCHAR(20),
    IN p_tutorTelefono  VARCHAR(20),
    IN p_tutorCorreo    VARCHAR(150),
    IN p_tutorParentesco VARCHAR(50),
    IN p_tutorTipo      VARCHAR(50),
    OUT p_idEstudiante  INT,
    OUT p_idTutor       INT,
    OUT p_mensaje       VARCHAR(255)
)
BEGIN
    DECLARE v_idDireccion INT DEFAULT NULL;
    DECLARE v_idTutor     INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_idEstudiante = 0;
        SET p_idTutor = 0;
        SET p_mensaje = 'ERROR: No se pudo crear el estudiante.';
    END;

    SET p_idEstudiante = 0;
    SET p_idTutor = 0;
    SET p_mensaje = '';

    START TRANSACTION;

    INSERT INTO Direccion(barrio, localidad, calle, numero, ubicacion_gps, estado)
    VALUES(p_barrio, p_localidad, p_calle, p_numero, p_ubicacionGps, p_departamento);
    SET v_idDireccion = LAST_INSERT_ID();

    IF p_tutorCi IS NOT NULL AND p_tutorCi <> '' THEN
        SELECT idTutor INTO v_idTutor
        FROM Tutor WHERE ci = p_tutorCi AND estado = 1 LIMIT 1;

        IF v_idTutor IS NULL THEN
            INSERT INTO Tutor(
                apPaterno, apMaterno, nombre, ci,
                telefono, correo, parentesco, tipo
            ) VALUES(
                p_tutorApPaterno, p_tutorApMaterno, p_tutorNombre, p_tutorCi,
                p_tutorTelefono, p_tutorCorreo, p_tutorParentesco, p_tutorTipo
            );
            SET v_idTutor = LAST_INSERT_ID();
        END IF;
    END IF;

    INSERT INTO Estudiante(
        idDireccion, rude, apPaterno, apMaterno, nombre,
        genero, ci, fechaNacimiento,
        provincia, departamento, nacionalidad, qr
    ) VALUES(
        v_idDireccion, p_rude, p_apPaterno, p_apMaterno, p_nombre,
        p_genero, p_ci, p_fechaNac,
        p_provincia, p_departamento, p_nacionalidad, p_qr
    );
    SET p_idEstudiante = LAST_INSERT_ID();
    SET p_idTutor = v_idTutor;

    IF v_idTutor IS NOT NULL THEN
        INSERT IGNORE INTO Estudiante_Tutor(idEstudiante, idTutor)
        VALUES(p_idEstudiante, v_idTutor);
    END IF;

    COMMIT;

    SET p_mensaje = CONCAT('OK: Estudiante creado. idEstudiante=', p_idEstudiante, ', idTutor=', IFNULL(v_idTutor, 0));
END$$

-- 2. EditarEstudiante
DROP PROCEDURE IF EXISTS EditarEstudiante$$
CREATE PROCEDURE EditarEstudiante(
    IN p_idEstudiante   INT,
    IN p_barrio         VARCHAR(100),
    IN p_localidad      VARCHAR(100),
    IN p_calle          VARCHAR(150),
    IN p_numero         VARCHAR(20),
    IN p_ubicacionGps   VARCHAR(100),
    IN p_rude           VARCHAR(30),
    IN p_apPaterno      VARCHAR(80),
    IN p_apMaterno      VARCHAR(80),
    IN p_nombre         VARCHAR(80),
    IN p_genero         CHAR(1),
    IN p_ci             VARCHAR(20),
    IN p_fechaNac       DATE,
    IN p_provincia      VARCHAR(100),
    IN p_departamento   VARCHAR(100),
    IN p_nacionalidad   VARCHAR(80),
    IN p_qr             VARCHAR(100),
    OUT p_mensaje       VARCHAR(255)
)
BEGIN
    DECLARE v_idDireccion INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_mensaje = 'ERROR: No se pudo editar el estudiante.';
    END;

    START TRANSACTION;

    SELECT idDireccion INTO v_idDireccion
    FROM Estudiante WHERE idEstudiante = p_idEstudiante;

    IF v_idDireccion IS NOT NULL THEN
        UPDATE Direccion SET
            barrio        = p_barrio,
            localidad     = p_localidad,
            calle         = p_calle,
            numero        = p_numero,
            ubicacion_gps = p_ubicacionGps,
            estado        = p_departamento
        WHERE idDireccion = v_idDireccion;
    END IF;

    UPDATE Estudiante SET
        rude            = p_rude,
        apPaterno       = p_apPaterno,
        apMaterno       = p_apMaterno,
        nombre          = p_nombre,
        genero          = p_genero,
        ci              = p_ci,
        fechaNacimiento = p_fechaNac,
        provincia       = p_provincia,
        departamento    = p_departamento,
        nacionalidad    = p_nacionalidad,
        qr              = p_qr
    WHERE idEstudiante = p_idEstudiante;

    COMMIT;

    SET p_mensaje = CONCAT('OK: Estudiante actualizado. id=', p_idEstudiante);
END$$

-- 3. EliminarEstudiante
DROP PROCEDURE IF EXISTS EliminarEstudiante$$
CREATE PROCEDURE EliminarEstudiante(
    IN  p_idEstudiante INT,
    OUT p_mensaje      VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_mensaje = 'ERROR: No se pudo eliminar el estudiante.';
    END;

    UPDATE Estudiante SET estado = 0 WHERE idEstudiante = p_idEstudiante;
    SET p_mensaje = CONCAT('OK: Estudiante desactivado. id=', p_idEstudiante);
END$$

-- 4. InscribirEstudiante
DROP PROCEDURE IF EXISTS InscribirEstudiante$$
CREATE PROCEDURE InscribirEstudiante(
    IN  p_idEstudiante      INT,
    IN  p_idCurso           INT,
    IN  p_idAdministrativo  INT,
    OUT p_idInscripcion     INT,
    OUT p_mensaje           VARCHAR(255)
)
BEGIN
    DECLARE v_existe INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_idInscripcion = 0;
        SET p_mensaje = 'ERROR: No se pudo inscribir al estudiante.';
    END;

    SET p_idInscripcion = 0;

    SELECT COUNT(*) INTO v_existe
    FROM Inscripcion
    WHERE idEstudiante = p_idEstudiante
      AND idCurso      = p_idCurso
      AND estado       = 1;

    IF v_existe > 0 THEN
        SET p_mensaje = 'ADVERTENCIA: El estudiante ya está inscrito en este curso.';
    ELSE
        START TRANSACTION;

        INSERT INTO Inscripcion(idEstudiante, idCurso, idAdministrativo)
        VALUES(p_idEstudiante, p_idCurso, p_idAdministrativo);

        SET p_idInscripcion = LAST_INSERT_ID();

        COMMIT;

        SET p_mensaje = CONCAT('OK: Inscripción realizada. id=', p_idInscripcion);
    END IF;
END$$

-- 5. EliminarInscripcion
DROP PROCEDURE IF EXISTS EliminarInscripcion$$
CREATE PROCEDURE EliminarInscripcion(
    IN  p_idInscripcion INT,
    OUT p_mensaje       VARCHAR(255)
)
BEGIN
    DECLARE v_error  INT DEFAULT 0;
    DECLARE v_existe INT DEFAULT 0;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET v_error = 1;

    SELECT COUNT(*) INTO v_existe
    FROM Inscripcion
    WHERE idInscripcion = p_idInscripcion AND estado = 1;

    IF v_existe = 0 THEN
        SET p_mensaje = 'ERROR: La inscripción no existe o ya fue eliminada.';
    ELSE
        START TRANSACTION;
        UPDATE Inscripcion
        SET estado = 0
        WHERE idInscripcion = p_idInscripcion;

        IF v_error = 1 THEN
            ROLLBACK;
            SET p_mensaje = 'ERROR: No se pudo eliminar la inscripción. Transacción revertida.';
        ELSE
            COMMIT;
            SET p_mensaje = 'OK: Inscripción eliminada correctamente.';
        END IF;
    END IF;
END$$

-- 6. InsertarTutor
DROP PROCEDURE IF EXISTS InsertarTutor$$
CREATE PROCEDURE InsertarTutor(
    IN p_nombreUsuario  VARCHAR(150),
    IN p_password       VARCHAR(255),
    IN p_apPaterno      VARCHAR(80),
    IN p_apMaterno      VARCHAR(80),
    IN p_nombre         VARCHAR(80),
    IN p_ci             VARCHAR(20),
    IN p_telefono       VARCHAR(20),
    IN p_correo         VARCHAR(150),
    IN p_parentesco     VARCHAR(50),
    IN p_tipo           VARCHAR(50),
    IN p_direccion      VARCHAR(255),
    IN p_profesion      VARCHAR(100),
    IN p_ocupacion      VARCHAR(100),
    OUT p_idUsuario     INT,
    OUT p_idTutor       INT,
    OUT p_mensaje       VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_idUsuario = 0;
        SET p_idTutor = 0;
        SET p_mensaje = 'ERROR: No se pudo crear el tutor.';
    END;

    SET p_idUsuario = 0;
    SET p_idTutor = 0;
    SET p_mensaje = '';

    START TRANSACTION;

    IF p_nombreUsuario IS NOT NULL AND p_nombreUsuario <> '' THEN
        INSERT INTO Usuario(idRoles, nombre, password)
        VALUES(3, p_nombreUsuario, p_password);
        SET p_idUsuario = LAST_INSERT_ID();
    END IF;

    INSERT INTO Tutor(
        idUsuario, apPaterno, apMaterno, nombre, ci,
        telefono, correo, parentesco, tipo,
        direccion, profesion, ocupacion
    ) VALUES(
        IF(p_idUsuario > 0, p_idUsuario, NULL),
        p_apPaterno, p_apMaterno, p_nombre, p_ci,
        p_telefono, p_correo, p_parentesco, p_tipo,
        p_direccion, p_profesion, p_ocupacion
    );
    SET p_idTutor = LAST_INSERT_ID();

    COMMIT;

    SET p_mensaje = CONCAT('OK: Tutor creado. Usuario=', p_idUsuario, ', Tutor=', p_idTutor);
END$$

-- 7. EditarTutor
DROP PROCEDURE IF EXISTS EditarTutor$$
CREATE PROCEDURE EditarTutor(
    IN p_idTutor        INT,
    IN p_nombreUsuario  VARCHAR(150),
    IN p_password       VARCHAR(255),
    IN p_apPaterno      VARCHAR(80),
    IN p_apMaterno      VARCHAR(80),
    IN p_nombre         VARCHAR(80),
    IN p_ci             VARCHAR(20),
    IN p_telefono       VARCHAR(20),
    IN p_correo         VARCHAR(150),
    IN p_parentesco     VARCHAR(50),
    IN p_tipo           VARCHAR(50),
    IN p_direccion      VARCHAR(255),
    IN p_profesion      VARCHAR(100),
    IN p_ocupacion      VARCHAR(100),
    OUT p_mensaje       VARCHAR(255)
)
BEGIN
    DECLARE v_idUsuario INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_mensaje = 'ERROR: No se pudo editar el tutor.';
    END;

    START TRANSACTION;

    SELECT idUsuario INTO v_idUsuario
    FROM Tutor WHERE idTutor = p_idTutor;

    UPDATE Tutor SET
        apPaterno  = p_apPaterno,
        apMaterno  = p_apMaterno,
        nombre     = p_nombre,
        ci         = p_ci,
        telefono   = p_telefono,
        correo     = p_correo,
        parentesco = p_parentesco,
        tipo       = p_tipo,
        direccion  = p_direccion,
        profesion  = p_profesion,
        ocupacion  = p_ocupacion
    WHERE idTutor = p_idTutor;

    IF v_idUsuario IS NOT NULL AND v_idUsuario > 0 THEN
        IF p_nombreUsuario IS NOT NULL AND p_nombreUsuario <> '' THEN
            UPDATE Usuario SET nombre = p_nombreUsuario WHERE idUsuario = v_idUsuario;
        END IF;
        IF p_password IS NOT NULL AND p_password <> '' THEN
            UPDATE Usuario SET password = p_password WHERE idUsuario = v_idUsuario;
        END IF;
    END IF;

    COMMIT;

    SET p_mensaje = CONCAT('OK: Tutor actualizado. id=', p_idTutor);
END$$

-- 8. EliminarTutor
DROP PROCEDURE IF EXISTS EliminarTutor$$
CREATE PROCEDURE EliminarTutor(
    IN  p_idTutor INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_idUsuario INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_mensaje = 'ERROR: No se pudo eliminar el tutor.';
    END;

    START TRANSACTION;

    SELECT idUsuario INTO v_idUsuario
    FROM Tutor WHERE idTutor = p_idTutor;

    UPDATE Tutor SET estado = 0 WHERE idTutor = p_idTutor;

    IF v_idUsuario IS NOT NULL AND v_idUsuario > 0 THEN
        UPDATE Usuario SET estado = 0 WHERE idUsuario = v_idUsuario;
    END IF;

    COMMIT;

    SET p_mensaje = CONCAT('OK: Tutor desactivado. id=', p_idTutor);
END$$

-- 9. InsertarDocente
DROP PROCEDURE IF EXISTS InsertarDocente$$
CREATE PROCEDURE InsertarDocente(
    IN p_nombreUsuario VARCHAR(150),
    IN p_password      VARCHAR(255),
    IN p_apPaterno     VARCHAR(80),
    IN p_apMaterno     VARCHAR(80),
    IN p_nombre        VARCHAR(80),
    IN p_ci            VARCHAR(20),
    IN p_numeroItem    VARCHAR(20),
    IN p_rda           VARCHAR(50),
    IN p_categoria     VARCHAR(100),
    IN p_genero        CHAR(1),
    IN p_fechaNac      DATE,
    IN p_correo        VARCHAR(150),
    IN p_telefono      VARCHAR(20),
    IN p_direccion     VARCHAR(255),
    IN p_nacionalidad  VARCHAR(80),
    IN p_departamento  VARCHAR(100),
    IN p_provincia     VARCHAR(100),
    OUT p_idUsuario  INT,
    OUT p_idDocente  INT,
    OUT p_mensaje    VARCHAR(255)
)
BEGIN
    DECLARE v_idCategoria INT DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_idUsuario = 0; SET p_idDocente = 0;
        SET p_mensaje = 'ERROR: No se pudo crear el docente.';
    END;

    SET p_idUsuario = 0; SET p_idDocente = 0; SET p_mensaje = '';
    START TRANSACTION;

    IF p_categoria IS NOT NULL AND p_categoria <> '' THEN
        SELECT idCategoria INTO v_idCategoria FROM Categoria WHERE nombre = p_categoria AND estado = 1 LIMIT 1;
        IF v_idCategoria IS NULL THEN
            INSERT INTO Categoria(nombre) VALUES(p_categoria);
            SET v_idCategoria = LAST_INSERT_ID();
        END IF;
    END IF;

    INSERT INTO Usuario(idRoles, nombre, password) VALUES(2, p_nombreUsuario, p_password);
    SET p_idUsuario = LAST_INSERT_ID();

    INSERT INTO Docente(
        idUsuario, idCategoria, apPaterno, apMaterno, nombre, ci, numeroItem, rda,
        genero, fechaNacimiento, correo, telefono, direccion, nacionalidad,
        departamento, provincia
    ) VALUES(
        p_idUsuario, v_idCategoria, p_apPaterno, p_apMaterno, p_nombre, p_ci, p_numeroItem, p_rda,
        p_genero, p_fechaNac, p_correo, p_telefono, p_direccion, p_nacionalidad,
        p_departamento, p_provincia
    );
    SET p_idDocente = LAST_INSERT_ID();

    COMMIT;
    SET p_mensaje = CONCAT('OK: Docente creado. Usuario=', p_idUsuario, ', Docente=', p_idDocente);
END$$

-- 10. EditarDocente
DROP PROCEDURE IF EXISTS EditarDocente$$
CREATE PROCEDURE EditarDocente(
    IN p_idDocente     INT,
    IN p_nombreUsuario VARCHAR(150),
    IN p_password      VARCHAR(255),
    IN p_apPaterno     VARCHAR(80),
    IN p_apMaterno     VARCHAR(80),
    IN p_nombre        VARCHAR(80),
    IN p_ci            VARCHAR(20),
    IN p_numeroItem    VARCHAR(20),
    IN p_rda           VARCHAR(50),
    IN p_categoria     VARCHAR(100),
    IN p_genero        CHAR(1),
    IN p_fechaNac      DATE,
    IN p_correo        VARCHAR(150),
    IN p_telefono      VARCHAR(20),
    IN p_direccion     VARCHAR(255),
    IN p_nacionalidad  VARCHAR(80),
    IN p_departamento  VARCHAR(100),
    IN p_provincia     VARCHAR(100),
    OUT p_mensaje      VARCHAR(255)
)
BEGIN
    DECLARE v_idUsuario   INT DEFAULT 0;
    DECLARE v_idCategoria INT DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_mensaje = 'ERROR: No se pudo editar el docente.';
    END;

    START TRANSACTION;
    SELECT idUsuario INTO v_idUsuario FROM Docente WHERE idDocente = p_idDocente;

    IF p_categoria IS NOT NULL AND p_categoria <> '' THEN
        SELECT idCategoria INTO v_idCategoria FROM Categoria WHERE nombre = p_categoria AND estado = 1 LIMIT 1;
        IF v_idCategoria IS NULL THEN
            INSERT INTO Categoria(nombre) VALUES(p_categoria);
            SET v_idCategoria = LAST_INSERT_ID();
        END IF;
    END IF;

    UPDATE Docente SET
        idCategoria     = v_idCategoria,
        apPaterno       = p_apPaterno,
        apMaterno       = p_apMaterno,
        nombre          = p_nombre,
        ci              = p_ci,
        numeroItem      = p_numeroItem,
        rda             = p_rda,
        genero          = p_genero,
        fechaNacimiento = p_fechaNac,
        correo          = p_correo,
        telefono        = p_telefono,
        direccion       = p_direccion,
        nacionalidad    = p_nacionalidad,
        departamento    = p_departamento,
        provincia       = p_provincia
    WHERE idDocente = p_idDocente;

    IF v_idUsuario > 0 THEN
        UPDATE Usuario SET nombre = p_nombreUsuario WHERE idUsuario = v_idUsuario;
        IF p_password IS NOT NULL AND p_password <> '' THEN
            UPDATE Usuario SET password = p_password WHERE idUsuario = v_idUsuario;
        END IF;
    END IF;

    COMMIT;
    SET p_mensaje = CONCAT('OK: Docente actualizado. id=', p_idDocente);
END$$

-- 11. EliminarDocente
DROP PROCEDURE IF EXISTS EliminarDocente$$
CREATE PROCEDURE EliminarDocente(
    IN  p_idDocente INT,
    OUT p_mensaje   VARCHAR(255)
)
BEGIN
    DECLARE v_idUsuario INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_mensaje = 'ERROR: No se pudo eliminar el docente.';
    END;

    START TRANSACTION;

    SELECT idUsuario INTO v_idUsuario
    FROM Docente WHERE idDocente = p_idDocente;

    UPDATE Docente SET estado = 0 WHERE idDocente = p_idDocente;

    IF v_idUsuario IS NOT NULL AND v_idUsuario > 0 THEN
        UPDATE Usuario SET estado = 0 WHERE idUsuario = v_idUsuario;
    END IF;

    COMMIT;

    SET p_mensaje = CONCAT('OK: Docente desactivado. id=', p_idDocente);
END$$

-- 12. InsertarAdministrativo
DROP PROCEDURE IF EXISTS InsertarAdministrativo$$
CREATE PROCEDURE InsertarAdministrativo(
    IN p_nombreUsuario VARCHAR(150),
    IN p_password      VARCHAR(255),
    IN p_apPaterno     VARCHAR(80),
    IN p_apMaterno     VARCHAR(80),
    IN p_nombre        VARCHAR(80),
    IN p_ci            VARCHAR(20),
    IN p_nitem         VARCHAR(20),
    IN p_rda           VARCHAR(50),
    IN p_categoria     VARCHAR(100),
    IN p_genero        CHAR(1),
    IN p_fechaNac      DATE,
    IN p_correo        VARCHAR(150),
    IN p_telefono      VARCHAR(20),
    IN p_direccion     VARCHAR(255),
    IN p_nacionalidad  VARCHAR(80),
    IN p_departamento  VARCHAR(100),
    IN p_provincia     VARCHAR(100),
    OUT p_idUsuario        INT,
    OUT p_idAdministrativo INT,
    OUT p_mensaje          VARCHAR(255)
)
BEGIN
    DECLARE v_idCategoria INT DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_idUsuario = 0; SET p_idAdministrativo = 0;
        SET p_mensaje = 'ERROR: No se pudo crear el administrativo.';
    END;

    SET p_idUsuario = 0; SET p_idAdministrativo = 0; SET p_mensaje = '';
    START TRANSACTION;

    IF p_categoria IS NOT NULL AND p_categoria <> '' THEN
        SELECT idCategoria INTO v_idCategoria FROM Categoria WHERE nombre = p_categoria AND estado = 1 LIMIT 1;
        IF v_idCategoria IS NULL THEN
            INSERT INTO Categoria(nombre) VALUES(p_categoria);
            SET v_idCategoria = LAST_INSERT_ID();
        END IF;
    END IF;

    INSERT INTO Usuario(idRoles, nombre, password) VALUES(1, p_nombreUsuario, p_password);
    SET p_idUsuario = LAST_INSERT_ID();

    INSERT INTO Administrativo(
        idUsuario, idCategoria, apPaterno, apMaterno, nombre, ci, nitem, rda,
        genero, fechaNacimiento, correo, telefono, direccion, nacionalidad,
        departamento, provincia
    ) VALUES(
        p_idUsuario, v_idCategoria, p_apPaterno, p_apMaterno, p_nombre, p_ci, p_nitem, p_rda,
        p_genero, p_fechaNac, p_correo, p_telefono, p_direccion, p_nacionalidad,
        p_departamento, p_provincia
    );
    SET p_idAdministrativo = LAST_INSERT_ID();

    COMMIT;
    SET p_mensaje = CONCAT('OK. Administrativo creado. Usuario=', p_idUsuario, ', Administrativo=', p_idAdministrativo);
END$$

-- 12b. sp_InsertarAdministrativo (To support instalar.php)
DROP PROCEDURE IF EXISTS sp_InsertarAdministrativo$$
CREATE PROCEDURE sp_InsertarAdministrativo(
    IN p_nombreUsuario VARCHAR(150),
    IN p_password      VARCHAR(255),
    IN p_apPaterno     VARCHAR(80),
    IN p_apMaterno     VARCHAR(80),
    IN p_nombre        VARCHAR(80),
    IN p_ci            VARCHAR(20),
    IN p_nitem         VARCHAR(20),
    IN p_rda           VARCHAR(50),
    IN p_categoria     VARCHAR(100),
    IN p_genero        CHAR(1),
    IN p_fechaNac      DATE,
    IN p_correo        VARCHAR(150),
    IN p_telefono      VARCHAR(20),
    IN p_direccion     VARCHAR(255),
    IN p_nacionalidad  VARCHAR(80),
    IN p_departamento  VARCHAR(100),
    IN p_provincia     VARCHAR(100),
    OUT p_idUsuario        INT,
    OUT p_idAdministrativo INT,
    OUT p_mensaje          VARCHAR(255)
)
BEGIN
    CALL InsertarAdministrativo(
        p_nombreUsuario, p_password, p_apPaterno, p_apMaterno, p_nombre, p_ci,
        p_nitem, p_rda, p_categoria, p_genero, p_fechaNac,
        p_correo, p_telefono, p_direccion, p_nacionalidad,
        p_departamento, p_provincia,
        p_idUsuario, p_idAdministrativo, p_mensaje
    );
END$$

-- 13. EditarAdministrativo
DROP PROCEDURE IF EXISTS EditarAdministrativo$$
CREATE PROCEDURE EditarAdministrativo(
    IN p_idAdministrativo INT,
    IN p_nombreUsuario VARCHAR(150),
    IN p_password      VARCHAR(255),
    IN p_apPaterno     VARCHAR(80),
    IN p_apMaterno     VARCHAR(80),
    IN p_nombre        VARCHAR(80),
    IN p_ci            VARCHAR(20),
    IN p_nitem         VARCHAR(20),
    IN p_rda           VARCHAR(50),
    IN p_categoria     VARCHAR(100),
    IN p_genero        CHAR(1),
    IN p_fechaNac      DATE,
    IN p_correo        VARCHAR(150),
    IN p_telefono      VARCHAR(20),
    IN p_direccion     VARCHAR(255),
    IN p_nacionalidad  VARCHAR(80),
    IN p_departamento  VARCHAR(100),
    IN p_provincia     VARCHAR(100),
    OUT p_mensaje      VARCHAR(255)
)
BEGIN
    DECLARE v_idUsuario   INT DEFAULT 0;
    DECLARE v_idCategoria INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_mensaje = 'ERROR: No se pudo editar el administrativo.';
    END;

    START TRANSACTION;

    SELECT idUsuario INTO v_idUsuario
    FROM Administrativo WHERE idAdministrativo = p_idAdministrativo;

    IF p_categoria IS NOT NULL AND p_categoria <> '' THEN
        SELECT idCategoria INTO v_idCategoria
        FROM Categoria WHERE nombre = p_categoria AND estado = 1 LIMIT 1;

        IF v_idCategoria IS NULL THEN
            INSERT INTO Categoria(nombre) VALUES(p_categoria);
            SET v_idCategoria = LAST_INSERT_ID();
        END IF;
    END IF;

    UPDATE Administrativo SET
        idCategoria     = v_idCategoria,
        apPaterno       = p_apPaterno,
        apMaterno       = p_apMaterno,
        nombre          = p_nombre,
        ci              = p_ci,
        nitem           = p_nitem,
        rda             = p_rda,
        genero          = p_genero,
        fechaNacimiento = p_fechaNac,
        correo          = p_correo,
        telefono        = p_telefono,
        direccion       = p_direccion,
        nacionalidad    = p_nacionalidad,
        departamento    = p_departamento,
        provincia       = p_provincia
    WHERE idAdministrativo = p_idAdministrativo;

    IF v_idUsuario > 0 THEN
        UPDATE Usuario SET nombre = p_nombreUsuario WHERE idUsuario = v_idUsuario;

        IF p_password IS NOT NULL AND p_password <> '' THEN
            UPDATE Usuario SET password = p_password WHERE idUsuario = v_idUsuario;
        END IF;
    END IF;

    COMMIT;

    SET p_mensaje = CONCAT('OK: Administrativo actualizado. id=', p_idAdministrativo);
END$$

-- 14. EliminarAdministrativo
DROP PROCEDURE IF EXISTS EliminarAdministrativo$$
CREATE PROCEDURE EliminarAdministrativo(
    IN  p_idAdministrativo INT,
    OUT p_mensaje          VARCHAR(255)
)
BEGIN
    DECLARE v_idUsuario INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_mensaje = 'ERROR: No se pudo eliminar el administrativo.';
    END;

    START TRANSACTION;

    SELECT idUsuario INTO v_idUsuario
    FROM Administrativo WHERE idAdministrativo = p_idAdministrativo;

    UPDATE Administrativo SET estado = 0 WHERE idAdministrativo = p_idAdministrativo;

    IF v_idUsuario IS NOT NULL AND v_idUsuario > 0 THEN
        UPDATE Usuario SET estado = 0 WHERE idUsuario = v_idUsuario;
    END IF;

    COMMIT;

    SET p_mensaje = CONCAT('OK: Administrativo desactivado. id=', p_idAdministrativo);
END$$

DELIMITER ;


-- ============================================================
-- PROCEDIMIENTOS / MIGRACIÓN DESDE stored_procedures_direccion.sql
-- ============================================================
-- ============================================================
-- STORED PROCEDURES: DIRECCIÓN (sin prefijo sp_)
-- Sistema Académico JAEGASA — jaegasa_db_fluter
-- ------------------------------------------------------------
-- Nota: en la tabla Direccion, la columna "estado" es VARCHAR
-- (departamento/estado geográfico) y la bandera de activo es
-- "estadoReg" (TINYINT). La baja lógica usa estadoReg = 0.
-- ============================================================

USE jaegasa_db_fluter;

-- ------------------------------------------------------------
-- InsertarDireccion
-- ------------------------------------------------------------
DELIMITER $$
DROP PROCEDURE IF EXISTS InsertarDireccion$$
CREATE PROCEDURE InsertarDireccion(
    IN  p_barrio       VARCHAR(100),
    IN  p_localidad    VARCHAR(100),
    IN  p_calle        VARCHAR(150),
    IN  p_numero       VARCHAR(20),
    IN  p_ubicacionGps VARCHAR(100),
    IN  p_estado       VARCHAR(50),   -- departamento / estado geográfico
    OUT p_idDireccion  INT,
    OUT p_mensaje      VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_idDireccion = 0;
        SET p_mensaje = 'ERROR: No se pudo crear la dirección.';
    END;

    SET p_idDireccion = 0;

    INSERT INTO Direccion(barrio, localidad, calle, numero, ubicacion_gps, estado)
    VALUES(p_barrio, p_localidad, p_calle, p_numero, p_ubicacionGps, p_estado);

    SET p_idDireccion = LAST_INSERT_ID();
    SET p_mensaje = CONCAT('OK: Dirección creada. id=', p_idDireccion);
END$$
DELIMITER ;

-- ------------------------------------------------------------
-- EditarDireccion
-- ------------------------------------------------------------
DELIMITER $$
DROP PROCEDURE IF EXISTS EditarDireccion$$
CREATE PROCEDURE EditarDireccion(
    IN  p_idDireccion  INT,
    IN  p_barrio       VARCHAR(100),
    IN  p_localidad    VARCHAR(100),
    IN  p_calle        VARCHAR(150),
    IN  p_numero       VARCHAR(20),
    IN  p_ubicacionGps VARCHAR(100),
    IN  p_estado       VARCHAR(50),
    OUT p_mensaje      VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_mensaje = 'ERROR: No se pudo editar la dirección.';
    END;

    UPDATE Direccion SET
        barrio        = p_barrio,
        localidad     = p_localidad,
        calle         = p_calle,
        numero        = p_numero,
        ubicacion_gps = p_ubicacionGps,
        estado        = p_estado
    WHERE idDireccion = p_idDireccion;

    SET p_mensaje = CONCAT('OK: Dirección actualizada. id=', p_idDireccion);
END$$
DELIMITER ;

-- ------------------------------------------------------------
-- EliminarDireccion (baja lógica: estadoReg = 0)
-- ------------------------------------------------------------
DELIMITER $$
DROP PROCEDURE IF EXISTS EliminarDireccion$$
CREATE PROCEDURE EliminarDireccion(
    IN  p_idDireccion INT,
    OUT p_mensaje     VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_mensaje = 'ERROR: No se pudo eliminar la dirección.';
    END;

    UPDATE Direccion SET estadoReg = 0 WHERE idDireccion = p_idDireccion;
    SET p_mensaje = CONCAT('OK: Dirección desactivada. id=', p_idDireccion);
END$$
DELIMITER ;

-- ============================================================
-- FIN
-- ============================================================


-- ============================================================
-- PROCEDIMIENTOS / MIGRACIÓN DESDE stored_procedures_inscripcion.sql
-- ============================================================
-- ============================================================
-- stored_procedures_inscripcion.sql
-- Sistema Académico JAEGASA
-- ------------------------------------------------------------
-- Procedimiento faltante del módulo Inscripción.
-- (sp_InscribirEstudiante ya viene en el script base de la BD.)
--
-- EJECUTAR en phpMyAdmin sobre la base jaegasa_db_fluter.
-- ============================================================

USE jaegasa_db_fluter;

DELIMITER $$

-- ------------------------------------------------------------
-- sp_EliminarInscripcion
-- Baja lógica de una inscripción (estado = 0).
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_EliminarInscripcion$$
CREATE PROCEDURE sp_EliminarInscripcion(
    IN  p_idInscripcion INT,
    OUT p_mensaje       VARCHAR(255)
)
BEGIN
    DECLARE v_error  INT DEFAULT 0;
    DECLARE v_existe INT DEFAULT 0;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET v_error = 1;

    SELECT COUNT(*) INTO v_existe
    FROM Inscripcion
    WHERE idInscripcion = p_idInscripcion AND estado = 1;

    IF v_existe = 0 THEN
        SET p_mensaje = 'ERROR: La inscripción no existe o ya fue eliminada.';
    ELSE
        START TRANSACTION;
        UPDATE Inscripcion
        SET estado = 0
        WHERE idInscripcion = p_idInscripcion;

        IF v_error = 1 THEN
            ROLLBACK;
            SET p_mensaje = 'ERROR: No se pudo eliminar la inscripción. Transacción revertida.';
        ELSE
            COMMIT;
            SET p_mensaje = 'OK: Inscripción eliminada correctamente.';
        END IF;
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================


-- ============================================================
-- PROCEDIMIENTOS / MIGRACIÓN DESDE stored_procedures_secundarias.sql
-- ============================================================
-- ============================================================
-- STORED PROCEDURES: TABLAS SECUNDARIAS (sin prefijo sp_)
-- Sistema Académico JAEGASA — jaegasa_db_fluter
-- Tablas: Nivel, Turno, Titulo, Paralelo, Gestion, Horario,
--         Especialidad, Dificultad_Aprendizaje, Discapacidad,
--         Condicion_Medica
-- ============================================================

USE jaegasa_db_fluter;

-- ============================================================
-- NIVEL
-- ============================================================
DELIMITER $$
DROP PROCEDURE IF EXISTS InsertarNivel$$
CREATE PROCEDURE InsertarNivel(
    IN p_nombre VARCHAR(100), IN p_horaInicio TIME, IN p_horaFin TIME,
    OUT p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_id = 0; SET p_mensaje = 'ERROR: No se pudo crear el nivel.'; END;
    INSERT INTO Nivel(nombre, horaInicio, horaFin) VALUES(p_nombre, p_horaInicio, p_horaFin);
    SET p_id = LAST_INSERT_ID();
    SET p_mensaje = CONCAT('OK: Nivel creado. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EditarNivel$$
CREATE PROCEDURE EditarNivel(
    IN p_id INT, IN p_nombre VARCHAR(100), IN p_horaInicio TIME, IN p_horaFin TIME,
    OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo editar el nivel.'; END;
    UPDATE Nivel SET nombre = p_nombre, horaInicio = p_horaInicio, horaFin = p_horaFin WHERE idNivel = p_id;
    SET p_mensaje = CONCAT('OK: Nivel actualizado. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EliminarNivel$$
CREATE PROCEDURE EliminarNivel(IN p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo eliminar el nivel.'; END;
    UPDATE Nivel SET estado = 0 WHERE idNivel = p_id;
    SET p_mensaje = CONCAT('OK: Nivel desactivado. id=', p_id);
END$$
DELIMITER ;

-- ============================================================
-- TURNO
-- ============================================================
DELIMITER $$
DROP PROCEDURE IF EXISTS InsertarTurno$$
CREATE PROCEDURE InsertarTurno(IN p_nombre VARCHAR(100), OUT p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_id = 0; SET p_mensaje = 'ERROR: No se pudo crear el turno.'; END;
    INSERT INTO Turno(nombre) VALUES(p_nombre);
    SET p_id = LAST_INSERT_ID();
    SET p_mensaje = CONCAT('OK: Turno creado. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EditarTurno$$
CREATE PROCEDURE EditarTurno(IN p_id INT, IN p_nombre VARCHAR(100), OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo editar el turno.'; END;
    UPDATE Turno SET nombre = p_nombre WHERE idTurno = p_id;
    SET p_mensaje = CONCAT('OK: Turno actualizado. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EliminarTurno$$
CREATE PROCEDURE EliminarTurno(IN p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo eliminar el turno.'; END;
    UPDATE Turno SET estado = 0 WHERE idTurno = p_id;
    SET p_mensaje = CONCAT('OK: Turno desactivado. id=', p_id);
END$$
DELIMITER ;

-- ============================================================
-- TITULO
-- ============================================================
DELIMITER $$
DROP PROCEDURE IF EXISTS InsertarTitulo$$
CREATE PROCEDURE InsertarTitulo(IN p_nombre VARCHAR(150), OUT p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_id = 0; SET p_mensaje = 'ERROR: No se pudo crear el título.'; END;
    INSERT INTO Titulo(nombre) VALUES(p_nombre);
    SET p_id = LAST_INSERT_ID();
    SET p_mensaje = CONCAT('OK: Título creado. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EditarTitulo$$
CREATE PROCEDURE EditarTitulo(IN p_id INT, IN p_nombre VARCHAR(150), OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo editar el título.'; END;
    UPDATE Titulo SET nombre = p_nombre WHERE idTitulo = p_id;
    SET p_mensaje = CONCAT('OK: Título actualizado. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EliminarTitulo$$
CREATE PROCEDURE EliminarTitulo(IN p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo eliminar el título.'; END;
    UPDATE Titulo SET estado = 0 WHERE idTitulo = p_id;
    SET p_mensaje = CONCAT('OK: Título desactivado. id=', p_id);
END$$
DELIMITER ;

-- ============================================================
-- PARALELO
-- ============================================================
DELIMITER $$
DROP PROCEDURE IF EXISTS InsertarParalelo$$
CREATE PROCEDURE InsertarParalelo(IN p_nombre VARCHAR(10), OUT p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_id = 0; SET p_mensaje = 'ERROR: No se pudo crear el paralelo.'; END;
    INSERT INTO Paralelo(nombre) VALUES(p_nombre);
    SET p_id = LAST_INSERT_ID();
    SET p_mensaje = CONCAT('OK: Paralelo creado. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EditarParalelo$$
CREATE PROCEDURE EditarParalelo(IN p_id INT, IN p_nombre VARCHAR(10), OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo editar el paralelo.'; END;
    UPDATE Paralelo SET nombre = p_nombre WHERE idParalelo = p_id;
    SET p_mensaje = CONCAT('OK: Paralelo actualizado. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EliminarParalelo$$
CREATE PROCEDURE EliminarParalelo(IN p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo eliminar el paralelo.'; END;
    UPDATE Paralelo SET estado = 0 WHERE idParalelo = p_id;
    SET p_mensaje = CONCAT('OK: Paralelo desactivado. id=', p_id);
END$$
DELIMITER ;

-- ============================================================
-- GESTION
-- ============================================================
DELIMITER $$
DROP PROCEDURE IF EXISTS InsertarGestion$$
CREATE PROCEDURE InsertarGestion(IN p_anio YEAR, OUT p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_id = 0; SET p_mensaje = 'ERROR: No se pudo crear la gestión.'; END;
    INSERT INTO Gestion(anio) VALUES(p_anio);
    SET p_id = LAST_INSERT_ID();
    SET p_mensaje = CONCAT('OK: Gestión creada. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EditarGestion$$
CREATE PROCEDURE EditarGestion(IN p_id INT, IN p_anio YEAR, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo editar la gestión.'; END;
    UPDATE Gestion SET anio = p_anio WHERE idGestion = p_id;
    SET p_mensaje = CONCAT('OK: Gestión actualizada. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EliminarGestion$$
CREATE PROCEDURE EliminarGestion(IN p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo eliminar la gestión.'; END;
    UPDATE Gestion SET estado = 0 WHERE idGestion = p_id;
    SET p_mensaje = CONCAT('OK: Gestión desactivada. id=', p_id);
END$$
DELIMITER ;

-- ============================================================
-- HORARIO
-- ============================================================
DELIMITER $$
DROP PROCEDURE IF EXISTS InsertarHorario$$
CREATE PROCEDURE InsertarHorario(
    IN p_diaSemana VARCHAR(20), IN p_horaInicio TIME, IN p_horaFin TIME,
    OUT p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_id = 0; SET p_mensaje = 'ERROR: No se pudo crear el horario.'; END;
    INSERT INTO Horario(diaSemana, horaInicio, horaFin) VALUES(p_diaSemana, p_horaInicio, p_horaFin);
    SET p_id = LAST_INSERT_ID();
    SET p_mensaje = CONCAT('OK: Horario creado. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EditarHorario$$
CREATE PROCEDURE EditarHorario(
    IN p_id INT, IN p_diaSemana VARCHAR(20), IN p_horaInicio TIME, IN p_horaFin TIME,
    OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo editar el horario.'; END;
    UPDATE Horario SET diaSemana = p_diaSemana, horaInicio = p_horaInicio, horaFin = p_horaFin WHERE idHorario = p_id;
    SET p_mensaje = CONCAT('OK: Horario actualizado. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EliminarHorario$$
CREATE PROCEDURE EliminarHorario(IN p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo eliminar el horario.'; END;
    UPDATE Horario SET estado = 0 WHERE idHorario = p_id;
    SET p_mensaje = CONCAT('OK: Horario desactivado. id=', p_id);
END$$
DELIMITER ;

-- ============================================================
-- ESPECIALIDAD  (resuelve categoría por nombre)
-- ============================================================
DELIMITER $$
DROP PROCEDURE IF EXISTS InsertarEspecialidad$$
CREATE PROCEDURE InsertarEspecialidad(
    IN p_nombre VARCHAR(150), IN p_categoria VARCHAR(100),
    OUT p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE v_idCategoria INT DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_id = 0; SET p_mensaje = 'ERROR: No se pudo crear la especialidad.'; END;
    IF p_categoria IS NOT NULL AND p_categoria <> '' THEN
        SELECT idCategoria INTO v_idCategoria FROM Categoria WHERE nombre = p_categoria AND estado = 1 LIMIT 1;
        IF v_idCategoria IS NULL THEN
            INSERT INTO Categoria(nombre) VALUES(p_categoria);
            SET v_idCategoria = LAST_INSERT_ID();
        END IF;
    END IF;
    INSERT INTO Especialidad(idCategoria, nombre) VALUES(v_idCategoria, p_nombre);
    SET p_id = LAST_INSERT_ID();
    SET p_mensaje = CONCAT('OK: Especialidad creada. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EditarEspecialidad$$
CREATE PROCEDURE EditarEspecialidad(
    IN p_id INT, IN p_nombre VARCHAR(150), IN p_categoria VARCHAR(100),
    OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE v_idCategoria INT DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo editar la especialidad.'; END;
    IF p_categoria IS NOT NULL AND p_categoria <> '' THEN
        SELECT idCategoria INTO v_idCategoria FROM Categoria WHERE nombre = p_categoria AND estado = 1 LIMIT 1;
        IF v_idCategoria IS NULL THEN
            INSERT INTO Categoria(nombre) VALUES(p_categoria);
            SET v_idCategoria = LAST_INSERT_ID();
        END IF;
    END IF;
    UPDATE Especialidad SET nombre = p_nombre, idCategoria = v_idCategoria WHERE idEspecialidad = p_id;
    SET p_mensaje = CONCAT('OK: Especialidad actualizada. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EliminarEspecialidad$$
CREATE PROCEDURE EliminarEspecialidad(IN p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo eliminar la especialidad.'; END;
    UPDATE Especialidad SET estado = 0 WHERE idEspecialidad = p_id;
    SET p_mensaje = CONCAT('OK: Especialidad desactivada. id=', p_id);
END$$
DELIMITER ;

-- ============================================================
-- DIFICULTAD DE APRENDIZAJE  (columna del grado: gado)
-- ============================================================
DELIMITER $$
DROP PROCEDURE IF EXISTS InsertarDificultad$$
CREATE PROCEDURE InsertarDificultad(
    IN p_tipoDificultad VARCHAR(150), IN p_grado VARCHAR(50),
    OUT p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_id = 0; SET p_mensaje = 'ERROR: No se pudo crear la dificultad.'; END;
    INSERT INTO Dificultad_Aprendizaje(tipoDificultad, gado) VALUES(p_tipoDificultad, p_grado);
    SET p_id = LAST_INSERT_ID();
    SET p_mensaje = CONCAT('OK: Dificultad creada. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EditarDificultad$$
CREATE PROCEDURE EditarDificultad(
    IN p_id INT, IN p_tipoDificultad VARCHAR(150), IN p_grado VARCHAR(50),
    OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo editar la dificultad.'; END;
    UPDATE Dificultad_Aprendizaje SET tipoDificultad = p_tipoDificultad, gado = p_grado WHERE idDificultad = p_id;
    SET p_mensaje = CONCAT('OK: Dificultad actualizada. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EliminarDificultad$$
CREATE PROCEDURE EliminarDificultad(IN p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo eliminar la dificultad.'; END;
    UPDATE Dificultad_Aprendizaje SET estado = 0 WHERE idDificultad = p_id;
    SET p_mensaje = CONCAT('OK: Dificultad desactivada. id=', p_id);
END$$
DELIMITER ;

-- ============================================================
-- DISCAPACIDAD
-- ============================================================
DELIMITER $$
DROP PROCEDURE IF EXISTS InsertarDiscapacidad$$
CREATE PROCEDURE InsertarDiscapacidad(IN p_tipoDiscapacidad VARCHAR(150), OUT p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_id = 0; SET p_mensaje = 'ERROR: No se pudo crear la discapacidad.'; END;
    INSERT INTO Discapacidad(tipoDiscapacidad) VALUES(p_tipoDiscapacidad);
    SET p_id = LAST_INSERT_ID();
    SET p_mensaje = CONCAT('OK: Discapacidad creada. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EditarDiscapacidad$$
CREATE PROCEDURE EditarDiscapacidad(IN p_id INT, IN p_tipoDiscapacidad VARCHAR(150), OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo editar la discapacidad.'; END;
    UPDATE Discapacidad SET tipoDiscapacidad = p_tipoDiscapacidad WHERE idDiscapacidad = p_id;
    SET p_mensaje = CONCAT('OK: Discapacidad actualizada. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EliminarDiscapacidad$$
CREATE PROCEDURE EliminarDiscapacidad(IN p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo eliminar la discapacidad.'; END;
    UPDATE Discapacidad SET estado = 0 WHERE idDiscapacidad = p_id;
    SET p_mensaje = CONCAT('OK: Discapacidad desactivada. id=', p_id);
END$$
DELIMITER ;

-- ============================================================
-- CONDICION MEDICA
-- ============================================================
DELIMITER $$
DROP PROCEDURE IF EXISTS InsertarCondicion$$
CREATE PROCEDURE InsertarCondicion(IN p_condicion VARCHAR(200), OUT p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_id = 0; SET p_mensaje = 'ERROR: No se pudo crear la condición médica.'; END;
    INSERT INTO Condicion_Medica(condicion) VALUES(p_condicion);
    SET p_id = LAST_INSERT_ID();
    SET p_mensaje = CONCAT('OK: Condición médica creada. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EditarCondicion$$
CREATE PROCEDURE EditarCondicion(IN p_id INT, IN p_condicion VARCHAR(200), OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo editar la condición médica.'; END;
    UPDATE Condicion_Medica SET condicion = p_condicion WHERE idCondicion = p_id;
    SET p_mensaje = CONCAT('OK: Condición médica actualizada. id=', p_id);
END$$

DROP PROCEDURE IF EXISTS EliminarCondicion$$
CREATE PROCEDURE EliminarCondicion(IN p_id INT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN SET p_mensaje = 'ERROR: No se pudo eliminar la condición médica.'; END;
    UPDATE Condicion_Medica SET estado = 0 WHERE idCondicion = p_id;
    SET p_mensaje = CONCAT('OK: Condición médica desactivada. id=', p_id);
END$$
DELIMITER ;

-- ============================================================
-- FIN
-- ============================================================


-- ============================================================
-- PROCEDIMIENTOS / MIGRACIÓN DESDE migracion_ubicacion.sql
-- ============================================================
-- ============================================================
-- MIGRACIÓN: departamento + provincia en Administrativo y Docente
-- Sistema Académico JAEGASA — jaegasa_db_fluter
-- ------------------------------------------------------------
-- Agrega las columnas y redefine los SP de insertar/editar para
-- guardar departamento y provincia (cascada Bolivia).
-- Ejecutar UNA sola vez en phpMyAdmin.
-- ============================================================

USE jaegasa_db_fluter;

-- ------------------------------------------------------------
-- 1) Nuevas columnas
-- ------------------------------------------------------------
ALTER TABLE Administrativo
    ADD COLUMN departamento VARCHAR(100) NULL AFTER nacionalidad,
    ADD COLUMN provincia    VARCHAR(100) NULL AFTER departamento;

ALTER TABLE Docente
    ADD COLUMN departamento VARCHAR(100) NULL AFTER nacionalidad,
    ADD COLUMN provincia    VARCHAR(100) NULL AFTER departamento;

-- ============================================================
-- 2) ADMINISTRATIVO
-- ============================================================
DELIMITER $$

DROP PROCEDURE IF EXISTS sp_InsertarAdministrativo$$
CREATE PROCEDURE sp_InsertarAdministrativo(
    IN p_nombreUsuario VARCHAR(150),
    IN p_password      VARCHAR(255),
    IN p_apPaterno     VARCHAR(80),
    IN p_apMaterno     VARCHAR(80),
    IN p_nombre        VARCHAR(80),
    IN p_ci            VARCHAR(20),
    IN p_nitem         VARCHAR(20),
    IN p_rda           VARCHAR(50),
    IN p_categoria     VARCHAR(100),
    IN p_genero        CHAR(1),
    IN p_fechaNac      DATE,
    IN p_correo        VARCHAR(150),
    IN p_telefono      VARCHAR(20),
    IN p_direccion     VARCHAR(255),
    IN p_nacionalidad  VARCHAR(80),
    IN p_departamento  VARCHAR(100),
    IN p_provincia     VARCHAR(100),
    OUT p_idUsuario        INT,
    OUT p_idAdministrativo INT,
    OUT p_mensaje          VARCHAR(255)
)
BEGIN
    DECLARE v_idCategoria INT DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_idUsuario = 0; SET p_idAdministrativo = 0;
        SET p_mensaje = 'ERROR: No se pudo crear el administrativo.';
    END;

    SET p_idUsuario = 0; SET p_idAdministrativo = 0; SET p_mensaje = '';
    START TRANSACTION;

    IF p_categoria IS NOT NULL AND p_categoria <> '' THEN
        SELECT idCategoria INTO v_idCategoria FROM Categoria WHERE nombre = p_categoria AND estado = 1 LIMIT 1;
        IF v_idCategoria IS NULL THEN
            INSERT INTO Categoria(nombre) VALUES(p_categoria);
            SET v_idCategoria = LAST_INSERT_ID();
        END IF;
    END IF;

    INSERT INTO Usuario(idRoles, nombre, password) VALUES(1, p_nombreUsuario, p_password);
    SET p_idUsuario = LAST_INSERT_ID();

    INSERT INTO Administrativo(
        idUsuario, idCategoria, apPaterno, apMaterno, nombre, ci, nitem, rda,
        genero, fechaNacimiento, correo, telefono, direccion, nacionalidad,
        departamento, provincia
    ) VALUES(
        p_idUsuario, v_idCategoria, p_apPaterno, p_apMaterno, p_nombre, p_ci, p_nitem, p_rda,
        p_genero, p_fechaNac, p_correo, p_telefono, p_direccion, p_nacionalidad,
        p_departamento, p_provincia
    );
    SET p_idAdministrativo = LAST_INSERT_ID();

    COMMIT;
    SET p_mensaje = CONCAT('OK. Administrativo creado. Usuario=', p_idUsuario, ', Administrativo=', p_idAdministrativo);
END$$

DROP PROCEDURE IF EXISTS sp_EditarAdministrativo$$
CREATE PROCEDURE sp_EditarAdministrativo(
    IN p_idAdministrativo INT,
    IN p_nombreUsuario VARCHAR(150),
    IN p_password      VARCHAR(255),
    IN p_apPaterno     VARCHAR(80),
    IN p_apMaterno     VARCHAR(80),
    IN p_nombre        VARCHAR(80),
    IN p_ci            VARCHAR(20),
    IN p_nitem         VARCHAR(20),
    IN p_rda           VARCHAR(50),
    IN p_categoria     VARCHAR(100),
    IN p_genero        CHAR(1),
    IN p_fechaNac      DATE,
    IN p_correo        VARCHAR(150),
    IN p_telefono      VARCHAR(20),
    IN p_direccion     VARCHAR(255),
    IN p_nacionalidad  VARCHAR(80),
    IN p_departamento  VARCHAR(100),
    IN p_provincia     VARCHAR(100),
    OUT p_mensaje      VARCHAR(255)
)
BEGIN
    DECLARE v_idUsuario   INT DEFAULT 0;
    DECLARE v_idCategoria INT DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_mensaje = 'ERROR: No se pudo editar el administrativo.';
    END;

    START TRANSACTION;
    SELECT idUsuario INTO v_idUsuario FROM Administrativo WHERE idAdministrativo = p_idAdministrativo;

    IF p_categoria IS NOT NULL AND p_categoria <> '' THEN
        SELECT idCategoria INTO v_idCategoria FROM Categoria WHERE nombre = p_categoria AND estado = 1 LIMIT 1;
        IF v_idCategoria IS NULL THEN
            INSERT INTO Categoria(nombre) VALUES(p_categoria);
            SET v_idCategoria = LAST_INSERT_ID();
        END IF;
    END IF;

    UPDATE Administrativo SET
        idCategoria = v_idCategoria, apPaterno = p_apPaterno, apMaterno = p_apMaterno,
        nombre = p_nombre, ci = p_ci, nitem = p_nitem, rda = p_rda, genero = p_genero,
        fechaNacimiento = p_fechaNac, correo = p_correo, telefono = p_telefono,
        direccion = p_direccion, nacionalidad = p_nacionalidad,
        departamento = p_departamento, provincia = p_provincia
    WHERE idAdministrativo = p_idAdministrativo;

    IF v_idUsuario > 0 THEN
        UPDATE Usuario SET nombre = p_nombreUsuario WHERE idUsuario = v_idUsuario;
        IF p_password IS NOT NULL AND p_password <> '' THEN
            UPDATE Usuario SET password = p_password WHERE idUsuario = v_idUsuario;
        END IF;
    END IF;

    COMMIT;
    SET p_mensaje = CONCAT('OK: Administrativo actualizado. id=', p_idAdministrativo);
END$$

-- ============================================================
-- 3) DOCENTE
-- ============================================================
DROP PROCEDURE IF EXISTS sp_InsertarDocente$$
CREATE PROCEDURE sp_InsertarDocente(
    IN p_nombreUsuario VARCHAR(150),
    IN p_password      VARCHAR(255),
    IN p_apPaterno     VARCHAR(80),
    IN p_apMaterno     VARCHAR(80),
    IN p_nombre        VARCHAR(80),
    IN p_ci            VARCHAR(20),
    IN p_numeroItem    VARCHAR(20),
    IN p_rda           VARCHAR(50),
    IN p_categoria     VARCHAR(100),
    IN p_genero        CHAR(1),
    IN p_fechaNac      DATE,
    IN p_correo        VARCHAR(150),
    IN p_telefono      VARCHAR(20),
    IN p_direccion     VARCHAR(255),
    IN p_nacionalidad  VARCHAR(80),
    IN p_departamento  VARCHAR(100),
    IN p_provincia     VARCHAR(100),
    OUT p_idUsuario  INT,
    OUT p_idDocente  INT,
    OUT p_mensaje    VARCHAR(255)
)
BEGIN
    DECLARE v_idCategoria INT DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_idUsuario = 0; SET p_idDocente = 0;
        SET p_mensaje = 'ERROR: No se pudo crear el docente.';
    END;

    SET p_idUsuario = 0; SET p_idDocente = 0; SET p_mensaje = '';
    START TRANSACTION;

    IF p_categoria IS NOT NULL AND p_categoria <> '' THEN
        SELECT idCategoria INTO v_idCategoria FROM Categoria WHERE nombre = p_categoria AND estado = 1 LIMIT 1;
        IF v_idCategoria IS NULL THEN
            INSERT INTO Categoria(nombre) VALUES(p_categoria);
            SET v_idCategoria = LAST_INSERT_ID();
        END IF;
    END IF;

    INSERT INTO Usuario(idRoles, nombre, password) VALUES(2, p_nombreUsuario, p_password);
    SET p_idUsuario = LAST_INSERT_ID();

    INSERT INTO Docente(
        idUsuario, idCategoria, apPaterno, apMaterno, nombre, ci, numeroItem, rda,
        genero, fechaNacimiento, correo, telefono, direccion, nacionalidad,
        departamento, provincia
    ) VALUES(
        p_idUsuario, v_idCategoria, p_apPaterno, p_apMaterno, p_nombre, p_ci, p_numeroItem, p_rda,
        p_genero, p_fechaNac, p_correo, p_telefono, p_direccion, p_nacionalidad,
        p_departamento, p_provincia
    );
    SET p_idDocente = LAST_INSERT_ID();

    COMMIT;
    SET p_mensaje = CONCAT('OK: Docente creado. Usuario=', p_idUsuario, ', Docente=', p_idDocente);
END$$

DROP PROCEDURE IF EXISTS sp_EditarDocente$$
CREATE PROCEDURE sp_EditarDocente(
    IN p_idDocente     INT,
    IN p_nombreUsuario VARCHAR(150),
    IN p_password      VARCHAR(255),
    IN p_apPaterno     VARCHAR(80),
    IN p_apMaterno     VARCHAR(80),
    IN p_nombre        VARCHAR(80),
    IN p_ci            VARCHAR(20),
    IN p_numeroItem    VARCHAR(20),
    IN p_rda           VARCHAR(50),
    IN p_categoria     VARCHAR(100),
    IN p_genero        CHAR(1),
    IN p_fechaNac      DATE,
    IN p_correo        VARCHAR(150),
    IN p_telefono      VARCHAR(20),
    IN p_direccion     VARCHAR(255),
    IN p_nacionalidad  VARCHAR(80),
    IN p_departamento  VARCHAR(100),
    IN p_provincia     VARCHAR(100),
    OUT p_mensaje      VARCHAR(255)
)
BEGIN
    DECLARE v_idUsuario   INT DEFAULT 0;
    DECLARE v_idCategoria INT DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_mensaje = 'ERROR: No se pudo editar el docente.';
    END;

    START TRANSACTION;
    SELECT idUsuario INTO v_idUsuario FROM Docente WHERE idDocente = p_idDocente;

    IF p_categoria IS NOT NULL AND p_categoria <> '' THEN
        SELECT idCategoria INTO v_idCategoria FROM Categoria WHERE nombre = p_categoria AND estado = 1 LIMIT 1;
        IF v_idCategoria IS NULL THEN
            INSERT INTO Categoria(nombre) VALUES(p_categoria);
            SET v_idCategoria = LAST_INSERT_ID();
        END IF;
    END IF;

    UPDATE Docente SET
        idCategoria = v_idCategoria, apPaterno = p_apPaterno, apMaterno = p_apMaterno,
        nombre = p_nombre, ci = p_ci, numeroItem = p_numeroItem, rda = p_rda, genero = p_genero,
        fechaNacimiento = p_fechaNac, correo = p_correo, telefono = p_telefono,
        direccion = p_direccion, nacionalidad = p_nacionalidad,
        departamento = p_departamento, provincia = p_provincia
    WHERE idDocente = p_idDocente;

    IF v_idUsuario > 0 THEN
        UPDATE Usuario SET nombre = p_nombreUsuario WHERE idUsuario = v_idUsuario;
        IF p_password IS NOT NULL AND p_password <> '' THEN
            UPDATE Usuario SET password = p_password WHERE idUsuario = v_idUsuario;
        END IF;
    END IF;

    COMMIT;
    SET p_mensaje = CONCAT('OK: Docente actualizado. id=', p_idDocente);
END$$

DELIMITER ;

-- ============================================================
-- FIN DE LA MIGRACIÓN
-- ============================================================


-- ============================================================
-- PROCEDIMIENTOS / MIGRACIÓN DESDE migracion_salud_otros.sql
-- ============================================================
-- ============================================================
-- migracion_salud_otros.sql
-- Sistema Académico JAEGASA
-- ------------------------------------------------------------
-- Agrega el indicador de diagnóstico a la relación
-- Estudiante ↔ Dificultad de Aprendizaje.
--
-- EJECUTAR en phpMyAdmin sobre la base jaegasa_db_fluter.
-- ============================================================

USE jaegasa_db_fluter;

ALTER TABLE Estudiante_Dificultad
    ADD COLUMN diagnostico TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = el estudiante cuenta con diagnóstico de la dificultad'
    AFTER idDificultad;

-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================
