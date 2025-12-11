-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         11.5.2-MariaDB - mariadb.org binary distribution
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.6.0.6765
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Volcando estructura para tabla algm_comidas_rapidas.acceso_acceso
CREATE TABLE IF NOT EXISTS `acceso_acceso` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `categoria_id` int(10) unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `orden` smallint(6) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `FKacceso_acceso-categoria` (`categoria_id`) USING BTREE,
  CONSTRAINT `FKacceso_acceso-categoria` FOREIGN KEY (`categoria_id`) REFERENCES `acceso_categoria` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.acceso_acceso: ~0 rows (aproximadamente)

-- Volcando estructura para tabla algm_comidas_rapidas.acceso_categoria
CREATE TABLE IF NOT EXISTS `acceso_categoria` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.acceso_categoria: ~0 rows (aproximadamente)

-- Volcando estructura para tabla algm_comidas_rapidas.caja
CREATE TABLE IF NOT EXISTS `caja` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `abierta` tinyint(3) unsigned NOT NULL,
  `abierta_fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `abierta_usuario_id` int(10) unsigned NOT NULL,
  `cerrada_fecha` datetime DEFAULT NULL,
  `cerrada_usuario_id` int(10) unsigned DEFAULT NULL,
  `monto_inicial` decimal(12,2) unsigned NOT NULL,
  `monto_final` decimal(12,2) unsigned DEFAULT NULL,
  `total_ventas` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `total_gastos` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `empresa_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `FK_caja_abierta_user` (`abierta_usuario_id`),
  KEY `FK_caja_cerrada_user` (`cerrada_usuario_id`),
  KEY `empresa_id` (`empresa_id`),
  CONSTRAINT `FK_caja_abierta_user` FOREIGN KEY (`abierta_usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `FK_caja_cerrada_user` FOREIGN KEY (`cerrada_usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `FKcaja_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.caja: ~2 rows (aproximadamente)
INSERT INTO `caja` (`id`, `abierta`, `abierta_fecha`, `abierta_usuario_id`, `cerrada_fecha`, `cerrada_usuario_id`, `monto_inicial`, `monto_final`, `total_ventas`, `total_gastos`, `observaciones`, `empresa_id`) VALUES
	(1, 1, '2025-12-10 22:11:19', 1, NULL, NULL, 666.00, NULL, 0.00, 0.00, NULL, 1),
	(2, 1, '2025-12-10 22:16:23', 2, NULL, NULL, 999.00, NULL, 0.00, 0.00, NULL, 2);

-- Volcando estructura para tabla algm_comidas_rapidas.categoria_egreso_expensas
CREATE TABLE IF NOT EXISTS `categoria_egreso_expensas` (
  `id` int(10) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.categoria_egreso_expensas: ~5 rows (aproximadamente)
INSERT INTO `categoria_egreso_expensas` (`id`, `nombre`, `activo`) VALUES
	(1, 'Sueldos', 1),
	(2, 'Inventario', 1),
	(3, 'Servicios', 1),
	(4, 'Alquiler', 1),
	(5, 'Otros', 1);

-- Volcando estructura para tabla algm_comidas_rapidas.categoria_producto
CREATE TABLE IF NOT EXISTS `categoria_producto` (
  `id` int(10) unsigned NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.categoria_producto: ~3 rows (aproximadamente)
INSERT INTO `categoria_producto` (`id`, `nombre`, `activo`) VALUES
	(1, 'comida', 1),
	(2, 'bebidas', 1),
	(3, 'combos', 1);

-- Volcando estructura para tabla algm_comidas_rapidas.combo_producto
CREATE TABLE IF NOT EXISTS `combo_producto` (
  `id` int(10) unsigned NOT NULL,
  `combo_id` int(10) unsigned NOT NULL,
  `producto_id` int(10) unsigned NOT NULL,
  `cantidad` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `FKcombo_productos` (`combo_id`) USING BTREE,
  KEY `FKproducto_combo` (`producto_id`) USING BTREE,
  CONSTRAINT `FKcombo_productos` FOREIGN KEY (`combo_id`) REFERENCES `producto` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FKproducto_combo` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.combo_producto: ~0 rows (aproximadamente)

-- Volcando estructura para tabla algm_comidas_rapidas.detalle_pedidos
CREATE TABLE IF NOT EXISTS `detalle_pedidos` (
  `id` int(10) unsigned NOT NULL,
  `pedido_id` int(10) unsigned NOT NULL,
  `producto_id` int(10) unsigned NOT NULL,
  `precio` decimal(12,2) unsigned NOT NULL,
  `cantidad` int(10) unsigned NOT NULL,
  `empresa_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `FK_detalle_pedido` (`pedido_id`),
  KEY `FK_detalle_producto` (`producto_id`),
  KEY `empresa_id` (`empresa_id`),
  CONSTRAINT `FK_detalle_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  CONSTRAINT `FK_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`),
  CONSTRAINT `FKdetalle_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.detalle_pedidos: ~0 rows (aproximadamente)

-- Volcando estructura para tabla algm_comidas_rapidas.egresos
CREATE TABLE IF NOT EXISTS `egresos` (
  `id` int(10) unsigned NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `categoria_id` int(10) unsigned NOT NULL,
  `descripcion` text NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(10) unsigned NOT NULL,
  `caja_id` int(10) unsigned NOT NULL,
  `empresa_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `FK_egreso_categoria` (`categoria_id`),
  KEY `FK_egreso_usuario` (`usuario_id`),
  KEY `FK_egreso_caja` (`caja_id`),
  KEY `empresa_id` (`empresa_id`),
  CONSTRAINT `FK_egreso_caja` FOREIGN KEY (`caja_id`) REFERENCES `caja` (`id`),
  CONSTRAINT `FK_egreso_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categoria_egreso_expensas` (`id`),
  CONSTRAINT `FK_egreso_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FK_egreso_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.egresos: ~0 rows (aproximadamente)

-- Volcando estructura para tabla algm_comidas_rapidas.empresa
CREATE TABLE IF NOT EXISTS `empresa` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `cuit` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `creada_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.empresa: ~1 rows (aproximadamente)
INSERT INTO `empresa` (`id`, `nombre`, `cuit`, `direccion`, `telefono`, `email`, `activa`, `creada_en`) VALUES
	(1, 'ALGM Sitios Web', '23409738389', 'Avellaneda 541 Marcos Juarez', '3534191311', 'sitiosweb-algm@hotmail.com', 1, '2025-10-29 23:11:08'),
	(2, 'ALGM Prueba', '23422456219', 'Avellaneda 541 Marcos Juarez', '3472343839', 'sitiosweb-prueba@hotmail.com', 1, '2025-12-10 21:05:48');

-- Volcando estructura para tabla algm_comidas_rapidas.estado_pedido
CREATE TABLE IF NOT EXISTS `estado_pedido` (
  `id` int(10) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.estado_pedido: ~4 rows (aproximadamente)
INSERT INTO `estado_pedido` (`id`, `nombre`) VALUES
	(1, 'pendiente'),
	(2, 'completado'),
	(3, 'anulado'),
	(4, 'eliminado');

-- Volcando estructura para tabla algm_comidas_rapidas.metodo_pago
CREATE TABLE IF NOT EXISTS `metodo_pago` (
  `id` int(10) unsigned NOT NULL,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.metodo_pago: ~3 rows (aproximadamente)
INSERT INTO `metodo_pago` (`id`, `nombre`) VALUES
	(1, 'efectivo'),
	(2, 'transferencia'),
	(3, 'mixto');

-- Volcando estructura para tabla algm_comidas_rapidas.movimiento_stock
CREATE TABLE IF NOT EXISTS `movimiento_stock` (
  `id` int(10) unsigned NOT NULL,
  `producto_id` int(10) unsigned NOT NULL,
  `pedido_id` int(10) unsigned DEFAULT NULL,
  `tipo_movimiento_id` int(10) unsigned NOT NULL,
  `cantidad` int(10) unsigned NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `empresa_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_mov_stock_producto` (`producto_id`),
  KEY `FK_mov_stock_pedido` (`pedido_id`),
  KEY `FK_mov_stock_tipo` (`tipo_movimiento_id`),
  KEY `empresa_id` (`empresa_id`),
  CONSTRAINT `FK_mov_stock_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  CONSTRAINT `FK_mov_stock_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`),
  CONSTRAINT `FK_mov_stock_tipo` FOREIGN KEY (`tipo_movimiento_id`) REFERENCES `tipo_movimiento_stock` (`id`),
  CONSTRAINT `FKmov_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.movimiento_stock: ~0 rows (aproximadamente)

-- Volcando estructura para tabla algm_comidas_rapidas.pedidos
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` int(10) unsigned NOT NULL,
  `nombre_cliente` varchar(255) NOT NULL,
  `estado_id` int(10) unsigned NOT NULL,
  `total` decimal(12,2) unsigned NOT NULL,
  `total_efectivo` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `total_transferencia` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `metodo_pago_id` int(10) unsigned NOT NULL,
  `fecha_creado` datetime NOT NULL DEFAULT current_timestamp(),
  `comprobante_img` varchar(255) DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `caja_id` int(10) unsigned NOT NULL,
  `empresa_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `FK_pedido_estado` (`estado_id`),
  KEY `FK_pedido_pago` (`metodo_pago_id`),
  KEY `FK_pedido_usuario` (`usuario_id`),
  KEY `FK_pedido_caja` (`caja_id`),
  KEY `empresa_id` (`empresa_id`),
  CONSTRAINT `FK_pedido_caja` FOREIGN KEY (`caja_id`) REFERENCES `caja` (`id`),
  CONSTRAINT `FK_pedido_estado` FOREIGN KEY (`estado_id`) REFERENCES `estado_pedido` (`id`),
  CONSTRAINT `FK_pedido_pago` FOREIGN KEY (`metodo_pago_id`) REFERENCES `metodo_pago` (`id`),
  CONSTRAINT `FK_pedido_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `FKpedido_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.pedidos: ~0 rows (aproximadamente)

-- Volcando estructura para tabla algm_comidas_rapidas.pedido_historial
CREATE TABLE IF NOT EXISTS `pedido_historial` (
  `id` int(10) unsigned NOT NULL,
  `pedido_id` int(10) unsigned NOT NULL,
  `codigo` smallint(5) unsigned NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(10) unsigned NOT NULL,
  `empresa_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `FKpedidos_historial-usuario` (`usuario_id`) USING BTREE,
  KEY `FKpedidos_historial-pedido` (`pedido_id`) USING BTREE,
  KEY `empresa_id` (`empresa_id`),
  CONSTRAINT `FKpedidos_historial-empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FKpedidos_historial-pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  CONSTRAINT `FKpedidos_historial-usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.pedido_historial: ~0 rows (aproximadamente)

-- Volcando estructura para tabla algm_comidas_rapidas.producto
CREATE TABLE IF NOT EXISTS `producto` (
  `id` int(10) unsigned NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `precio` decimal(12,2) unsigned NOT NULL,
  `categoria_prod_id` int(10) unsigned NOT NULL,
  `stock_actual` int(10) unsigned NOT NULL DEFAULT 0,
  `activo` tinyint(3) unsigned DEFAULT NULL,
  `empresa_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `categoria_prod_id` (`categoria_prod_id`) USING BTREE,
  KEY `empresa_id` (`empresa_id`),
  CONSTRAINT `FKproducto_categoria` FOREIGN KEY (`categoria_prod_id`) REFERENCES `categoria_producto` (`id`),
  CONSTRAINT `FKproducto_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.producto: ~1 rows (aproximadamente)
INSERT INTO `producto` (`id`, `nombre`, `precio`, `categoria_prod_id`, `stock_actual`, `activo`, `empresa_id`) VALUES
	(1, 'Producto', 1500.00, 1, 25, 1, 1);

-- Volcando estructura para tabla algm_comidas_rapidas.reporte_diario
CREATE TABLE IF NOT EXISTS `reporte_diario` (
  `id` int(10) unsigned NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `total_ventas` decimal(12,2) unsigned NOT NULL,
  `total_gastos` decimal(12,2) unsigned NOT NULL,
  `balance` decimal(12,2) unsigned NOT NULL,
  `cantidad_pedidos` int(10) unsigned NOT NULL,
  `caja_id` int(10) unsigned NOT NULL,
  `empresa_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `FK_reporte_caja` (`caja_id`),
  KEY `empresa_id` (`empresa_id`),
  CONSTRAINT `FK_reporte_caja` FOREIGN KEY (`caja_id`) REFERENCES `caja` (`id`),
  CONSTRAINT `FK_reporte_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.reporte_diario: ~0 rows (aproximadamente)

-- Volcando estructura para tabla algm_comidas_rapidas.tipo_movimiento_stock
CREATE TABLE IF NOT EXISTS `tipo_movimiento_stock` (
  `id` int(10) unsigned NOT NULL,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.tipo_movimiento_stock: ~2 rows (aproximadamente)
INSERT INTO `tipo_movimiento_stock` (`id`, `nombre`) VALUES
	(1, 'entrada'),
	(2, 'salida');

-- Volcando estructura para tabla algm_comidas_rapidas.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(10) unsigned NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '',
  `activo` int(11) NOT NULL DEFAULT 0,
  `roles` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `empresa_id` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `Index_Unico_email` (`email`) USING BTREE,
  KEY `Index_email` (`email`) USING BTREE,
  KEY `FK_usuarios_empresa` (`empresa_id`) USING BTREE,
  CONSTRAINT `FKusuario_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.usuarios: ~2 rows (aproximadamente)
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `activo`, `roles`, `empresa_id`) VALUES
	(1, 'AdminALGM', 'admin@algm-webs.com', '$2y$10$rvlxeoJMhomzGUJ/7865ZOdnWqvuoa7/9kFe5l3Ca9I2KQ.EG07sS', 1, 'ROLE_ADMIN', 1),
	(2, 'Prueba', 'prueba@prueba.com', '$2y$10$p1vkiPjA8Tbi5J/JcpvuEe82OcYNGAhVNOEbV5Nh2O/Z7jlnGlI7C', 1, 'ROLE_ADMIN', 2);

-- Volcando estructura para tabla algm_comidas_rapidas.usuario_accesos
CREATE TABLE IF NOT EXISTS `usuario_accesos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `acceso_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `FKusuario_accesos-usuarios` (`usuario_id`) USING BTREE,
  KEY `FKusuario_accesos-accesos` (`acceso_id`) USING BTREE,
  CONSTRAINT `FKusuario_accesos-accesos` FOREIGN KEY (`acceso_id`) REFERENCES `acceso_acceso` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FKusuario_accesos-usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla algm_comidas_rapidas.usuario_accesos: ~0 rows (aproximadamente)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
