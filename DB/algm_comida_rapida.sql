

CREATE TABLE `caja` (
  `id` int UNSIGNED NOT NULL,
  `abierta` tinyint UNSIGNED NOT NULL,
  `abierta_fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `abierta_usuario_id` int UNSIGNED NOT NULL,
  `cerrada_fecha` datetime DEFAULT NULL,
  `cerrada_usuario_id` int UNSIGNED DEFAULT NULL,
  `monto_inicial` decimal(12,2) UNSIGNED NOT NULL,
  `monto_final` decimal(12,2) UNSIGNED DEFAULT NULL,
  `total_ventas` decimal(12,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `total_gastos` decimal(12,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `observaciones` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categoria_egreso_expensas` (
  `id` int UNSIGNED NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categoria_egreso_expensas` (`id`, `nombre`, `activo`) VALUES
	(1, 'Sueldos', 1),
	(2, 'Inventario', 1),
	(3, 'Servicios', 1),
	(4, 'Alquiler', 1),
	(5, 'Otros', 1);

CREATE TABLE `categoria_producto` (
  `id` int UNSIGNED NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categoria_producto` (`id`, `nombre`, `activo`) VALUES
	(1, 'comida', 1),
	(2, 'bebidas', 1),
	(3, 'combos', 1);

CREATE TABLE `combo_producto` (
  `id` int UNSIGNED NOT NULL,
  `combo_id` int UNSIGNED NOT NULL,
  `producto_id` int UNSIGNED NOT NULL,
  `cantidad` int UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `estado_pedido` (
  `id` int UNSIGNED NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `estado_pedido` (`id`, `nombre`) VALUES
	(1, 'pendiente'),
	(2, 'completado'),
	(3, 'anulado'),
	(4, 'eliminado');

CREATE TABLE empresa (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  cuit VARCHAR(20) DEFAULT NULL,
  direccion VARCHAR(255) DEFAULT NULL,
  telefono VARCHAR(50) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `metodo_pago` (
  `id` int UNSIGNED NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `metodo_pago` (`id`, `nombre`) VALUES
	(1, 'efectivo'),
	(2, 'transferencia'),
	(3, 'mixto');

CREATE TABLE `producto` (
  `id` int UNSIGNED NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio` decimal(12,2) UNSIGNED NOT NULL,
  `categoria_prod_id` int UNSIGNED NOT NULL,
  `stock_actual` int UNSIGNED NOT NULL DEFAULT '0',
  `activo` tinyint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tipo_movimiento_stock` (
  `id` int UNSIGNED NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tipo_movimiento_stock` (`id`, `nombre`) VALUES
	(1, 'entrada'),
	(2, 'salida');

CREATE TABLE `egresos` (
  `id` int UNSIGNED NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `categoria_id` int UNSIGNED NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int UNSIGNED NOT NULL,
  `caja_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pedidos` (
  `id` int UNSIGNED NOT NULL,
  `nombre_cliente` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_id` int UNSIGNED NOT NULL,
  `total` decimal(12,2) UNSIGNED NOT NULL,
  `total_efectivo` decimal(12,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `total_transferencia` decimal(12,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `metodo_pago_id` int UNSIGNED NOT NULL,
  `fecha_creado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `comprobante_img` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` int UNSIGNED NOT NULL,
  `caja_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detalle_pedidos` (
  `id` int UNSIGNED NOT NULL,
  `pedido_id` int UNSIGNED NOT NULL,
  `producto_id` int UNSIGNED NOT NULL,
  `precio` decimal(12,2) UNSIGNED NOT NULL,
  `cantidad` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `movimiento_stock` (
  `id` int UNSIGNED NOT NULL,
  `producto_id` int UNSIGNED NOT NULL,
  `pedido_id` int UNSIGNED DEFAULT NULL,
  `tipo_movimiento_id` int UNSIGNED NOT NULL,
  `cantidad` int UNSIGNED NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `reporte_diario` (
  `id` int UNSIGNED NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_ventas` decimal(12,2) UNSIGNED NOT NULL,
  `total_gastos` decimal(12,2) UNSIGNED NOT NULL,
  `balance` decimal(12,2) UNSIGNED NOT NULL,
  `cantidad_pedidos` int UNSIGNED NOT NULL,
  `caja_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




CREATE TABLE `usuarios` (
  `id` int UNSIGNED NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `activo` int NOT NULL DEFAULT '0',
  `roles` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `empresa_id` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `activo`, `roles`, `empresa_id`) VALUES
(1, 'AdminALGM', 'admin@algm-webs.com', '$2y$10$rvlxeoJMhomzGUJ/7865ZOdnWqvuoa7/9kFe5l3Ca9I2KQ.EG07sS', 1, 'ROLE_ADMIN', 1);


CREATE TABLE `pedido_historial` (
  `id` int UNSIGNED NOT NULL,
  `pedido_id` int UNSIGNED NOT NULL,
  `codigo` smallint UNSIGNED NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `acceso_categoria` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `orden` INT(11) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

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


----------------------------------------------------------------------

ALTER TABLE `caja`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `FK_caja_abierta_user` (`abierta_usuario_id`),
  ADD KEY `FK_caja_cerrada_user` (`cerrada_usuario_id`);

ALTER TABLE `categoria_egreso_expensas`
  ADD PRIMARY KEY (`id`) USING BTREE;

ALTER TABLE `categoria_producto`
  ADD PRIMARY KEY (`id`) USING BTREE;

ALTER TABLE `combo_producto`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `FKcombo_productos` (`combo_id`) USING BTREE,
  ADD KEY `FKproducto_combo` (`producto_id`) USING BTREE;

ALTER TABLE `detalle_pedidos`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `FK_detalle_pedido` (`pedido_id`),
  ADD KEY `FK_detalle_producto` (`producto_id`);

ALTER TABLE `egresos`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `FK_egreso_categoria` (`categoria_id`),
  ADD KEY `FK_egreso_usuario` (`usuario_id`),
  ADD KEY `FK_egreso_caja` (`caja_id`);

ALTER TABLE `estado_pedido`
  ADD PRIMARY KEY (`id`) USING BTREE;

ALTER TABLE `metodo_pago`
  ADD PRIMARY KEY (`id`) USING BTREE;

ALTER TABLE `movimiento_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_mov_stock_producto` (`producto_id`),
  ADD KEY `FK_mov_stock_pedido` (`pedido_id`),
  ADD KEY `FK_mov_stock_tipo` (`tipo_movimiento_id`);

ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `FK_pedido_estado` (`estado_id`),
  ADD KEY `FK_pedido_pago` (`metodo_pago_id`),
  ADD KEY `FK_pedido_usuario` (`usuario_id`),
  ADD KEY `FK_pedido_caja` (`caja_id`);

ALTER TABLE `pedido_historial`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `FKpedidos_historial-usuario` (`usuario_id`) USING BTREE,
  ADD KEY `FKpedidos_historial-pedido` (`pedido_id`) USING BTREE;

ALTER TABLE `producto`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `categoria_prod_id` (`categoria_prod_id`) USING BTREE;

ALTER TABLE `reporte_diario`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `FK_reporte_caja` (`caja_id`);

ALTER TABLE `tipo_movimiento_stock`
  ADD PRIMARY KEY (`id`) USING BTREE;

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `Index_Unico_email` (`email`) USING BTREE,
  ADD KEY `Index_email` (`email`) USING BTREE,
  ADD KEY `FK_usuarios_empresa` (`empresa_id`) USING BTREE;

ALTER TABLE `caja`
  ADD CONSTRAINT `FK_caja_abierta_user` FOREIGN KEY (`abierta_usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `FK_caja_cerrada_user` FOREIGN KEY (`cerrada_usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `combo_producto`
  ADD CONSTRAINT `FKcombo_productos` FOREIGN KEY (`combo_id`) REFERENCES `producto` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FKproducto_combo` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE CASCADE;

ALTER TABLE `detalle_pedidos`
  ADD CONSTRAINT `FK_detalle_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `FK_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`);

ALTER TABLE `egresos`
  ADD CONSTRAINT `FK_egreso_caja` FOREIGN KEY (`caja_id`) REFERENCES `caja` (`id`),
  ADD CONSTRAINT `FK_egreso_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categoria_egreso_expensas` (`id`),
  ADD CONSTRAINT `FK_egreso_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `movimiento_stock`
  ADD CONSTRAINT `FK_mov_stock_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `FK_mov_stock_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`),
  ADD CONSTRAINT `FK_mov_stock_tipo` FOREIGN KEY (`tipo_movimiento_id`) REFERENCES `tipo_movimiento_stock` (`id`);

ALTER TABLE `pedidos`
  ADD CONSTRAINT `FK_pedido_caja` FOREIGN KEY (`caja_id`) REFERENCES `caja` (`id`),
  ADD CONSTRAINT `FK_pedido_estado` FOREIGN KEY (`estado_id`) REFERENCES `estado_pedido` (`id`),
  ADD CONSTRAINT `FK_pedido_pago` FOREIGN KEY (`metodo_pago_id`) REFERENCES `metodo_pago` (`id`),
  ADD CONSTRAINT `FK_pedido_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `pedido_historial`
  ADD CONSTRAINT `FKpedidos_historial-pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `FKpedidos_historial-usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `producto`
  ADD CONSTRAINT `FKproducto_categoria` FOREIGN KEY (`categoria_prod_id`) REFERENCES `categoria_producto` (`id`);

ALTER TABLE `reporte_diario`
  ADD CONSTRAINT `FK_reporte_caja` FOREIGN KEY (`caja_id`) REFERENCES `caja` (`id`);

--------------------------------------------------------------------------------------
-- Relaciones a empresa
--

ALTER TABLE `producto`
	ADD COLUMN `empresa_id` INT UNSIGNED NOT NULL AFTER `activo`,
	ADD INDEX `empresa_id` (`empresa_id`),
	ADD CONSTRAINT `FKproducto_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE `pedidos`
	ADD COLUMN `empresa_id` INT UNSIGNED NOT NULL AFTER `caja_id`,
	ADD INDEX `empresa_id` (`empresa_id`),
	ADD CONSTRAINT `FKpedido_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE `detalle_pedidos`
	ADD COLUMN `empresa_id` INT UNSIGNED NOT NULL AFTER `cantidad`,
	ADD INDEX `empresa_id` (`empresa_id`),
	ADD CONSTRAINT `FKdetalle_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE `pedido_historial`
	ADD COLUMN `empresa_id` INT UNSIGNED NOT NULL AFTER `usuario_id`,
	ADD INDEX `empresa_id` (`empresa_id`),
	ADD CONSTRAINT `FKpedidos_historial-empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE `caja`
	ADD COLUMN `empresa_id` INT UNSIGNED NOT NULL AFTER `observaciones`,
	ADD INDEX `empresa_id` (`empresa_id`),
	ADD CONSTRAINT `FKcaja_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE `egresos`
	ADD COLUMN `empresa_id` INT UNSIGNED NOT NULL AFTER `caja_id`,
	ADD INDEX `empresa_id` (`empresa_id`),
	ADD CONSTRAINT `FK_egreso_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE `reporte_diario`
	ADD COLUMN `empresa_id` INT UNSIGNED NOT NULL AFTER `caja_id`,
	ADD INDEX `empresa_id` (`empresa_id`),
	ADD CONSTRAINT `FK_reporte_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE `movimiento_stock`
	ADD COLUMN `empresa_id` INT UNSIGNED NOT NULL AFTER `fecha`,
	ADD INDEX `empresa_id` (`empresa_id`),
	ADD CONSTRAINT `FKmov_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE `usuarios`
	ADD CONSTRAINT `FKusuario_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION;
