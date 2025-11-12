-- Vista: AdministradoresRegistrados
-- Descripción general:
-- Esta vista proporciona una lista de todos los administradores registrados en la
-- base de datos AmbarDiamond. Incluye información personal como nombre, apellidos,
-- correo electrónico, teléfono e imagen, así como el estado del usuario y el rol asignado.
-- Se utiliza para consultar rápidamente los datos de los administradores sin necesidad
-- de hacer joins complejos en la tabla Persona y Rol.
CREATE OR REPLACE VIEW AdministradoresRegistrados AS
SELECT 
    p.idPersona AS idAdministrador,
    p.Nombre,
    p.ApellidoPaterno,
    p.ApellidoMaterno,
    p.Email,
    p.Telefono,
    p.Imagen,
    p.Estatus AS Estado,
    r.NombreRol AS Rol,
    'Administrador' AS TipoUsuario
FROM Persona p
JOIN Rol r ON p.idRol = r.idRol
WHERE p.idRol = 1;

-- Vista: VentasDiariasPorEmpleado
-- Descripción general:
-- Esta vista proporciona un resumen detallado de las ventas diarias realizadas por
-- cada empleado en la base de datos AmbarDiamond. Incluye información sobre la venta,
-- como número de venta, fecha, hora, empleado responsable, cliente, producto, cantidad,
-- precio unitario, IVA y subtotal por producto. 
-- Además, calcula los totales de cada venta (subtotal, IVA y total) y muestra el tipo
-- de pago y el estatus de la venta. 
-- Se utiliza para generar reportes de desempeño de ventas, seguimiento de transacciones
-- y análisis diario por empleado.
CREATE OR REPLACE VIEW VentasDiariasPorEmpleado AS
SELECT 
    v.idVenta AS NumeroVenta,
    DATE(v.Fecha) AS Fecha,
    TIME(v.Fecha) AS Hora,
    CONCAT(emp.Nombre, ' ', emp.ApellidoPaterno, ' ', emp.ApellidoMaterno) AS Empleado,
    p.Nombre AS Producto,
    dv.Cantidad,
    dv.PrecioUnitario,
    dv.Total,
    v.TipoPago,
    v.Estatus
FROM Venta v
JOIN DetalleVenta dv ON v.idVenta = dv.idVenta
JOIN Producto p ON dv.idProducto = p.idProducto
JOIN Persona emp ON v.idPersona = emp.idPersona
WHERE v.Estatus = 'Activa';

-- Vista: VistaVentasEmpleado
-- Descripción general:
-- Esta vista proporciona un detalle completo de las ventas realizadas por cada empleado
-- en la base de datos AmbarDiamond. Incluye información sobre la venta, como número de venta,
-- ID del empleado, fecha, hora, producto, cantidad, precio unitario, IVA y subtotal por producto.
-- También calcula los totales de cada venta (subtotal, IVA y total) en tiempo real, y muestra
-- el tipo de pago y el estatus de la venta. 
-- Se utiliza para analizar y reportar el desempeño de los empleados en las ventas y gestionar
-- información de transacciones de manera precisa.
CREATE OR REPLACE VIEW VistaVentasEmpleado AS
SELECT 
    v.idVenta AS NumeroVenta,
    v.idPersona AS idEmpleado,
    DATE(v.Fecha) AS Fecha,
    TIME(v.Fecha) AS Hora,
    p.Nombre AS Producto,
    dv.Cantidad,
    dv.PrecioUnitario,
    dv.Total AS Subtotal,
    v.TipoPago,
    v.Estatus
FROM Venta v
JOIN DetalleVenta dv ON v.idVenta = dv.idVenta
JOIN Producto p ON dv.idProducto = p.idProducto;

-- Vista: EmpleadosRegistrados
-- Descripción general:
-- Esta vista proporciona una lista de todos los empleados registrados en la base de datos
-- AmbarDiamond. Incluye información personal como nombre, apellidos, correo electrónico,
-- edad, sexo, teléfono, imagen, estado del empleado y el rol asignado.
-- Se utiliza para consultar rápidamente los datos del personal sin necesidad de hacer joins
-- complejos en la tabla Persona y Rol, facilitando la administración interna del sistema.
CREATE OR REPLACE VIEW EmpleadosRegistrados AS
SELECT 
    p.idPersona AS idEmpleado,
    p.Nombre,
    p.ApellidoPaterno,
    p.ApellidoMaterno,
    p.Email,
    p.Edad,
    p.Sexo,
    p.Telefono,
    p.Imagen,
    p.Estatus AS Estado,
    r.NombreRol AS Rol,
    'Empleado' AS TipoUsuario
FROM Persona p
JOIN Rol r ON p.idRol = r.idRol
WHERE p.idRol = 2;

-- Vista: ProductosConEstado
-- Descripción general:
-- Esta vista proporciona un listado de todos los productos registrados en la base de datos
-- AmbarDiamond, incluyendo su información básica como nombre, categoría, existencia, 
-- precios de compra y venta, y la imagen asociada.
-- Además, determina el estado del producto como 'Activo' si hay existencia o 'Inactivo' 
-- si no hay stock disponible. Los resultados se ordenan alfabéticamente por nombre de producto.
-- Se utiliza para consultar el inventario de productos y conocer rápidamente su disponibilidad.
CREATE OR REPLACE VIEW ProductosConEstado AS
SELECT 
    p.idProducto,
    p.Nombre AS Producto,
    c.Nombre AS Categoria,
    p.Existencia,
    p.PrecioCompra,
    p.PrecioVenta,
    p.Imagen,
    CASE 
        WHEN p.Existencia > 0 THEN 'Activo'
        ELSE 'Inactivo'
    END AS Estado
FROM Producto p
JOIN Categoria c ON p.idCategoria = c.idCategoria
ORDER BY p.Nombre;

-- Vista: ClientesRegistrados
-- Descripción general:
-- Esta vista proporciona una lista de todos los clientes registrados en la base de datos
-- AmbarDiamond. Incluye información personal como nombre, apellidos, correo electrónico,
-- edad, sexo, teléfono, imagen, estado y el rol asignado.
-- Se utiliza para consultar rápidamente los datos de los clientes sin necesidad de hacer
-- joins complejos en la tabla Persona y Rol, facilitando la gestión y seguimiento de los usuarios.
CREATE OR REPLACE VIEW ClientesRegistrados AS
SELECT 
    p.idPersona AS idCliente,
    p.Nombre,
    p.ApellidoPaterno,
    p.ApellidoMaterno,
    p.Email,
    p.Edad,
    p.Sexo,
    p.Telefono,
    p.Imagen,
    p.Estatus AS Estado,
    r.NombreRol AS Rol,
    'Cliente' AS TipoCliente
FROM Persona p
JOIN Rol r ON p.idRol = r.idRol
WHERE p.idRol = 3;

-- Vista: PedidosCompletos
-- Descripción general:
-- Esta vista proporciona un detalle completo de todos los pedidos realizados en la
-- base de datos AmbarDiamond. Incluye información del pedido como ID, fecha, estatus
-- y el ID del cliente que lo realizó, así como los detalles de cada producto en el pedido:
-- ID del detalle, ID del producto, nombre del producto, cantidad, precio unitario y total.
-- Se utiliza para consultar y analizar los pedidos de manera integral, facilitando
-- la gestión de pedidos y la generación de reportes.
CREATE OR REPLACE VIEW PedidosCompletos AS
SELECT 
    pe.idPedido,
    pe.Fecha,
    pe.Estatus,
    pe.idPersona AS idCliente,
    CONCAT(per.Nombre, ' ', per.ApellidoPaterno, ' ', per.ApellidoMaterno) AS Usuario,
    dp.idDetallePedido,
    dp.idProducto,
    p.Nombre AS Producto,
    dp.Cantidad,
    dp.PrecioUnitario,
    dp.Total
FROM Pedido pe
JOIN DetallePedido dp ON pe.idPedido = dp.idPedido
JOIN Producto p ON dp.idProducto = p.idProducto
JOIN Persona per ON pe.idPersona = per.idPersona;

-- Vista: DevolucionesRealizadas
-- Descripción general:
-- Esta vista proporciona un detalle completo de todas las devoluciones registradas
-- en la base de datos AmbarDiamond. Incluye información de la devolución como ID, fecha
-- y motivo, así como los detalles de cada producto devuelto: ID del detalle de devolución,
-- ID de la venta, ID del detalle de venta, cantidad devuelta, total devuelto y nombre del producto.
-- Se utiliza para consultar y analizar devoluciones, facilitando el seguimiento de
-- productos retornados y la gestión de inventario.
CREATE OR REPLACE VIEW DevolucionesRealizadas AS
SELECT 
    d.idDevolucion,
    CONCAT(per.Nombre, ' ', per.ApellidoPaterno, ' ', per.ApellidoMaterno) AS Usuario,
    d.Fecha,
    d.Motivo,
    dd.idDetalleDevolucion,
    dd.idVenta,
    dd.idDetalleVenta,
    dd.CantidadDevuelta,
    dd.TotalDevuelto,
    p.Nombre AS NombreProducto
FROM Devolucion d
JOIN Persona per ON d.idPersona = per.idPersona
JOIN DetalleDevolucion dd ON d.idDevolucion = dd.idDevolucion
JOIN DetalleVenta dv ON dd.idDetalleVenta = dv.idDetalleVenta
JOIN Producto p ON dv.idProducto = p.idProducto;

-- Vista: VistaAuditoriaGeneral
-- Descripción general:
-- Esta vista proporciona un registro consolidado de auditorías realizadas en la base
-- de datos AmbarDiamond. Incluye auditorías de empleados, clientes, productos y devoluciones,
-- mostrando información como ID de auditoría, tipo de registro, movimiento realizado,
-- columna afectada, datos anteriores y nuevos, fecha de la acción, ID del usuario y
-- nombre completo, así como el rol del usuario responsable.
-- Se utiliza para supervisar y rastrear cambios en el sistema de manera centralizada,
-- facilitando el control de modificaciones y el seguimiento de acciones críticas.
CREATE OR REPLACE VIEW VistaAuditoriaGeneral AS
SELECT 
    a.idAuditoriaPersona AS idAuditoria,
    'Empleado' AS TipoRegistro,
    a.Movimiento,
    a.ColumnaAfectada,
    a.DatoAnterior,
    a.DatoNuevo,
    a.Fecha,
    p.idPersona AS idPersona,
    CONCAT(p.Nombre, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
    r.NombreRol AS RolUsuario
FROM AuditoriaPersona a
JOIN Persona p ON a.idPersona = p.idPersona
JOIN Rol r ON p.idRol = r.idRol

UNION ALL

-- Auditoría de Clientes
SELECT 
    a.idAuditoriaPersona AS idAuditoria,
    'Cliente' AS TipoRegistro,
    a.Movimiento,
    a.ColumnaAfectada,
    a.DatoAnterior,
    a.DatoNuevo,
    a.Fecha,
    p.idPersona AS idPersona,
    CONCAT(p.Nombre, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
    r.NombreRol AS RolUsuario
FROM AuditoriaPersona a
JOIN Persona p ON a.idPersona = p.idPersona
JOIN Rol r ON p.idRol = r.idRol

UNION ALL

-- Auditoría de Productos
SELECT 
    a.idAuditoriaProducto AS idAuditoria,
    'Producto' AS TipoRegistro,
    a.Movimiento,
    a.ColumnaAfectada,
    a.DatoAnterior,
    a.DatoNuevo,
    a.Fecha,
    a.idPersona AS idPersona,
    CONCAT(COALESCE(p.Nombre,''), ' ', COALESCE(p.ApellidoPaterno,''), ' ', COALESCE(p.ApellidoMaterno,'')) AS NombreCompleto,
    COALESCE(r.NombreRol, '') AS RolUsuario
FROM AuditoriaProducto a
LEFT JOIN Persona p ON a.idPersona = p.idPersona
LEFT JOIN Rol r ON p.idRol = r.idRol

UNION ALL

-- Auditoría de Devoluciones
SELECT 
    a.idAuditoriaDevolucion AS idAuditoria,
    'Devolución' AS TipoRegistro,
    a.Movimiento,
    a.ColumnaAfectada,
    a.DatoAnterior,
    a.DatoNuevo,
    a.Fecha,
    a.idPersona AS idPersona,
    CONCAT(COALESCE(p.Nombre,''), ' ', COALESCE(p.ApellidoPaterno,''), ' ', COALESCE(p.ApellidoMaterno,'')) AS NombreCompleto,
    COALESCE(r.NombreRol, '') AS RolUsuario
FROM AuditoriaDevolucion a
LEFT JOIN Persona p ON a.idPersona = p.idPersona
LEFT JOIN Rol r ON p.idRol = r.idRol;

-- Vista: VistaCarritoPorPersona
-- Descripción general:
-- Esta vista proporciona un detalle completo de los carritos de compras de cada persona
-- en la base de datos AmbarDiamond. Incluye información del usuario como ID y nombre completo,
-- así como los productos en su carrito: ID del carrito, ID del producto, nombre del producto,
-- imagen, cantidad, precio unitario, total, fecha de creación del carrito y estado del producto.
-- El estado se marca como 'Activo' si la cantidad es mayor a cero, o 'Inactivo' en caso contrario.
-- Se utiliza para consultar el contenido del carrito de cada usuario y facilitar la gestión
-- y seguimiento de productos antes de la compra.
CREATE OR REPLACE VIEW VistaCarritoPorPersona AS
SELECT
    per.idPersona,
    CONCAT(per.Nombre, ' ', per.ApellidoPaterno, ' ', per.ApellidoMaterno) AS NombrePersona,
    c.idCarrito,
    dc.idProducto,
    dc.NombreProducto AS Producto,
    dc.ImagenProducto,
    dc.Cantidad,
    dc.PrecioUnitario,
    dc.Total,
    c.FechaCreacion,
    CASE 
        WHEN dc.Cantidad > 0 THEN 'Activo'
        ELSE 'Inactivo'
    END AS EstadoProducto
FROM Carrito c
JOIN Persona per ON c.idPersona = per.idPersona
JOIN DetalleCarrito dc ON c.idCarrito = dc.idCarrito
ORDER BY per.idPersona, c.FechaCreacion, dc.NombreProducto;

-- Vista: VistaSugerenciasQuejas
-- Descripción general:
-- Esta vista proporciona un listado completo de todas las sugerencias y quejas registradas
-- en la base de datos AmbarDiamond. Incluye información como ID de la sugerencia o queja,
-- tipo, descripción, fecha de registro, ID del usuario que la realizó, nombre completo y rol.
-- Los registros se ordenan de manera descendente por fecha para mostrar primero los más recientes.
-- Se utiliza para gestionar y analizar retroalimentación de usuarios y facilitar la toma de decisiones
-- basadas en sus comentarios.
CREATE OR REPLACE VIEW VistaSugerenciasQuejas AS
SELECT 
    sq.idSugerenciaQueja,
    sq.Tipo,
    sq.Descripcion,
    sq.Fecha,
    p.idPersona,
    CONCAT(p.Nombre, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
    r.NombreRol AS RolUsuario
FROM SugerenciaQueja sq
JOIN Persona p ON sq.idPersona = p.idPersona
JOIN Rol r ON p.idRol = r.idRol
ORDER BY sq.Fecha DESC;

-- Vista: VistaProductosBajoStock
-- Descripción general:
-- Esta vista proporciona un listado de todos los productos en la base de datos AmbarDiamond
-- que tienen una existencia menor a 30 unidades. Incluye información como ID del producto,
-- nombre, precio de compra, precio de venta, código de barras, existencia actual, categoría
-- e imagen del producto.
-- Los resultados se ordenan por cantidad de existencia de manera ascendente para identificar
-- rápidamente los productos con bajo stock.
-- Se utiliza para gestionar inventario y tomar decisiones de reabastecimiento.
CREATE OR REPLACE VIEW VistaProductosBajoStock AS
SELECT 
    p.idProducto,
    p.Nombre,
    p.PrecioCompra,
    p.PrecioVenta,
    p.CodigoBarra,
    p.Existencia,
    c.Nombre AS Categoria,
    p.Imagen
FROM Producto p
JOIN Categoria c ON p.idCategoria = c.idCategoria
WHERE p.Existencia < 30
ORDER BY p.Existencia ASC;

-- Nueva Vista --
CREATE OR REPLACE VIEW VistaFinanzasPorFecha AS
SELECT
    f.idFinanzas,
    f.idVenta,
    v.Fecha AS FechaVenta,
    f.TotalVenta,
    f.TotalInvertido,
    f.Ganancia
FROM Finanzas f
INNER JOIN Venta v ON f.idVenta = v.idVenta;