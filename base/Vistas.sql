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

CREATE OR REPLACE VIEW VistaProductosBajoStock AS
SELECT 
    p.idProducto,
    p.Nombre,
    p.PrecioCompra,
    p.PrecioVenta,
    p.CodigoBarra,
    p.Existencia,
    p.MinimoInventario,
    c.Nombre AS Categoria,
    p.Imagen,
    CASE
        WHEN p.Existencia <= (p.MinimoInventario / 2) THEN 'Crítico'
        WHEN p.Existencia < p.MinimoInventario THEN 'Bajo'
    END AS NivelInventario
FROM Producto p
JOIN Categoria c ON p.idCategoria = c.idCategoria
WHERE p.Existencia < p.MinimoInventario
ORDER BY p.Existencia ASC;


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

CREATE VIEW VistaNotificaciones AS
SELECT 
    n.idNotificacion,
    n.idProducto,
    p.Nombre AS NombreProducto,
    p.Existencia,
    p.MinimoInventario,
    n.Mensaje,
    n.Fecha
FROM NotificacionInventario n
INNER JOIN Producto p
    ON n.idProducto = p.idProducto
ORDER BY n.Fecha DESC;

CREATE VIEW VistaCategoriasSinImagen AS
SELECT 
    idCategoria,
    Nombre,
    Descripcion
FROM Categoria;

