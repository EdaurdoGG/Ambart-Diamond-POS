CREATE DATABASE AmbarDiamond;
USE AmbarDiamond;

CREATE TABLE Rol (
    idRol INT AUTO_INCREMENT PRIMARY KEY,
    NombreRol VARCHAR(50) NOT NULL,
    Descripcion VARCHAR(200)
);

CREATE TABLE Persona (
    idPersona INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    ApellidoPaterno VARCHAR(100) NOT NULL,
    ApellidoMaterno VARCHAR(100),
    Telefono VARCHAR(15),
    Email VARCHAR(150) UNIQUE,
    Edad INT CHECK (Edad >= 18),
    Sexo ENUM('M','F','Otro') NOT NULL,
    Estatus ENUM('Activo','Inactivo') DEFAULT 'Activo',
    Usuario VARCHAR(50) UNIQUE NOT NULL,
    Contrasena VARCHAR(255) NOT NULL,
    Imagen VARCHAR(255) NULL,
    idRol INT NOT NULL,
    FOREIGN KEY (idRol) REFERENCES Rol(idRol)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE AuditoriaPersona (
    idAuditoriaPersona INT AUTO_INCREMENT PRIMARY KEY,
    Movimiento VARCHAR(50) NOT NULL,
    ColumnaAfectada VARCHAR(100),
    DatoAnterior TEXT,
    DatoNuevo TEXT,
    Fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    idPersona INT,
    FOREIGN KEY (idPersona) REFERENCES Persona(idPersona)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE Categoria (
    idCategoria INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Descripcion VARCHAR(200),
    Imagen VARCHAR(255)
);

CREATE TABLE Producto (
    idProducto INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(150) NOT NULL,
    PrecioCompra DECIMAL(10,2) NOT NULL CHECK (PrecioCompra >= 0),
    PrecioVenta DECIMAL(10,2) NOT NULL CHECK (PrecioVenta >= 0),
    CodigoBarra VARCHAR(100) UNIQUE,
    Existencia INT NOT NULL CHECK (Existencia >= 0),
    idCategoria INT NOT NULL,
    Imagen VARCHAR(255),
    MinimoInventario INT NOT NULL DEFAULT 30,
    FOREIGN KEY (idCategoria) REFERENCES Categoria(idCategoria)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE AuditoriaProducto (
    idAuditoriaProducto INT AUTO_INCREMENT PRIMARY KEY,
    Movimiento VARCHAR(50) NOT NULL,
    ColumnaAfectada VARCHAR(100),
    DatoAnterior TEXT,
    DatoNuevo TEXT,
    Fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    idPersona INT,
    FOREIGN KEY (idPersona) REFERENCES Persona(idPersona)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE Venta (
    idVenta INT AUTO_INCREMENT PRIMARY KEY,
    Fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    TipoPago ENUM('Efectivo','Tarjeta') NOT NULL,
    Estatus ENUM('Activa','Cancelada','Devuelta') DEFAULT 'Activa',
    idPersona INT NOT NULL,
    FOREIGN KEY (idPersona) REFERENCES Persona(idPersona)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE DetalleVenta (
    idDetalleVenta INT AUTO_INCREMENT PRIMARY KEY,
    idVenta INT NOT NULL,
    idProducto INT NOT NULL,
    Cantidad INT NOT NULL CHECK (Cantidad > 0),
    PrecioUnitario DECIMAL(10,2) NOT NULL,
    Total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (idVenta) REFERENCES Venta(idVenta)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (idProducto) REFERENCES Producto(idProducto)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE Carrito (
    idCarrito INT AUTO_INCREMENT PRIMARY KEY,
    idPersona INT NOT NULL,
    FechaCreacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idPersona) REFERENCES Persona(idPersona)
        ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE DetalleCarrito (
    idDetalleCarrito INT AUTO_INCREMENT PRIMARY KEY,
    idCarrito INT NOT NULL,
    idProducto INT NOT NULL,
    NombreProducto VARCHAR(150) NOT NULL,
    ImagenProducto VARCHAR(255),
    Cantidad INT NOT NULL CHECK (Cantidad > 0),
    PrecioUnitario DECIMAL(10,2) NOT NULL,
    Total DECIMAL(10,2) GENERATED ALWAYS AS (Cantidad * PrecioUnitario) STORED,
    FOREIGN KEY (idCarrito) REFERENCES Carrito(idCarrito)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (idProducto) REFERENCES Producto(idProducto)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE Pedido (
    idPedido INT AUTO_INCREMENT PRIMARY KEY,
    Fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Estatus ENUM('Pendiente','Atendido','Cancelado') DEFAULT 'Pendiente',
    idPersona INT NOT NULL,
    FOREIGN KEY (idPersona) REFERENCES Persona(idPersona)
        ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE DetallePedido (
    idDetallePedido INT AUTO_INCREMENT PRIMARY KEY,
    idPedido INT NOT NULL,
    idProducto INT NOT NULL,
    Cantidad INT NOT NULL CHECK (Cantidad > 0),
    PrecioUnitario DECIMAL(10,2) NOT NULL,
    Total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (idPedido) REFERENCES Pedido(idPedido)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (idProducto) REFERENCES Producto(idProducto)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE Devolucion (
    idDevolucion INT AUTO_INCREMENT PRIMARY KEY,
    Fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Motivo VARCHAR(200),
    idPersona INT,
    FOREIGN KEY (idPersona) REFERENCES Persona(idPersona)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE DetalleDevolucion (
    idDetalleDevolucion INT AUTO_INCREMENT PRIMARY KEY,
    idDevolucion INT NOT NULL,
    idVenta INT NOT NULL,
    idDetalleVenta INT NOT NULL,
    CantidadDevuelta INT NOT NULL CHECK (CantidadDevuelta > 0),
    TotalDevuelto DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (idDevolucion) REFERENCES Devolucion(idDevolucion)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (idVenta) REFERENCES Venta(idVenta)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (idDetalleVenta) REFERENCES DetalleVenta(idDetalleVenta)
        ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE AuditoriaDevolucion (
    idAuditoriaDevolucion INT AUTO_INCREMENT PRIMARY KEY,
    Movimiento VARCHAR(50) NOT NULL,        
    ColumnaAfectada VARCHAR(100),           
    DatoAnterior TEXT,                      
    DatoNuevo TEXT,                         
    Fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    idPersona INT,                          
    FOREIGN KEY (idPersona) REFERENCES Persona(idPersona)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE Finanzas (
    idFinanzas INT AUTO_INCREMENT PRIMARY KEY,
    idVenta INT NOT NULL UNIQUE,
    TotalVenta DECIMAL(10,2) NOT NULL,
    TotalInvertido DECIMAL(10,2) NOT NULL,
    Ganancia DECIMAL(10,2) GENERATED ALWAYS AS (TotalVenta - TotalInvertido) STORED,
    FOREIGN KEY (idVenta) REFERENCES Venta(idVenta)
        ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE SugerenciaQueja (
    idSugerenciaQueja INT AUTO_INCREMENT PRIMARY KEY,
    idPersona INT NOT NULL,
    Tipo ENUM('Sugerencia','Queja') NOT NULL,
    Descripcion VARCHAR(255) NOT NULL,
    Fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idPersona) REFERENCES Persona(idPersona)
        ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE NotificacionInventario (
    idNotificacion INT AUTO_INCREMENT PRIMARY KEY,
    idProducto INT NOT NULL,
    Mensaje VARCHAR(255) NOT NULL,
    Fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idProducto) REFERENCES Producto(idProducto)
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- Insertar los roles y el primer usuario administrador
-- Este paso debe realizarse **después de crear las tablas** pero **antes de definir triggers, vistas y procedimientos almacenados**, 
-- para asegurar que los triggers que dependen de roles o del primer usuario no generen errores.

INSERT INTO Rol (NombreRol, Descripcion)
VALUES 
    ('Administrador', 'Usuario con todos los permisos del sistema'),
    ('Empleado', 'Usuario con permisos limitados para operaciones diarias'),
    ('Usuario', 'Cliente o usuario final sin permisos administrativos');

INSERT INTO Persona (
    Nombre, ApellidoPaterno, ApellidoMaterno, Telefono, Email, Edad, Sexo, Estatus, Usuario, Contrasena, idRol
)
VALUES (
    'Reyna', 'Guzman', 'Yepez', '1112223344', 'admin@ambar.com', 30, 'F', 'Activo', 'admin', '12345', 1
);