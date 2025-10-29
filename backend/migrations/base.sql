-- Ejecutar antes de instalar


UPDATE usuarios SET activo = 0 WHERE activo = 10;

UPDATE usuarios SET activo = 1 WHERE activo = 20;

-- usuario
CREATE TABLE `usuarios` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_unicode_ci',
    `email` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8mb3_unicode_ci',
    `password` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8mb3_unicode_ci',
    `activo` INT(11) NOT NULL DEFAULT '0',
    `roles` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_unicode_ci',
    `empresa_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `Index_Unico_email` (`email`) USING BTREE,
    INDEX `Index_email` (`email`) USING BTREE,
    INDEX `FK_usuarios_empresa` (`empresa_id`) USING BTREE
)
COLLATE='utf8mb3_general_ci'
ENGINE=InnoDB;

-- categoria accesos
CREATE TABLE `acceso_categoria` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `orden` INT(11) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

-- accesos
CREATE TABLE `acceso_acceso` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `categoria_id` INT(10) UNSIGNED NOT NULL,
    `nombre` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `orden` SMALLINT(6) NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `FKacceso_acceso-categoria` (`categoria_id`) USING BTREE,
    CONSTRAINT `FKacceso_acceso-categoria` FOREIGN KEY (`categoria_id`) REFERENCES `acceso_categoria` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

-- usuario accesos
CREATE TABLE `usuario_accesos` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id` INT(10) UNSIGNED NOT NULL,
    `acceso_id` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `FKusuario_accesos-usuarios` (`usuario_id`) USING BTREE,
    INDEX `FKusuario_accesos-accesos` (`acceso_id`) USING BTREE,
    CONSTRAINT `FKusuario_accesos-accesos` FOREIGN KEY (`acceso_id`) REFERENCES `acceso_acceso` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT `FKusuario_accesos-usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;


-- Categoriá de los productos
CREATE TABLE `categoria_producto` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `activo` TINYINT(3) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

INSERT INTO categoria_producto (id, nombre, activo) VALUES
(1,'comida', 1),
(2,'bebidas', 1),
(3,'combos',1);


-- Tabla de productos
CREATE TABLE `producto` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `precio` DECIMAL(12,2) UNSIGNED NOT NULL,
    `categoria_prod_id` INT(10) UNSIGNED NOT NULL,
    `stock_actual` INT UNSIGNED NOT NULL DEFAULT 0,
    `activo` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `categoria_prod_id` (`categoria_prod_id`) USING BTREE,
    CONSTRAINT `FKproducto_categoria` FOREIGN KEY (`categoria_prod_id`) REFERENCES `categoria_producto` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

-- TODO: Pedidos cuenta con el guardado de imagen del comprobante si es "transferencia"
-- TODO: Pedidos cuenta con descuento de stock de los items <- crear movimiento de stock

-- categoria de los egresos
CREATE TABLE `categoria_egreso_expensas` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(100) NOT NULL,
    `activo` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

INSERT INTO categoria_egreso_expensas (id, nombre, activo) VALUES
(1, 'sueldos', 1),
(2, 'inventario', 1),
(3, 'servicios', 1),
(4, 'alquiler', 1),
(5, 'otros', 1);


-- egresos
CREATE TABLE `egresos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `monto` DECIMAL(12,2) NOT NULL,
    `categoria_id` INT UNSIGNED NOT NULL,
    `descripcion` TEXT NOT NULL,
    `fecha` DATE NOT NULL,
    `usuario_id` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    CONSTRAINT `FK_egreso_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categoria_egreso_expensas` (`id`),
    CONSTRAINT `FK_egreso_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

-- metodo pago
CREATE TABLE `metodo_pago` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

INSERT INTO metodo_pago (id, nombre) VALUES
(1,'efectivo'),
(2,'transferencia');

-- estado pedido
CREATE TABLE `estado_pedido` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

INSERT INTO estado_pedido (nombre) VALUES
('pendiente'), ('completado'), ('anulado');

-- pedidos
CREATE TABLE `pedidos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre_cliente` VARCHAR(255) NOT NULL,
    `estado_id` INT(10) UNSIGNED NOT NULL,
    `total` DECIMAL(12,2) UNSIGNED NOT NULL,
    `metodo_pago_id` INT(10) UNSIGNED NOT NULL,
    `fecha_creado` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `comprobante_img` VARCHAR(255) NULL,
    `usuario_id` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    CONSTRAINT `FK_pedido_estado` FOREIGN KEY (`estado_id`) REFERENCES `estado_pedido` (`id`),
    CONSTRAINT `FK_pedido_pago` FOREIGN KEY (`metodo_pago_id`) REFERENCES `metodo_pago` (`id`),
    CONSTRAINT `FK_pedido_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

-- detalle del pedido
CREATE TABLE `detalle_pedidos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pedido_id` INT(10) UNSIGNED NOT NULL,
    `producto_id` INT(10) UNSIGNED NOT NULL,
    `precio` DECIMAL(12,2) UNSIGNED NOT NULL,
    `cantidad` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    CONSTRAINT `FK_detalle_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
    CONSTRAINT `FK_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

-- caja
CREATE TABLE `caja` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `abierta` TINYINT(3) UNSIGNED NOT NULL,
    `abierta_fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `abierta_usuario_id` INT(10) UNSIGNED NOT NULL,
    `cerrada_fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `cerrada_usuario_id` INT(10) UNSIGNED NULL,
    `monto_inicial` DECIMAL(12,2) UNSIGNED NOT NULL,
    `monto_final` DECIMAL(12,2) UNSIGNED NULL,
    `total_ventas` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    `total_gastos` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    `observaciones` TEXT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    CONSTRAINT `FK_caja_abierta_user` FOREIGN KEY (`abierta_usuario_id`) REFERENCES `usuarios` (`id`),
    CONSTRAINT `FK_caja_cerrada_user` FOREIGN KEY (`cerrada_usuario_id`) REFERENCES `usuarios` (`id`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

-- reportes
CREATE TABLE `reporte_diario` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    `total_ventas` DECIMAL(12,2) UNSIGNED NOT NULL,
    `total_gastos` DECIMAL(12,2) UNSIGNED NOT NULL,
    `balance` DECIMAL(12,2) UNSIGNED NOT NULL,
    `cantidad_pedidos` INT(10) UNSIGNED NOT NULL,
    `caja_id` INT(10) UNSIGNED NOT NULL,

    PRIMARY KEY (`id`) USING BTREE,
    CONSTRAINT `FK_reporte_caja` FOREIGN KEY (`caja_id`) REFERENCES `caja` (`id`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

-- tipo de movimiento
CREATE TABLE `tipo_movimiento_stock` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_unicode_ci',
    PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

INSERT INTO tipo_movimiento_stock (id, nombre) VALUES
(1, 'entrada'),
(2, 'salida');

-- movimientos de stock
CREATE TABLE `movimiento_stock` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `producto_id` INT UNSIGNED NOT NULL,
    `pedido_id` INT UNSIGNED NULL,
    `tipo_movimiento_id` INT UNSIGNED NOT NULL,
    `cantidad` INT UNSIGNED NOT NULL,
    `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `FK_mov_stock_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`),
    CONSTRAINT `FK_mov_stock_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
    CONSTRAINT `FK_mov_stock_tipo` FOREIGN KEY (`tipo_movimiento_id`) REFERENCES `tipo_movimiento_stock` (`id`)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

-- 4
ALTER TABLE `egresos`
    CHANGE COLUMN `fecha` `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP() AFTER `descripcion`;

-- 5
ALTER TABLE `caja`
    CHANGE COLUMN `cerrada_fecha` `cerrada_fecha` DATETIME NULL AFTER `abierta_usuario_id`;

-- 6
CREATE TABLE `pedido_historial` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `pedido_id` INT(10) UNSIGNED NOT NULL,
    `detalle_id` INT(10) UNSIGNED NULL DEFAULT NULL,
    `codigo` SMALLINT(5) UNSIGNED NOT NULL,
    `fecha` DATETIME NOT NULL DEFAULT current_timestamp(),
    `usuario_id` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `FKpedidos_historial-usuario` (`usuario_id`) USING BTREE,
    INDEX `FKpedidos_historial-pedido` (`pedido_id`) USING BTREE,
    INDEX `FKpedidos_historial-pedido_detalle` (`detalle_id`) USING BTREE,
    CONSTRAINT `FKpedidos_historial-pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT `FKpedidos_historial-pedido_detalle` FOREIGN KEY (`detalle_id`) REFERENCES `detalle_pedidos` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
    CONSTRAINT `FKpedidos_historial-usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

ALTER TABLE `pedido_historial`
    DROP COLUMN `detalle_id`,
    DROP FOREIGN KEY `FKpedidos_historial-pedido_detalle`;

INSERT INTO `estado_pedido` (`id`, `nombre`) VALUES (4, 'eliminado');

ALTER TABLE `pedidos`
    ADD COLUMN `caja_id` INT UNSIGNED NOT NULL AFTER `usuario_id`,
    ADD CONSTRAINT `FK_pedido_caja` FOREIGN KEY (`caja_id`) REFERENCES `caja` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION;


-- INSERT INTO `algm_carri_elarco`.`usuarios` (`id`, `nombre`, `email`, `password`, `activo`, `roles`, `empresa_id`) VALUES
-- (2, 'Prueba', 'carri@prueba.com', '$2y$10$p1vkiPjA8Tbi5J/JcpvuEe82OcYNGAhVNOEbV5Nh2O/Z7jlnGlI7C', 1, 'ROLE_ADMIN', 1);

-- identificar egresos por caja
ALTER TABLE `egresos`
    ADD COLUMN `caja_id` INT UNSIGNED NOT NULL AFTER `usuario_id`,
    ADD CONSTRAINT `FK_egreso_caja` FOREIGN KEY (`caja_id`) REFERENCES `caja` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION;

-- nueva tabla para los combos, de esta forma relaciona el stock con los productos individuales
CREATE TABLE `combo_producto` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `combo_id` INT(10) UNSIGNED NOT NULL,
    `producto_id` INT(10) UNSIGNED NOT NULL,
    `cantidad` INT(10) UNSIGNED NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `FKcombo_productos` (`combo_id`) USING BTREE,
    INDEX `FKproducto_combo` (`producto_id`) USING BTREE,
    CONSTRAINT `FKcombo_productos` FOREIGN KEY (`combo_id`) REFERENCES `producto` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
    CONSTRAINT `FKproducto_combo` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

-- 19 Detalles sobre el total del dinero destinado al pedido
ALTER TABLE `pedidos`
    ADD COLUMN `total_efectivo` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0 AFTER `total`,
  ADD COLUMN `total_transferencia` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0 AFTER `total_efectivo`;

INSERT INTO `algm_carri_elarco`.`metodo_pago` (`id`, `nombre`) VALUES (3, 'mixto');