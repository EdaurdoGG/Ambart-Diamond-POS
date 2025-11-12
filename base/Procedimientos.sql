-- Procedimiento: AgregarPersona
-- Descripción general:
-- Este procedimiento registra una nueva persona en la base de datos AmbarDiamond.
-- Inserta los datos personales, de contacto y credenciales de acceso (usuario y contraseña)
-- en la tabla Persona. Asigna automáticamente el estatus 'Activo' y el rol con ID = 3,
-- correspondiente al rol 'Cliente', garantizando que toda nueva persona quede habilitada
-- para utilizar el sistema como usuario estándar.
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

-- Procedimiento: ActualizarPersonaCompleto
-- Descripción general:
-- Este procedimiento actualiza de manera completa la información de una persona
-- registrada en la base de datos AmbarDiamond. Permite modificar datos personales,
-- de contacto, de acceso y el rol asignado. 
-- Actualiza los campos de la tabla Persona con los valores proporcionados mediante 
-- los parámetros de entrada, incluyendo nombre, apellidos, teléfono, correo, edad, 
-- sexo, estatus, usuario e identificador de rol. 
-- Se utiliza para mantener actualizados los datos de cualquier usuario dentro del sistema.
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

-- Procedimiento: EliminarPersona
-- Descripción general:
-- Este procedimiento realiza la eliminación lógica de una persona en la base de datos
-- AmbarDiamond, cambiando su estatus a 'Inactivo' en la tabla Persona. 
-- No elimina físicamente el registro, lo que permite conservar el historial y la integridad 
-- de los datos relacionados. 
-- Se utiliza para deshabilitar usuarios o empleados sin perder su información en el sistema.
CREATE PROCEDURE EliminarPersona(IN p_idPersona INT)
BEGIN
    UPDATE Persona SET Estatus = 'Inactivo' WHERE idPersona = p_idPersona;
END $$

-- Procedimiento: RecuperarPersona
-- Descripción general:
-- Este procedimiento restaura el estatus de una persona previamente desactivada en la base 
-- de datos AmbarDiamond, cambiando su campo Estatus a 'Activo' dentro de la tabla Persona. 
-- Se utiliza para reactivar usuarios o empleados que fueron dados de baja de forma lógica, 
-- permitiendo que vuelvan a acceder y operar en el sistema sin necesidad de recrear su registro.
CREATE PROCEDURE RecuperarPersona(IN p_idPersona INT)
BEGIN
    UPDATE Persona SET Estatus = 'Activo' WHERE idPersona = p_idPersona;
END $$

-- Procedimiento: CambiarContrasenaValidada
-- Descripción general:
-- Este procedimiento permite actualizar la contraseña de un usuario en la base de datos 
-- AmbarDiamond únicamente después de validar su identidad mediante nombre completo, 
-- correo electrónico y nombre de usuario. 
-- Si los datos coinciden con un registro existente, se genera un hash seguro de la nueva 
-- contraseña utilizando el algoritmo SHA2 (256 bits) y se actualiza en la tabla Persona. 
-- Con ello se garantiza un proceso de recuperación o cambio de contraseña más seguro y controlado.
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

-- Procedimiento: AgregarProducto
-- Descripción general:
-- Este procedimiento registra un nuevo producto en la base de datos AmbarDiamond,
-- almacenando su información principal, como nombre, precios de compra y venta,
-- código de barras, existencia en inventario, categoría y la ruta de su imagen asociada.
-- Se utiliza para dar de alta nuevos artículos en el catálogo de productos del sistema,
-- asegurando la correcta vinculación con su categoría correspondiente.
CREATE PROCEDURE AgregarProducto(
    IN p_Nombre VARCHAR(150),
    IN p_PrecioCompra DECIMAL(10,2),
    IN p_PrecioVenta DECIMAL(10,2),
    IN p_CodigoBarra VARCHAR(100),
    IN p_Existencia INT,
    IN p_idCategoria INT,
    IN p_Imagen VARCHAR(255)
)
BEGIN
    INSERT INTO Producto (Nombre, PrecioCompra, PrecioVenta, CodigoBarra, Existencia, idCategoria, Imagen)
    VALUES (p_Nombre, p_PrecioCompra, p_PrecioVenta, p_CodigoBarra, p_Existencia, p_idCategoria, p_Imagen);
END $$

-- Procedimiento: ActualizarProductoCompleto
-- Descripción general:
-- Este procedimiento permite actualizar de manera completa la información de un producto
-- registrado en la base de datos AmbarDiamond. Modifica todos los campos de la tabla
-- Producto, incluyendo nombre, precios de compra y venta, código de barras, existencia,
-- categoría e imagen asociada. 
-- Se utiliza para mantener actualizados los datos del catálogo de productos y garantizar
-- la coherencia de la información en el sistema.
CREATE PROCEDURE ActualizarProductoCompleto(
    IN p_idProducto INT,
    IN p_Nombre VARCHAR(150),
    IN p_PrecioCompra DECIMAL(10,2),
    IN p_PrecioVenta DECIMAL(10,2),
    IN p_CodigoBarra VARCHAR(100),
    IN p_Existencia INT,
    IN p_idCategoria INT,
    IN p_Imagen VARCHAR(255)
)
BEGIN
    UPDATE Producto
    SET Nombre = p_Nombre,
        PrecioCompra = p_PrecioCompra,
        PrecioVenta = p_PrecioVenta,
        CodigoBarra = p_CodigoBarra,
        Existencia = p_Existencia,
        idCategoria = p_idCategoria,
        Imagen = p_Imagen
    WHERE idProducto = p_idProducto;
END $$

-- Procedimiento: EliminarProducto
-- Descripción general:
-- Este procedimiento elimina físicamente un producto de la base de datos AmbarDiamond,
-- eliminando el registro correspondiente de la tabla Producto según su identificador.
-- Se utiliza para remover productos que ya no forman parte del catálogo del sistema.
-- Nota: La eliminación es permanente, por lo que se recomienda usarla con precaución.
CREATE PROCEDURE EliminarProducto(IN p_idProducto INT)
BEGIN
    DELETE FROM Producto WHERE idProducto = p_idProducto;
END $$

-- Procedimiento: BuscarProductoPorCodigoBarra
-- Descripción general:
-- Este procedimiento permite consultar un producto en la base de datos AmbarDiamond
-- utilizando su código de barras como criterio de búsqueda. 
-- Devuelve toda la información registrada en la tabla Producto correspondiente
-- al código proporcionado. 
-- Se utiliza para localizar rápidamente productos específicos dentro del catálogo del sistema.
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

-- Procedimiento: BuscarProductoPorNombre
-- Descripción general:
-- Este procedimiento permite consultar productos en la base de datos AmbarDiamond
-- utilizando un fragmento o el nombre completo del producto como criterio de búsqueda. 
-- Devuelve todos los registros de la tabla Producto cuyo campo Nombre contenga el texto
-- proporcionado. 
-- Se utiliza para localizar productos dentro del catálogo de manera flexible y rápida.
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

-- Procedimiento: AgregarAlCarrito
-- Descripción general:
-- Este procedimiento permite agregar un producto al carrito de compras de un usuario
-- en la base de datos AmbarDiamond. Primero verifica si el usuario ya tiene un carrito
-- activo; si no existe, crea uno nuevo. Luego valida si el producto ya está en el carrito:
--   - Si existe, incrementa la cantidad en 1.
--   - Si no existe, agrega un nuevo registro en DetalleCarrito con cantidad inicial 1.
-- Se utiliza para gestionar de manera automática la adición de productos al carrito
-- asegurando la coherencia de los datos y la actualización correcta de las cantidades.
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

-- Procedimiento: RestarCantidadCarrito
-- Descripción general:
-- Este procedimiento permite disminuir la cantidad de un producto en el carrito de compras
-- de un usuario en la base de datos AmbarDiamond. 
-- Si la cantidad actual es mayor que la indicada, se reduce el valor en la tabla DetalleCarrito. 
-- Si la cantidad a restar es igual o mayor que la actual, el registro del producto se elimina
-- completamente del carrito. 
-- Se utiliza para gestionar la modificación de cantidades en el carrito de manera segura
-- y mantener la coherencia del inventario temporal del usuario.
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

-- Procedimiento: SumarCantidadCarrito
-- Descripción general:
-- Este procedimiento permite aumentar la cantidad de un producto específico en el carrito
-- de compras de un usuario en la base de datos AmbarDiamond. 
-- Antes de actualizar, verifica que la suma de la cantidad actual y la solicitada no supere
-- la existencia disponible en inventario. 
-- Si hay suficiente stock, incrementa la cantidad en DetalleCarrito; de lo contrario,
-- genera un error indicando que no hay suficiente stock disponible. 
-- Se utiliza para garantizar que el carrito refleje cantidades válidas según la disponibilidad
-- de productos.
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

-- Procedimiento: ObtenerCarritoPorPersona
-- Descripción general:
-- Este procedimiento permite obtener todos los productos del carrito de compras de un
-- usuario específico en la base de datos AmbarDiamond. 
-- Devuelve información detallada del carrito, incluyendo el ID del carrito, ID del detalle,
-- nombre del producto, imagen, cantidad, precio unitario y total por producto. 
-- Los resultados se ordenan por el nombre del producto para facilitar la presentación
-- y manipulación de la información en interfaces de usuario o reportes.
CREATE PROCEDURE ObtenerCarritoPorPersona(IN p_idPersona INT)
BEGIN
    SELECT 
        c.idCarrito, 
        dc.idDetalleCarrito, 
        dc.NombreProducto AS Producto,
        dc.ImagenProducto AS Imagen,  -- alias 'Imagen'
        dc.Cantidad, 
        dc.PrecioUnitario, 
        dc.Total
    FROM Carrito c
    JOIN DetalleCarrito dc ON c.idCarrito = dc.idCarrito
    WHERE c.idPersona = p_idPersona
    ORDER BY dc.NombreProducto;
END $$

-- Descripción general:
-- Este procedimiento convierte el carrito de compras de un usuario en un pedido
-- dentro de la base de datos AmbarDiamond. 
-- Primero verifica que el usuario tenga un carrito existente; si no, genera un error. 
-- Luego, crea un nuevo registro en la tabla Pedido con estatus 'Pendiente' y copia 
-- todos los productos del carrito a la tabla DetallePedido, preservando cantidades y precios.
-- Finalmente, elimina los registros del carrito y sus detalles para limpiar el carrito del usuario.
-- Se utiliza para formalizar la compra y transferir los productos del carrito al sistema de pedidos.
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

-- Procedimiento: CambiarPedidoACancelado
-- Descripción general:
-- Este procedimiento permite cambiar el estatus de un pedido existente a 'Cancelado'
-- en la base de datos AmbarDiamond. 
-- Actualiza únicamente el campo Estatus de la tabla Pedido según el identificador 
-- proporcionado, permitiendo que los pedidos que no se concretaron queden correctamente 
-- registrados como cancelados.
CREATE PROCEDURE CambiarPedidoACancelado(IN p_idPedido INT)
BEGIN
    UPDATE Pedido SET Estatus = 'Cancelado' WHERE idPedido = p_idPedido;
END $$

-- Procedimiento: ProcesarVentaNormal
-- Descripción general:
-- Este procedimiento procesa la venta normal de un usuario en la base de datos AmbarDiamond.
-- Toma el carrito de compras del usuario, calcula el subtotal, el IVA (16%) y el monto total,
-- registra la venta en la tabla Venta y los detalles de cada producto en DetalleVenta.
-- Además, actualiza la existencia de los productos vendidos en la tabla Producto
-- y elimina los registros del carrito para reflejar que la venta ha sido completada.
-- Se utiliza para formalizar transacciones de ventas y garantizar la consistencia
-- del inventario y la información de compras.
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

    -- 1️⃣ Obtener el carrito de la persona
    SELECT idCarrito INTO v_idCarrito 
    FROM Carrito 
    WHERE idPersona = p_idPersona 
    LIMIT 1;

    -- 2️⃣ Calcular total de la venta y el total invertido
    SELECT 
        SUM(dc.Cantidad * p.PrecioVenta) AS TotalVenta,
        SUM(dc.Cantidad * p.PrecioCompra) AS TotalInvertido
    INTO v_TotalVenta, v_TotalInvertido
    FROM DetalleCarrito dc
    JOIN Producto p ON dc.idProducto = p.idProducto
    WHERE dc.idCarrito = v_idCarrito;

    -- 3️⃣ Insertar la venta
    INSERT INTO Venta (TipoPago, Estatus, idPersona)
    VALUES (p_TipoPago, 'Activa', p_idPersona);

    SET v_idVenta = LAST_INSERT_ID();

    -- 4️⃣ Insertar el detalle de venta
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

    -- 5️⃣ Actualizar inventario
    UPDATE Producto p
    JOIN DetalleCarrito dc ON p.idProducto = dc.idProducto
    SET p.Existencia = p.Existencia - dc.Cantidad
    WHERE dc.idCarrito = v_idCarrito;

    -- 6️⃣ Insertar en Finanzas
    INSERT INTO Finanzas (idVenta, TotalVenta, TotalInvertido)
    VALUES (v_idVenta, v_TotalVenta, v_TotalInvertido);

    -- 7️⃣ Limpiar carrito
    DELETE FROM DetalleCarrito WHERE idCarrito = v_idCarrito;

END $$

-- Procedimiento: DevolverProductoIndividual
-- Descripción general:
-- Este procedimiento gestiona la devolución de un producto específico vendido en la base
-- de datos AmbarDiamond. 
-- Verifica que la cantidad a devolver no exceda la cantidad vendida y registra la devolución
-- en las tablas Devolucion y DetalleDevolucion, asociando el motivo y el usuario responsable.
-- Actualiza la existencia del producto en inventario y ajusta los detalles de la venta, 
-- recalculando subtotal, IVA y monto total. Si la devolución completa un detalle, este se elimina.
-- Además, registra la acción en la tabla AuditoriaProducto para mantener un historial de movimientos.
-- Se utiliza para manejar devoluciones individuales de manera segura y mantener la coherencia
-- de inventario y registros de ventas.

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

    -- Iniciar transacción para asegurar atomicidad
    START TRANSACTION;

    -- Obtener datos del detalle de venta
    SELECT idProducto, Cantidad, PrecioUnitario, Total
    INTO v_idProducto, v_CantidadActual, v_PrecioUnitario, v_TotalActual
    FROM DetalleVenta
    WHERE idDetalleVenta = p_idDetalleVenta AND idVenta = p_idVenta
    FOR UPDATE;

    -- Validar que la cantidad devuelta sea válida
    IF p_CantidadDevuelta > v_CantidadActual THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cantidad a devolver excede la cantidad vendida.';
    END IF;

    -- Insertar registro principal de devolución
    INSERT INTO Devolucion (Motivo, idPersona)
    VALUES (p_Motivo, p_idPersona);

    -- Insertar detalle de la devolución
    INSERT INTO DetalleDevolucion (idDevolucion, idVenta, idDetalleVenta, CantidadDevuelta, TotalDevuelto)
    VALUES (LAST_INSERT_ID(), p_idVenta, p_idDetalleVenta, p_CantidadDevuelta, p_CantidadDevuelta * v_PrecioUnitario);

    -- Actualizar inventario del producto devuelto
    UPDATE Producto
    SET Existencia = Existencia + p_CantidadDevuelta
    WHERE idProducto = v_idProducto;

    -- Actualizar o eliminar el detalle de venta
    IF v_CantidadActual - p_CantidadDevuelta > 0 THEN
        UPDATE DetalleVenta
        SET Cantidad = v_CantidadActual - p_CantidadDevuelta,
            Total = (v_CantidadActual - p_CantidadDevuelta) * v_PrecioUnitario
        WHERE idDetalleVenta = p_idDetalleVenta;
    ELSE
        DELETE FROM DetalleVenta WHERE idDetalleVenta = p_idDetalleVenta;
    END IF;

    -- Recalcular el total de la venta y actualizar Finanzas
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

    -- Si no quedan productos, marcar venta como “Devuelta”
    IF (SELECT COUNT(*) FROM DetalleVenta WHERE idVenta = p_idVenta) = 0 THEN
        UPDATE Venta SET Estatus = 'Devuelta' WHERE idVenta = p_idVenta;
    END IF;

    -- Registrar auditoría
    INSERT INTO AuditoriaProducto (Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona)
    VALUES (
        'Devolución individual',
        'Existencia',
        v_CantidadActual,
        v_CantidadActual - p_CantidadDevuelta,
        p_idPersona
    );

    -- Confirmar la transacción
    COMMIT;
END$$


-- Procedimiento: DevolverVentaCompleta
-- Descripción general:
-- Este procedimiento gestiona la devolución total de una venta en la base de datos AmbarDiamond.
-- Crea un registro en la tabla Devolucion con el motivo y el usuario responsable, y recorre 
-- todos los productos de la venta para:
--   - Registrar cada devolución en DetalleDevolucion.
--   - Actualizar la existencia de los productos en inventario.
--   - Eliminar los detalles de venta correspondientes.
--   - Registrar la acción en AuditoriaProducto para mantener un historial de movimientos.
-- Finalmente, actualiza la tabla Venta ajustando Subtotal, IVA y MontoTotal a cero y 
-- cambiando el estatus a 'Devuelta'.
-- Se utiliza para procesar devoluciones completas de ventas de manera segura y coherente.
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

    -- Insertar cabecera de la devolución
    INSERT INTO Devolucion (Motivo, idPersona)
    VALUES (p_Motivo, p_idPersona);
    SET v_idDevolucion = LAST_INSERT_ID();

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_idDetalleVenta, v_idProducto, v_Cantidad, v_PrecioUnitario;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Leer existencia actual y bloquear fila
        SELECT Existencia INTO v_ExistenciaActual
        FROM Producto
        WHERE idProducto = v_idProducto
        FOR UPDATE;

        IF v_ExistenciaActual IS NULL THEN
            SET v_ExistenciaActual = 0;
        END IF;

        SET v_ExistenciaNuevo = v_ExistenciaActual + v_Cantidad;

        -- Insertar registro en DetalleDevolucion
        INSERT INTO DetalleDevolucion (
            idDevolucion, idVenta, idDetalleVenta, CantidadDevuelta, TotalDevuelto
        )
        VALUES (
            v_idDevolucion, p_idVenta, v_idDetalleVenta, v_Cantidad, v_Cantidad * v_PrecioUnitario
        );

        -- Actualizar el inventario
        UPDATE Producto
        SET Existencia = v_ExistenciaNuevo
        WHERE idProducto = v_idProducto;

        -- Registrar en auditoría
        INSERT INTO AuditoriaProducto (
            Movimiento, ColumnaAfectada, DatoAnterior, DatoNuevo, idPersona
        )
        VALUES (
            'Devolución completa', 'Existencia', v_ExistenciaActual, v_ExistenciaNuevo, p_idPersona
        );

        -- En lugar de eliminar el detalle, dejarlo (o marcarlo) y poner su Total = 0
        UPDATE DetalleVenta
        SET Total = 0
        WHERE idDetalleVenta = v_idDetalleVenta;
    END LOOP;
    CLOSE cur;

    -- Cambiar el estatus de la venta
    UPDATE Venta
    SET Estatus = 'Devuelta'
    WHERE idVenta = p_idVenta;

    -- Actualizar finanzas a cero
    UPDATE Finanzas
    SET TotalVenta = 0,
        TotalInvertido = 0
    WHERE idVenta = p_idVenta;

    COMMIT;
END$$

-- Procedimiento: RegistrarSugerenciaQueja
-- Descripción general:
-- Este procedimiento permite registrar una sugerencia o queja de un usuario en la base
-- de datos AmbarDiamond. Inserta un nuevo registro en la tabla SugerenciaQueja
-- incluyendo el identificador del usuario, el tipo (sugerencia o queja), la descripción
-- proporcionada y la fecha actual. 
-- Se utiliza para recopilar retroalimentación de los usuarios y facilitar su seguimiento
-- dentro del sistema.
CREATE PROCEDURE RegistrarSugerenciaQueja(IN p_idPersona INT, IN p_Tipo VARCHAR(20), IN p_Descripcion VARCHAR(255))
BEGIN
    INSERT INTO SugerenciaQueja (idPersona, Tipo, Descripcion, Fecha)
    VALUES (p_idPersona, p_Tipo, p_Descripcion, NOW());
END $$

-- Procedimiento: ProcesarVentaPedido
-- Descripción general:
-- Este procedimiento procesa la venta de un pedido existente en la base de datos AmbarDiamond.
-- Toma un pedido identificado por su ID, calcula el subtotal, el IVA (16%) y el monto total,
-- registra la venta en la tabla Venta y los detalles de cada producto en DetalleVenta.
-- Además, actualiza el estatus del pedido a 'Atendido' y ajusta la existencia de los productos
-- vendidos en la tabla Producto. 
-- Se utiliza para formalizar transacciones basadas en pedidos y garantizar la coherencia
-- de inventario y registros de ventas.
CREATE PROCEDURE ProcesarVentaPedido(
    IN p_idPedido INT,
    IN p_idEmpleado INT,      -- 👈 ID del empleado que atiende el pedido
    IN p_TipoPago VARCHAR(10)
)
BEGIN
    DECLARE v_idPersonaPedido INT;
    DECLARE v_TotalInvertido DECIMAL(10,2);
    DECLARE v_TotalVenta DECIMAL(10,2);
    DECLARE v_idVenta INT;

    -- 1️⃣ Obtener la persona que hizo el pedido (cliente)
    SELECT idPersona INTO v_idPersonaPedido
    FROM Pedido
    WHERE idPedido = p_idPedido;

    -- 2️⃣ Calcular el total de la venta y el total invertido usando los precios del producto
    SELECT 
        SUM(dp.Cantidad * p.PrecioVenta) AS TotalVenta,
        SUM(dp.Cantidad * p.PrecioCompra) AS TotalInvertido
    INTO v_TotalVenta, v_TotalInvertido
    FROM DetallePedido dp
    JOIN Producto p ON dp.idProducto = p.idProducto
    WHERE dp.idPedido = p_idPedido;

    -- 3️⃣ Insertar la venta con el ID del empleado que la atiende
    INSERT INTO Venta (TipoPago, Estatus, idPersona)
    VALUES (p_TipoPago, 'Activa', p_idEmpleado);

    SET v_idVenta = LAST_INSERT_ID();

    -- 4️⃣ Insertar el detalle de venta usando los productos del pedido
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

    -- 5️⃣ Actualizar inventario
    UPDATE Producto p
    JOIN DetallePedido dp ON p.idProducto = dp.idProducto
    SET p.Existencia = p.Existencia - dp.Cantidad
    WHERE dp.idPedido = p_idPedido;

    -- 6️⃣ Insertar en Finanzas
    INSERT INTO Finanzas (idVenta, TotalVenta, TotalInvertido)
    VALUES (v_idVenta, v_TotalVenta, v_TotalInvertido);

    -- 7️⃣ Marcar el pedido como atendido
    UPDATE Pedido
    SET Estatus = 'Atendido'
    WHERE idPedido = p_idPedido;

END $$

-- Procedimiento: ActualizarPerfilCliente
-- Descripción general:
-- Este procedimiento permite actualizar la información personal de un cliente registrado
-- en la base de datos AmbarDiamond. Modifica campos como nombre, apellidos, correo
-- electrónico, teléfono e imagen de perfil en la tabla Persona según el ID del cliente.
-- Se utiliza para que los clientes puedan mantener su perfil actualizado de manera segura.
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

-- Procedimiento: ActualizarPerfilAdministrador
-- Descripción general:
-- Este procedimiento permite actualizar la información personal de un administrador
-- registrado en la base de datos AmbarDiamond. Modifica campos como nombre, apellidos,
-- correo electrónico, teléfono e imagen de perfil en la tabla Persona según el ID del administrador.
-- Se utiliza para mantener actualizada la información de los administradores del sistema.
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

-- Procedimiento: ActualizarPerfilEmpleado
-- Descripción general:
-- Este procedimiento permite actualizar la información personal de un empleado registrado
-- en la base de datos AmbarDiamond. Modifica campos como nombre, apellidos, correo
-- electrónico, teléfono e imagen de perfil en la tabla Persona según el ID del empleado.
-- Se utiliza para mantener actualizada la información del personal del sistema.
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

--Nuevo Procediminto--
CREATE PROCEDURE VaciarCarritoPorPersona(IN p_idPersona INT)
BEGIN
    DECLARE v_idCarrito INT;

    -- Obtener el idCarrito asociado a la persona
    SELECT idCarrito INTO v_idCarrito
    FROM Carrito
    WHERE idPersona = p_idPersona
    LIMIT 1;

    -- Si el carrito existe, eliminar los productos asociados
    IF v_idCarrito IS NOT NULL THEN
        DELETE FROM DetalleCarrito
        WHERE idCarrito = v_idCarrito;

    END IF;
END $$