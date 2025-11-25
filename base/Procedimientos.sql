DELIMITER $$

CREATE PROCEDURE AgregarPersona(
    IN p_Nombre VARCHAR(100),
    IN p_ApellidoPaterno VARCHAR(100),
    IN p_ApellidoMaterno VARCHAR(100),
    IN p_Telefono VARCHAR(15),
    IN p_Email VARCHAR(150),
    IN p_Edad INT,
    IN p_Sexo VARCHAR(10),
    IN p_Usuario VARCHAR(50),
    IN p_Contrasena VARCHAR(255)
)
BEGIN
    INSERT INTO Persona (Nombre, ApellidoPaterno, ApellidoMaterno, Telefono, Email, Edad, Sexo, Estatus, Usuario, Contrasena, idRol)
    VALUES (p_Nombre, p_ApellidoPaterno, p_ApellidoMaterno, p_Telefono, p_Email, p_Edad, p_Sexo, 'Activo', p_Usuario, p_Contrasena, 3);
END $$

CREATE PROCEDURE ActualizarPersonaCompleto(
    IN p_idPersona INT,
    IN p_Nombre VARCHAR(100),
    IN p_ApellidoPaterno VARCHAR(100),
    IN p_ApellidoMaterno VARCHAR(100),
    IN p_Telefono VARCHAR(15),
    IN p_Email VARCHAR(150),
    IN p_Edad INT,
    IN p_Sexo VARCHAR(10),
    IN p_Estatus VARCHAR(10),
    IN p_Usuario VARCHAR(50),
    IN p_idRol INT
)
BEGIN
    UPDATE Persona
    SET Nombre = p_Nombre,
        ApellidoPaterno = p_ApellidoPaterno,
        ApellidoMaterno = p_ApellidoMaterno,
        Telefono = p_Telefono,
        Email = p_Email,
        Edad = p_Edad,
        Sexo = p_Sexo,
        Estatus = p_Estatus,
        Usuario = p_Usuario,
        idRol = p_idRol
    WHERE idPersona = p_idPersona;
END $$

CREATE PROCEDURE EliminarPersona(IN p_idPersona INT)
BEGIN
    UPDATE Persona SET Estatus = 'Inactivo' WHERE idPersona = p_idPersona;
END $$

CREATE PROCEDURE RecuperarPersona(IN p_idPersona INT)
BEGIN
    UPDATE Persona SET Estatus = 'Activo' WHERE idPersona = p_idPersona;
END $$

CREATE PROCEDURE CambiarContrasenaValidada(
    IN p_Nombre VARCHAR(100),
    IN p_ApellidoPaterno VARCHAR(100),
    IN p_ApellidoMaterno VARCHAR(100),
    IN p_Email VARCHAR(150),
    IN p_Usuario VARCHAR(50),
    IN p_NuevaContrasena VARCHAR(255)
)
BEGIN
    DECLARE v_idPersona INT;

    SELECT idPersona
    INTO v_idPersona
    FROM Persona
    WHERE Nombre = p_Nombre
      AND ApellidoPaterno = p_ApellidoPaterno
      AND ApellidoMaterno = p_ApellidoMaterno
      AND Email = p_Email
      AND Usuario = p_Usuario
    LIMIT 1;

    IF v_idPersona IS NOT NULL THEN
        UPDATE Persona
        SET Contrasena = p_NuevaContrasena
        WHERE idPersona = v_idPersona;
    END IF;
END $$

CREATE PROCEDURE AgregarProducto(
    IN p_Nombre VARCHAR(150),
    IN p_PrecioCompra DECIMAL(10,2),
    IN p_PrecioVenta DECIMAL(10,2),
    IN p_CodigoBarra VARCHAR(100),
    IN p_Existencia INT,
    IN p_idCategoria INT,
    IN p_Imagen VARCHAR(255),
    IN p_MinimoInventario INT
)
BEGIN
    INSERT INTO Producto (
        Nombre, PrecioCompra, PrecioVenta, CodigoBarra, 
        Existencia, idCategoria, Imagen, MinimoInventario
    )
    VALUES (
        p_Nombre, p_PrecioCompra, p_PrecioVenta, p_CodigoBarra,
        p_Existencia, p_idCategoria, p_Imagen, p_MinimoInventario
    );
END $$

CREATE PROCEDURE ActualizarProductoCompleto(
    IN p_idProducto INT,
    IN p_Nombre VARCHAR(150),
    IN p_PrecioCompra DECIMAL(10,2),
    IN p_PrecioVenta DECIMAL(10,2),
    IN p_CodigoBarra VARCHAR(100),
    IN p_Existencia INT,
    IN p_idCategoria INT,
    IN p_Imagen VARCHAR(255),
    IN p_MinimoInventario INT
)
BEGIN
    UPDATE Producto
    SET Nombre = p_Nombre,
        PrecioCompra = p_PrecioCompra,
        PrecioVenta = p_PrecioVenta,
        CodigoBarra = p_CodigoBarra,
        Existencia = p_Existencia,
        idCategoria = p_idCategoria,
        Imagen = p_Imagen,
        MinimoInventario = p_MinimoInventario
    WHERE idProducto = p_idProducto;
END $$

CREATE PROCEDURE EliminarProducto(IN p_idProducto INT)
BEGIN
    DELETE FROM Producto WHERE idProducto = p_idProducto;
END $$

CREATE PROCEDURE BuscarProductoPorCodigoBarra(IN p_CodigoBarra VARCHAR(100))
BEGIN
    SELECT 
        p.idProducto,
        p.Nombre AS Producto,
        p.CodigoBarra,
        c.Nombre AS Categoria,
        p.PrecioVenta,
        p.PrecioCompra,
        p.Existencia,
        p.Imagen
    FROM Producto p
    INNER JOIN Categoria c ON p.idCategoria = c.idCategoria
    WHERE p.CodigoBarra = p_CodigoBarra;
END $$

CREATE PROCEDURE BuscarProductoPorNombre(IN p_Nombre VARCHAR(150))
BEGIN
    SELECT 
        p.idProducto,
        p.Nombre AS Producto,
        p.CodigoBarra,
        c.Nombre AS Categoria,
        p.PrecioVenta,
        p.PrecioCompra,
        p.Existencia,
        p.Imagen
    FROM Producto p
    INNER JOIN Categoria c ON p.idCategoria = c.idCategoria
    WHERE p.Nombre LIKE CONCAT('%', p_Nombre, '%');
END $$

CREATE PROCEDURE AgregarAlCarrito(
    IN p_idPersona INT,
    IN p_idProducto INT
)
BEGIN
    DECLARE v_idCarrito INT;
    DECLARE v_Precio DECIMAL(10,2);
    DECLARE v_NombreProducto VARCHAR(255);
    DECLARE v_ImagenProducto VARCHAR(255);
    DECLARE v_idDetalleCarrito INT;
    DECLARE v_CantidadActual INT;

    SELECT idCarrito INTO v_idCarrito
    FROM Carrito
    WHERE idPersona = p_idPersona
    LIMIT 1;

    IF v_idCarrito IS NULL THEN
        INSERT INTO Carrito (idPersona, FechaCreacion)
        VALUES (p_idPersona, NOW());
        SET v_idCarrito = LAST_INSERT_ID();
    END IF;

    SELECT Nombre, Imagen, PrecioVenta
    INTO v_NombreProducto, v_ImagenProducto, v_Precio
    FROM Producto
    WHERE idProducto = p_idProducto;

    SELECT idDetalleCarrito, Cantidad
    INTO v_idDetalleCarrito, v_CantidadActual
    FROM DetalleCarrito
    WHERE idCarrito = v_idCarrito AND idProducto = p_idProducto
    LIMIT 1;

    IF v_idDetalleCarrito IS NOT NULL THEN
        UPDATE DetalleCarrito
        SET Cantidad = v_CantidadActual + 1
        WHERE idDetalleCarrito = v_idDetalleCarrito;
    ELSE
        INSERT INTO DetalleCarrito (
            idCarrito,
            idProducto,
            NombreProducto,
            ImagenProducto,
            Cantidad,
            PrecioUnitario
        )
        VALUES (
            v_idCarrito,
            p_idProducto,
            v_NombreProducto,
            v_ImagenProducto,
            1,
            v_Precio
        );
    END IF;
END $$

CREATE PROCEDURE RestarCantidadCarrito(
    IN p_idDetalleCarrito INT,
    IN p_Cantidad INT
)
BEGIN
    DECLARE v_CantidadActual INT;

    SELECT Cantidad INTO v_CantidadActual
    FROM DetalleCarrito
    WHERE idDetalleCarrito = p_idDetalleCarrito;

    IF v_CantidadActual > p_Cantidad THEN
        UPDATE DetalleCarrito
        SET Cantidad = Cantidad - p_Cantidad
        WHERE idDetalleCarrito = p_idDetalleCarrito;
    ELSE
        DELETE FROM DetalleCarrito
        WHERE idDetalleCarrito = p_idDetalleCarrito;
    END IF;
END $$

CREATE PROCEDURE SumarCantidadCarrito(
    IN p_idDetalleCarrito INT,
    IN p_Cantidad INT
)
BEGIN
    DECLARE v_Existencia INT;
    DECLARE v_CantidadActual INT;

    SELECT p.Existencia, dc.Cantidad
    INTO v_Existencia, v_CantidadActual
    FROM DetalleCarrito dc
    JOIN Producto p ON dc.idProducto = p.idProducto
    WHERE dc.idDetalleCarrito = p_idDetalleCarrito;

    IF v_CantidadActual + p_Cantidad <= v_Existencia THEN
        UPDATE DetalleCarrito
        SET Cantidad = Cantidad + p_Cantidad
        WHERE idDetalleCarrito = p_idDetalleCarrito;
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No hay suficiente stock disponible.';
    END IF;
END $$

CREATE PROCEDURE ObtenerCarritoPorPersona(IN p_idPersona INT)
BEGIN
    SELECT 
        c.idCarrito, 
        dc.idDetalleCarrito, 
        dc.NombreProducto AS Producto,
        dc.ImagenProducto AS Imagen, 
        dc.Cantidad, 
        dc.PrecioUnitario, 
        dc.Total
    FROM Carrito c
    JOIN DetalleCarrito dc ON c.idCarrito = dc.idCarrito
    WHERE c.idPersona = p_idPersona
    ORDER BY dc.NombreProducto;
END $$

CREATE PROCEDURE ActualizarCantidadCarrito(
    IN p_idDetalleCarrito INT,
    IN p_NuevaCantidad INT
)
BEGIN
    DECLARE v_Existencia INT;
    DECLARE v_idProducto INT;

    -- Obtener el producto ligado al detalle
    SELECT idProducto
    INTO v_idProducto
    FROM DetalleCarrito
    WHERE idDetalleCarrito = p_idDetalleCarrito;

    -- Si no existe el detalle, detenemos
    IF v_idProducto IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El detalle del carrito no existe.';
    END IF;

    -- Obtener existencia del producto
    SELECT Existencia
    INTO v_Existencia
    FROM Producto
    WHERE idProducto = v_idProducto;

    -- Si la nueva cantidad es 0 o menos, eliminar detalle
    IF p_NuevaCantidad <= 0 THEN
        DELETE FROM DetalleCarrito WHERE idDetalleCarrito = p_idDetalleCarrito;

    -- Validar stock
    ELSEIF p_NuevaCantidad > v_Existencia THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No hay suficiente stock disponible.';

    -- Si hay existencia suficiente, actualizar
    ELSE
        UPDATE DetalleCarrito
        SET Cantidad = p_NuevaCantidad
        WHERE idDetalleCarrito = p_idDetalleCarrito;
    END IF;

END $$

CREATE PROCEDURE CrearPedidoDesdeCarrito(IN p_idPersona INT)
BEGIN
    DECLARE v_idCarrito INT;
    DECLARE v_idPedido INT;

    SELECT idCarrito INTO v_idCarrito
    FROM Carrito
    WHERE idPersona = p_idPersona
    LIMIT 1;

    IF v_idCarrito IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No existe un carrito para esta persona.';
    END IF;

    INSERT INTO Pedido (idPersona, Estatus)
    VALUES (p_idPersona, 'Pendiente');

    SET v_idPedido = LAST_INSERT_ID();

    INSERT INTO DetallePedido (idPedido, idProducto, Cantidad, PrecioUnitario, Total)
    SELECT 
        v_idPedido,
        dc.idProducto,
        dc.Cantidad,
        dc.PrecioUnitario,
        dc.Total
    FROM DetalleCarrito dc
    INNER JOIN Carrito c ON dc.idCarrito = c.idCarrito
    WHERE c.idPersona = p_idPersona;

    DELETE FROM DetalleCarrito
    WHERE idCarrito = v_idCarrito;

    DELETE FROM Carrito
    WHERE idCarrito = v_idCarrito;

END $$

CREATE PROCEDURE CambiarPedidoACancelado(IN p_idPedido INT)
BEGIN
    UPDATE Pedido SET Estatus = 'Cancelado' WHERE idPedido = p_idPedido;
END $$

CREATE PROCEDURE ProcesarVentaNormal(
    IN p_idPersona INT,
    IN p_TipoPago VARCHAR(10)
)
BEGIN
    DECLARE v_idCarrito INT;
    DECLARE v_Subtotal DECIMAL(10,2);
    DECLARE v_TotalInvertido DECIMAL(10,2);
    DECLARE v_TotalVenta DECIMAL(10,2);
    DECLARE v_idVenta INT;

    SELECT idCarrito INTO v_idCarrito 
    FROM Carrito 
    WHERE idPersona = p_idPersona 
    LIMIT 1;

    SELECT 
        SUM(dc.Cantidad * p.PrecioVenta) AS TotalVenta,
        SUM(dc.Cantidad * p.PrecioCompra) AS TotalInvertido
    INTO v_TotalVenta, v_TotalInvertido
    FROM DetalleCarrito dc
    JOIN Producto p ON dc.idProducto = p.idProducto
    WHERE dc.idCarrito = v_idCarrito;

    INSERT INTO Venta (TipoPago, Estatus, idPersona)
    VALUES (p_TipoPago, 'Activa', p_idPersona);

    SET v_idVenta = LAST_INSERT_ID();

    INSERT INTO DetalleVenta (idVenta, idProducto, Cantidad, PrecioUnitario, Total)
    SELECT 
        v_idVenta,
        dc.idProducto,
        dc.Cantidad,
        p.PrecioVenta,
        dc.Cantidad * p.PrecioVenta
    FROM DetalleCarrito dc
    JOIN Producto p ON dc.idProducto = p.idProducto
    WHERE dc.idCarrito = v_idCarrito;

    UPDATE Producto p
    JOIN DetalleCarrito dc ON p.idProducto = dc.idProducto
    SET p.Existencia = p.Existencia - dc.Cantidad
    WHERE dc.idCarrito = v_idCarrito;

    INSERT INTO Finanzas (idVenta, TotalVenta, TotalInvertido)
    VALUES (v_idVenta, v_TotalVenta, v_TotalInvertido);

    DELETE FROM DetalleCarrito WHERE idCarrito = v_idCarrito;

END $$

CREATE PROCEDURE DevolverProductoIndividual(
    IN p_idVenta INT,
    IN p_idDetalleVenta INT,
    IN p_CantidadDevuelta INT,
    IN p_Motivo VARCHAR(200),
    IN p_idPersona INT
)
BEGIN
    DECLARE v_CantidadActual INT;
    DECLARE v_PrecioUnitario DECIMAL(10,2);
    DECLARE v_TotalActual DECIMAL(10,2);
    DECLARE v_idProducto INT;

    START TRANSACTION;

    SELECT idProducto, Cantidad, PrecioUnitario, Total
    INTO v_idProducto, v_CantidadActual, v_PrecioUnitario, v_TotalActual
    FROM DetalleVenta
    WHERE idDetalleVenta = p_idDetalleVenta AND idVenta = p_idVenta
    FOR UPDATE;

    IF p_CantidadDevuelta > v_CantidadActual THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cantidad a devolver excede la cantidad vendida.';
    END IF;

    INSERT INTO Devolucion (Motivo, idPersona)
    VALUES (p_Motivo, p_idPersona);

    INSERT INTO DetalleDevolucion (idDevolucion, idVenta, idDetalleVenta, CantidadDevuelta, TotalDevuelto)
    VALUES (LAST_INSERT_ID(), p_idVenta, p_idDetalleVenta, p_CantidadDevuelta, p_CantidadDevuelta * v_PrecioUnitario);

    UPDATE Producto
    SET Existencia = Existencia + p_CantidadDevuelta
    WHERE idProducto = v_idProducto;

    IF v_CantidadActual - p_CantidadDevuelta > 0 THEN
        UPDATE DetalleVenta
        SET Cantidad = v_CantidadActual - p_CantidadDevuelta,
            Total = (v_CantidadActual - p_CantidadDevuelta) * v_PrecioUnitario
        WHERE idDetalleVenta = p_idDetalleVenta;
    ELSE
        DELETE FROM DetalleVenta WHERE idDetalleVenta = p_idDetalleVenta;
    END IF;

    UPDATE Finanzas f
    JOIN (
        SELECT 
            v.idVenta,
            IFNULL(SUM(dv.Total), 0) AS NuevoTotalVenta,
            IFNULL(SUM(dv.Cantidad * p.PrecioCompra), 0) AS NuevoTotalInvertido
        FROM Venta v
        LEFT JOIN DetalleVenta dv ON v.idVenta = dv.idVenta
        LEFT JOIN Producto p ON dv.idProducto = p.idProducto
        WHERE v.idVenta = p_idVenta
        GROUP BY v.idVenta
    ) AS sub ON f.idVenta = sub.idVenta
    SET f.TotalVenta = sub.NuevoTotalVenta,
        f.TotalInvertido = sub.NuevoTotalInvertido;

    IF (SELECT COUNT(*) FROM DetalleVenta WHERE idVenta = p_idVenta) = 0 THEN
        UPDATE Venta SET Estatus = 'Devuelta' WHERE idVenta = p_idVenta;
    END IF;

    INSERT INTO AuditoriaProducto (Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
    VALUES (
        'Devolución individual',
        'Existencia',
        v_CantidadActual,
        v_CantidadActual - p_CantidadDevuelta,
        p_idPersona
    );

    COMMIT;
END$$

CREATE PROCEDURE DevolverVentaCompleta(
    IN p_idVenta INT,
    IN p_Motivo VARCHAR(200),
    IN p_idPersona INT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_idDetalleVenta INT;
    DECLARE v_idProducto INT;
    DECLARE v_Cantidad INT;
    DECLARE v_PrecioUnitario DECIMAL(10,2);
    DECLARE v_idDevolucion INT;
    DECLARE v_ExistenciaActual INT;
    DECLARE v_ExistenciaNuevo INT;

    DECLARE cur CURSOR FOR
        SELECT idDetalleVenta, idProducto, Cantidad, PrecioUnitario
        FROM DetalleVenta
        WHERE idVenta = p_idVenta;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Error en DevolverVentaCompleta. Transacción revertida.';
    END;

    START TRANSACTION;

    INSERT INTO Devolucion (Motivo, idPersona)
    VALUES (p_Motivo, p_idPersona);
    SET v_idDevolucion = LAST_INSERT_ID();

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_idDetalleVenta, v_idProducto, v_Cantidad, v_PrecioUnitario;
        IF done THEN
            LEAVE read_loop;
        END IF;

        SELECT Existencia INTO v_ExistenciaActual
        FROM Producto
        WHERE idProducto = v_idProducto
        FOR UPDATE;

        IF v_ExistenciaActual IS NULL THEN
            SET v_ExistenciaActual = 0;
        END IF;

        SET v_ExistenciaNuevo = v_ExistenciaActual + v_Cantidad;

        INSERT INTO DetalleDevolucion (
            idDevolucion, idVenta, idDetalleVenta, CantidadDevuelta, TotalDevuelto
        )
        VALUES (
            v_idDevolucion, p_idVenta, v_idDetalleVenta, v_Cantidad, v_Cantidad * v_PrecioUnitario
        );

        UPDATE Producto
        SET Existencia = v_ExistenciaNuevo
        WHERE idProducto = v_idProducto;

        INSERT INTO AuditoriaProducto (
            Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona
        )
        VALUES (
            'Devolución completa', 'Existencia', v_ExistenciaActual, v_ExistenciaNuevo, p_idPersona
        );

        UPDATE DetalleVenta
        SET Total = 0
        WHERE idDetalleVenta = v_idDetalleVenta;
    END LOOP;
    CLOSE cur;

    UPDATE Venta
    SET Estatus = 'Devuelta'
    WHERE idVenta = p_idVenta;

    UPDATE Finanzas
    SET TotalVenta = 0,
        TotalInvertido = 0
    WHERE idVenta = p_idVenta;

    COMMIT;
END$$

CREATE PROCEDURE RegistrarSugerenciaQueja(IN p_idPersona INT, IN p_Tipo VARCHAR(20), IN p_Descripcion VARCHAR(255))
BEGIN
    INSERT INTO SugerenciaQueja (idPersona, Tipo, Descripcion, Fecha)
    VALUES (p_idPersona, p_Tipo, p_Descripcion, NOW());
END $$

CREATE PROCEDURE ProcesarVentaPedido(
    IN p_idPedido INT,
    IN p_idEmpleado INT,     
    IN p_TipoPago VARCHAR(10)
)
BEGIN
    DECLARE v_idPersonaPedido INT;
    DECLARE v_TotalInvertido DECIMAL(10,2);
    DECLARE v_TotalVenta DECIMAL(10,2);
    DECLARE v_idVenta INT;

    SELECT idPersona INTO v_idPersonaPedido
    FROM Pedido
    WHERE idPedido = p_idPedido;

    SELECT 
        SUM(dp.Cantidad * p.PrecioVenta) AS TotalVenta,
        SUM(dp.Cantidad * p.PrecioCompra) AS TotalInvertido
    INTO v_TotalVenta, v_TotalInvertido
    FROM DetallePedido dp
    JOIN Producto p ON dp.idProducto = p.idProducto
    WHERE dp.idPedido = p_idPedido;

    INSERT INTO Venta (TipoPago, Estatus, idPersona)
    VALUES (p_TipoPago, 'Activa', p_idEmpleado);

    SET v_idVenta = LAST_INSERT_ID();

    INSERT INTO DetalleVenta (idVenta, idProducto, Cantidad, PrecioUnitario, Total)
    SELECT 
        v_idVenta,
        dp.idProducto,
        dp.Cantidad,
        p.PrecioVenta,
        dp.Cantidad * p.PrecioVenta
    FROM DetallePedido dp
    JOIN Producto p ON dp.idProducto = p.idProducto
    WHERE dp.idPedido = p_idPedido;

    UPDATE Producto p
    JOIN DetallePedido dp ON p.idProducto = dp.idProducto
    SET p.Existencia = p.Existencia - dp.Cantidad
    WHERE dp.idPedido = p_idPedido;

    INSERT INTO Finanzas (idVenta, TotalVenta, TotalInvertido)
    VALUES (v_idVenta, v_TotalVenta, v_TotalInvertido);

    UPDATE Pedido
    SET Estatus = 'Atendido'
    WHERE idPedido = p_idPedido;

END $$

CREATE PROCEDURE ActualizarPerfilCliente(
    IN p_idPersona INT,
    IN p_Nombre VARCHAR(100),
    IN p_ApellidoP VARCHAR(100),
    IN p_ApellidoM VARCHAR(100),
    IN p_Email VARCHAR(150),
    IN p_Telefono VARCHAR(15),
    IN p_Imagen VARCHAR(255)
)
BEGIN
    UPDATE Persona
    SET Nombre = p_Nombre,
        ApellidoPaterno = p_ApellidoP,
        ApellidoMaterno = p_ApellidoM,
        Email = p_Email,
        Telefono = p_Telefono,
        Imagen = p_Imagen
    WHERE idPersona = p_idPersona;
END$$

CREATE PROCEDURE ActualizarPerfilAdministrador(
    IN p_idPersona INT,
    IN p_Nombre VARCHAR(100),
    IN p_ApellidoP VARCHAR(100),
    IN p_ApellidoM VARCHAR(100),
    IN p_Email VARCHAR(150),
    IN p_Telefono VARCHAR(15),
    IN p_Imagen VARCHAR(255)
)
BEGIN
    UPDATE Persona
    SET Nombre = p_Nombre,
        ApellidoPaterno = p_ApellidoP,
        ApellidoMaterno = p_ApellidoM,
        Email = p_Email,
        Telefono = p_Telefono,
        Imagen = p_Imagen
    WHERE idPersona = p_idPersona;
END$$

CREATE PROCEDURE ActualizarPerfilEmpleado(
    IN p_idPersona INT,
    IN p_Nombre VARCHAR(100),
    IN p_ApellidoP VARCHAR(100),
    IN p_ApellidoM VARCHAR(100),
    IN p_Email VARCHAR(150),
    IN p_Telefono VARCHAR(15),
    IN p_Imagen VARCHAR(255)
)
BEGIN
    UPDATE Persona
    SET Nombre = p_Nombre,
        ApellidoPaterno = p_ApellidoP,
        ApellidoMaterno = p_ApellidoM,
        Email = p_Email,
        Telefono = p_Telefono,
        Imagen = p_Imagen
    WHERE idPersona = p_idPersona;
END$$

CREATE PROCEDURE VaciarCarritoPorPersona(IN p_idPersona INT)
BEGIN
    DECLARE v_idCarrito INT;

    SELECT idCarrito INTO v_idCarrito
    FROM Carrito
    WHERE idPersona = p_idPersona
    LIMIT 1;

    IF v_idCarrito IS NOT NULL THEN
        DELETE FROM DetalleCarrito
        WHERE idCarrito = v_idCarrito;

    END IF;
END $$

DELIMITER ;