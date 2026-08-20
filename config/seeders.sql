USE chemlook_pos;

-- Limpiar tablas antes de insertar
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE detalle_ventas;
TRUNCATE TABLE ventas;
TRUNCATE TABLE cajas;
TRUNCATE TABLE productos;
TRUNCATE TABLE categorias;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Insertar Categorías
INSERT INTO categorias (id, nombre) VALUES
(1, 'Desinfectantes y Sanitizantes'),
(2, 'Detergentes Industriales'),
(3, 'Cuidado Personal y Manos'),
(4, 'Limpieza de Superficies y Vidrios');

-- 2. Insertar Productos
INSERT INTO productos (categoria_id, codigo_barras, nombre, precio, stock) VALUES
(1, '7861000101', 'Desinfectante de Pino (Caneca 20L)', 24.50, 10),
(1, '7861000102', 'Desinfectante de Pino (Galón 4L)', 6.50, 25),
(1, '7861000103', 'Amonio Cuaternario 5ta Gen (1L)', 8.00, 30),
(2, '7861000201', 'Detergente Líquido Multiusos (Caneca 20L)', 28.00, 8),
(2, '7861000202', 'Desengrasante Industrial Heavy Duty (Galón 4L)', 12.50, 15),
(3, '7861000301', 'Jabón Líquido para Manos Neutro (1L)', 3.20, 50),
(3, '7861000302', 'Alcohol en Gel Antibacterial 70% (Galón 4L)', 9.00, 20),
(4, '7861000401', 'Limpia Vidrios con Atomizador (500ml)', 2.10, 40),
(4, '7861000402', 'Cera Autobrillante para Pisos (Galón 4L)', 11.00, 12);

-- 3. Abrir Caja Inicial para Pruebas
INSERT INTO cajas (monto_inicial, estado) VALUES (50.00, 'ABIERTA');