DELIMITER $$

-- TRIGGER: DetalleVentaBeforeInsert
-- Evita vender más de lo que hay en stock
CREATE TRIGGER DetalleVentaBeforeInsert
BEFORE INSERT ON DetalleVenta
FOR EACH ROW
BEGIN
    DECLARE stockDisponible INT;
    SELECT Existencia INTO stockDisponible
    FROM Producto
    WHERE idProducto = NEW.idProducto;

    IF NEW.Cantidad > stockDisponible THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede vender más de lo disponible en stock.';
    END IF;
END$$

-- TRIGGER: DetalleVentaAfterInsert
-- Auditoría al registrar una venta
CREATE TRIGGER DetalleVentaAfterInsert
AFTER INSERT ON DetalleVenta
FOR EACH ROW
BEGIN
    INSERT INTO AuditoriaProducto(Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
    VALUES (
        CONCAT('Venta realizada por Usuario ', @id_usuario_actual),
        'Existencia',
        (SELECT Existencia + NEW.Cantidad FROM Producto WHERE idProducto = NEW.idProducto),
        (SELECT Existencia FROM Producto WHERE idProducto = NEW.idProducto),
        @id_usuario_actual
    );
END$$

-- TRIGGER: DevolucionAfterInsert
-- Auditoría al registrar una devolución
CREATE TRIGGER DevolucionAfterInsert
AFTER INSERT ON Devolucion
FOR EACH ROW
BEGIN
    INSERT INTO AuditoriaDevolucion(Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
    VALUES (
        CONCAT('Devolucion por Usuario ', @id_usuario_actual),
        'Motivo',
        NULL,
        NEW.Motivo,
        @id_usuario_actual
    );
END$$

-- TRIGGER: PersonaAfterInsert
-- Auditoría al crear una persona
CREATE TRIGGER PersonaAfterInsert
AFTER INSERT ON Persona
FOR EACH ROW
BEGIN
    INSERT INTO AuditoriaPersona(Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
    VALUES (
        CONCAT('Creacion de usuario ', NEW.idPersona),
        'Nombre',
        NULL,
        NEW.Nombre,
        NEW.idPersona
    );
END$$

-- TRIGGER: PersonaAfterUpdate
-- Auditoría de cambios en Persona
CREATE TRIGGER PersonaAfterUpdate
AFTER UPDATE ON Persona
FOR EACH ROW
BEGIN
    DECLARE v_usuario INT;
    SET v_usuario = IFNULL(@id_usuario_actual, NEW.idPersona);

    IF NOT (NEW.Nombre <=> OLD.Nombre) THEN
        INSERT INTO AuditoriaPersona VALUES (CONCAT('Modificacion por Usuario ', v_usuario), 'Nombre', OLD.Nombre, NEW.Nombre, v_usuario);
    END IF;

    IF NOT (NEW.ApellidoPaterno <=> OLD.ApellidoPaterno) THEN
        INSERT INTO AuditoriaPersona VALUES (CONCAT('Modificacion por Usuario ', v_usuario), 'ApellidoPaterno', OLD.ApellidoPaterno, NEW.ApellidoPaterno, v_usuario);
    END IF;

    IF NOT (NEW.ApellidoMaterno <=> OLD.ApellidoMaterno) THEN
        INSERT INTO AuditoriaPersona VALUES (CONCAT('Modificacion por Usuario ', v_usuario), 'ApellidoMaterno', OLD.ApellidoMaterno, NEW.ApellidoMaterno, v_usuario);
    END IF;

    IF NOT (NEW.Telefono <=> OLD.Telefono) THEN
        INSERT INTO AuditoriaPersona VALUES (CONCAT('Modificacion por Usuario ', v_usuario), 'Telefono', OLD.Telefono, NEW.Telefono, v_usuario);
    END IF;

    IF NOT (NEW.Email <=> OLD.Email) THEN
        INSERT INTO AuditoriaPersona VALUES (CONCAT('Modificacion por Usuario ', v_usuario), 'Email', OLD.Email, NEW.Email, v_usuario);
    END IF;

    IF NOT (NEW.Edad <=> OLD.Edad) THEN
        INSERT INTO AuditoriaPersona VALUES (CONCAT('Modificacion por Usuario ', v_usuario), 'Edad', OLD.Edad, NEW.Edad, v_usuario);
    END IF;

    IF NOT (NEW.Sexo <=> OLD.Sexo) THEN
        INSERT INTO AuditoriaPersona VALUES (CONCAT('Modificacion por Usuario ', v_usuario), 'Sexo', OLD.Sexo, NEW.Sexo, v_usuario);
    END IF;

    IF NOT (NEW.Estatus <=> OLD.Estatus) THEN
        INSERT INTO AuditoriaPersona VALUES (CONCAT('Modificacion por Usuario ', v_usuario), 'Estatus', OLD.Estatus, NEW.Estatus, v_usuario);
    END IF;
END$$

-- TRIGGER: ProductoAfterInsert
-- Auditoría al crear un producto
CREATE TRIGGER ProductoAfterInsert
AFTER INSERT ON Producto
FOR EACH ROW
BEGIN
    INSERT INTO AuditoriaProducto(Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
    VALUES (
        CONCAT('Creacion por Usuario ', @id_usuario_actual),
        'Nombre',
        NULL,
        NEW.Nombre,
        @id_usuario_actual
    );
END$$

-- TRIGGER: ProductoAfterUpdate
-- Auditoría de cambios en Producto + alerta baja existencia
CREATE TRIGGER ProductoAfterUpdate
AFTER UPDATE ON Producto
FOR EACH ROW
BEGIN
    DECLARE v_usuario INT DEFAULT @id_usuario_actual;

    IF NOT (NEW.Nombre <=> OLD.Nombre) THEN
        INSERT INTO AuditoriaProducto (Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
        VALUES (CONCAT('Modificacion por Usuario ', v_usuario), 'Nombre', OLD.Nombre, NEW.Nombre, v_usuario);
    END IF;

    IF NOT (NEW.CodigoBarra <=> OLD.CodigoBarra) THEN
        INSERT INTO AuditoriaProducto (Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
        VALUES (CONCAT('Modificacion por Usuario ', v_usuario), 'CodigoBarra', OLD.CodigoBarra, NEW.CodigoBarra, v_usuario);
    END IF;

    IF NOT (NEW.Existencia <=> OLD.Existencia) THEN
        INSERT INTO AuditoriaProducto (Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
        VALUES (CONCAT('Cambio por Venta/Devolucion/Usuario ', v_usuario), 'Existencia', OLD.Existencia, NEW.Existencia, v_usuario);

        IF NEW.Existencia < 10 AND OLD.Existencia >= 10 THEN
            INSERT INTO AuditoriaProducto (Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
            VALUES ('Alerta Baja Existencia', 'Existencia', OLD.Existencia, NEW.Existencia, v_usuario);
        END IF;
    END IF;

    IF NOT (NEW.PrecioCompra <=> OLD.PrecioCompra) THEN
        INSERT INTO AuditoriaProducto (Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
        VALUES (CONCAT('Modificacion por Usuario ', v_usuario), 'PrecioCompra', OLD.PrecioCompra, NEW.PrecioCompra, v_usuario);
    END IF;

    IF NOT (NEW.PrecioVenta <=> OLD.PrecioVenta) THEN
        INSERT INTO AuditoriaProducto (Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
        VALUES (CONCAT('Modificacion por Usuario ', v_usuario), 'PrecioVenta', OLD.PrecioVenta, NEW.PrecioVenta, v_usuario);
    END IF;

    IF NOT (NEW.idCategoria <=> OLD.idCategoria) THEN
        INSERT INTO AuditoriaProducto (Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
        VALUES (CONCAT('Modificacion por Usuario ', v_usuario), 'Categoria', OLD.idCategoria, NEW.idCategoria, v_usuario);
    END IF;
END$$

DELIMITER ;
