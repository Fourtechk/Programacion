-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-10-2025 a las 05:01:39
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema`
--
CREATE DATABASE IF NOT EXISTS `sistema` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sistema`;
-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horas`
--

CREATE TABLE `horas` (
  `id_horas` int(11) NOT NULL,
  `semanales_req` int(11) NOT NULL,
  `cumplidas` int(11) DEFAULT 0,
  `fecha_t` date DEFAULT NULL,
  `id_miembro` int(11) DEFAULT NULL,
  `horas_pendientes` int(11) DEFAULT 0,
  `justificativos` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horas`
--

INSERT INTO `horas` (`id_horas`, `semanales_req`, `cumplidas`, `fecha_t`, `id_miembro`, `horas_pendientes`, `justificativos`) VALUES
(1, 21, 0, NULL, 1, 0, ''),
(2, 21, 0, NULL, 2, 0, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `miembro`
--

CREATE TABLE `miembro` (
  `id_miembro` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `aprobado` tinyint(1) DEFAULT 0,
  `es_miembro` tinyint(1) DEFAULT 0,
  `admin` tinyint(1) DEFAULT 0,
  `estado` varchar(100) DEFAULT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `foto_perfil` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `miembro`
--

INSERT INTO `miembro` (`id_miembro`, `nombre`, `email`, `password`, `fecha_nacimiento`, `aprobado`, `es_miembro`, `admin`, `estado`, `id_unidad`, `fecha_ingreso`, `foto_perfil`) VALUES
(1, 'Administrador General', 'admin@gmail.com', '$2y$10$EB5bCH08G3dluMHJttBIVOBaLfcA7r40Fp4ttkKghE7kv6t2MTsle', '2000-11-20', 1, 1, 1, 'activo', 1, '2025-01-01', 'perfiles/perfil_1_1762437313.jpg'),
(2, 'Miembro', 'miembro@gmail.com', '$2y$10$EB5bCH08G3dluMHJttBIVOBaLfcA7r40Fp4ttkKghE7kv6t2MTsle', '2007-09-13', 1, 1, 0, 'activo', 2, '2025-10-22', 'perfiles/perfil_1_1762437313.jpg');

--
-- Estructura de tabla para la tabla `pago`
--
ALTER TABLE `miembro`
  ADD PRIMARY KEY (`id_miembro`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_miembro_unidad` (`id_unidad`);

CREATE TABLE `pago` (
  `id_pago` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` varchar(255) DEFAULT NULL,
  `estado_pa` enum('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
  `fecha_p` date DEFAULT NULL,
  `comprobante` varchar(255) DEFAULT NULL,
  `metodo_pago` enum('efectivo','transferencia','tarjeta') DEFAULT 'efectivo',
  `id_miembro` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pago`
--

INSERT INTO `pago` (`id_pago`, `monto`, `concepto`, `estado_pa`, `fecha_p`, `comprobante`, `metodo_pago`, `id_miembro`) VALUES
(1, 453.00, 'Couta abril', 'aprobado', '2025-09-10', 'comprobantes/1757543214_IMG_20250513_214946.jpg', 'efectivo', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `postulacion`
--

CREATE TABLE `postulacion` (
  `id_postulacion` int(11) NOT NULL,
  `fecha_po` date DEFAULT NULL,
  `estado_po` enum('pendiente','aceptada','rechazada') DEFAULT 'pendiente',
  `comentarios_admin` text DEFAULT NULL,
  `id_miembro` int(11) DEFAULT NULL,
  `cantidad_menores` int(11) DEFAULT NULL,
  `trabajo` varchar(255) DEFAULT NULL,
  `tipo_contrato` varchar(50) DEFAULT NULL,
  `ingresos_nominales` decimal(10,2) DEFAULT NULL,
  `ingresos_familiares` decimal(10,2) DEFAULT NULL,
  `observacion_salud` text DEFAULT NULL,
  `constitucion_familiar` text DEFAULT NULL,
  `vivienda_actual` varchar(255) DEFAULT NULL,
  `gasto_vivienda` decimal(10,2) DEFAULT NULL,
  `nivel_educativo` varchar(100) DEFAULT NULL,
  `hijos_estudiando` int(11) DEFAULT NULL,
  `patrimonio` text DEFAULT NULL,
  `disponibilidad_ayuda` text DEFAULT NULL,
  `motivacion` text DEFAULT NULL,
  `presentado_por` varchar(255) DEFAULT NULL,
  `referencia_contacto` varchar(255) DEFAULT NULL,
  `fecha_postulacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `postulacion`
--

INSERT INTO `postulacion` (`id_postulacion`, `fecha_po`, `estado_po`, `comentarios_admin`, `id_miembro`, `cantidad_menores`, `trabajo`, `tipo_contrato`, `ingresos_nominales`, `ingresos_familiares`, `observacion_salud`, `constitucion_familiar`, `vivienda_actual`, `gasto_vivienda`, `nivel_educativo`, `hijos_estudiando`, `patrimonio`, `disponibilidad_ayuda`, `motivacion`, `presentado_por`, `referencia_contacto`, `fecha_postulacion`) VALUES
(1, '2025-01-01', 'aceptada', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-11 02:36:39'),
(2, '2025-10-22', 'aceptada', NULL, 2, 2, 'Empleado', 'Indefinido', 50000.00, 80000.00, 'Ninguna', 'Nuclear', 'Alquiler', 15000.00, 'Universitario', 2, 'Ahorros', 'Sí', 'Me gustaría mejorar mi calidad de vida y la de mi familia.', 'Ninguno', 'Ninguno', '2025-10-15 04:20:10');
-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidad_habitacional`
--

CREATE TABLE `unidad_habitacional` (
  `id_unidad` int(11) NOT NULL,
  `metros_cuadrados` decimal(10,2) NOT NULL,
  `estado_un` enum('ocupada','disponible','mantenimiento') DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `unidad_habitacional`
--

INSERT INTO `unidad_habitacional` (`id_unidad`, `metros_cuadrados`, `estado_un`) VALUES
(1, 45.00, 'ocupada'),
(2, 45.00, 'ocupada'),
(3, 45.00, 'disponible'),
(4, 45.00, 'disponible'),
(5, 45.00, 'disponible'),
(6, 45.00, 'disponible'),
(7, 45.00, 'disponible'),
(8, 45.00, 'disponible'),
(9, 45.00, 'disponible'),
(10, 45.00, 'disponible'),
(11, 45.00, 'disponible'),
(12, 45.00, 'disponible'),
(13, 45.00, 'disponible'),
(14, 45.00, 'disponible'),
(15, 45.00, 'disponible'),
(16, 45.00, 'disponible'),
(17, 45.00, 'disponible'),
(18, 45.00, 'disponible'),
(19, 45.00, 'disponible'),
(20, 45.00, 'disponible'),
(21, 45.00, 'disponible'),
(22, 45.00, 'disponible'),
(23, 45.00, 'disponible'),
(24, 45.00, 'disponible'),
(25, 45.00, 'disponible'),
(26, 45.00, 'disponible'),
(27, 45.00, 'disponible'),
(28, 45.00, 'disponible'),
(29, 45.00, 'disponible'),
(30, 45.00, 'disponible'),
(31, 45.00, 'disponible'),
(32, 45.00, 'disponible'),
(33, 45.00, 'disponible'),
(34, 45.00, 'disponible'),
(35, 45.00, 'disponible'),
(36, 45.00, 'disponible'),
(37, 45.00, 'disponible'),
(38, 45.00, 'disponible'),
(39, 45.00, 'disponible'),
(40, 45.00, 'disponible'),
(41, 45.00, 'disponible'),
(42, 45.00, 'disponible'),
(43, 45.00, 'disponible'),
(44, 45.00, 'disponible'),
(45, 45.00, 'disponible'),
(46, 45.00, 'disponible'),
(47, 45.00, 'disponible'),
(48, 45.00, 'disponible'),
(49, 45.00, 'disponible'),
(50, 45.00, 'disponible'),
(51, 45.00, 'disponible'),
(52, 45.00, 'disponible'),
(53, 45.00, 'disponible'),
(54, 45.00, 'disponible'),
(55, 45.00, 'disponible'),
(56, 45.00, 'disponible'),
(57, 45.00, 'disponible'),
(58, 45.00, 'disponible'),
(59, 45.00, 'disponible'),
(60, 45.00, 'disponible'),
(61, 45.00, 'disponible'),
(62, 45.00, 'disponible'),
(63, 45.00, 'disponible'),
(64, 45.00, 'disponible'),
(65, 45.00, 'disponible'),
(66, 45.00, 'disponible'),
(67, 45.00, 'disponible'),
(68, 45.00, 'disponible'),
(69, 45.00, 'disponible'),
(70, 45.00, 'disponible'),
(71, 45.00, 'disponible'),
(72, 45.00, 'disponible'),
(73, 45.00, 'disponible'),
(74, 45.00, 'disponible'),
(75, 45.00, 'disponible'),
(76, 45.00, 'disponible'),
(77, 45.00, 'disponible'),
(78, 45.00, 'disponible'),
(79, 45.00, 'disponible'),
(80, 45.00, 'disponible'),
(81, 45.00, 'disponible'),
(82, 45.00, 'disponible'),
(83, 45.00, 'disponible'),
(84, 45.00, 'disponible'),
(85, 45.00, 'disponible'),
(86, 45.00, 'disponible'),
(87, 45.00, 'disponible'),
(88, 45.00, 'disponible'),
(89, 45.00, 'disponible'),
(90, 45.00, 'disponible'),
(91, 45.00, 'disponible'),
(92, 45.00, 'disponible'),
(93, 45.00, 'disponible'),
(94, 45.00, 'disponible'),
(95, 45.00, 'disponible'),
(96, 45.00, 'disponible'),
(97, 45.00, 'disponible'),
(98, 45.00, 'disponible'),
(99, 45.00, 'disponible'),
(100, 45.00, 'disponible');

CREATE TABLE `calendario` (
    `id_evento` INT(11) NOT NULL AUTO_INCREMENT,
    `titulo` VARCHAR(255) NOT NULL,
    `descripcion` TEXT NULL DEFAULT NULL,
    `fecha_evento` DATE NOT NULL,
    `creado_por` INT(11) NULL DEFAULT NULL,
    PRIMARY KEY (`id_evento`)
);

CREATE TABLE `foro` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `usuario_id` INT(11) NOT NULL,
    `titulo` VARCHAR(255) NOT NULL,
    `mensaje` TEXT NOT NULL,
    `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX (`usuario_id`),
    CONSTRAINT `fk_foro_miembro` 
        FOREIGN KEY (`usuario_id`) 
        REFERENCES `miembro`(`id_miembro`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_general_ci;


CREATE TABLE `foro_respuestas` (
    `id` INT(11) AUTO_INCREMENT,
    `foro_id` INT(11) NOT NULL,
    `usuario_id` INT(11) NOT NULL,
    `respuesta` TEXT NOT NULL,
    `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_respuesta_foro`
    FOREIGN KEY (`foro_id`) REFERENCES `foro`(`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
     CONSTRAINT `fk_respuesta_usuario`   
    FOREIGN KEY (`usuario_id`) REFERENCES `miembro`(`id_miembro`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Indices de la tabla `horas`
--
ALTER TABLE `horas`
  ADD PRIMARY KEY (`id_horas`),
  ADD KEY `fk_horas_miembro` (`id_miembro`);

--
-- Indices de la tabla `miembro`
--


--
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `fk_pago_miembro` (`id_miembro`);

--
-- Indices de la tabla `postulacion`
--
ALTER TABLE `postulacion`
  ADD PRIMARY KEY (`id_postulacion`),
  ADD KEY `fk_postulacion_miembro` (`id_miembro`);

--
-- Indices de la tabla `unidad_habitacional`
--
ALTER TABLE `unidad_habitacional`
  ADD PRIMARY KEY (`id_unidad`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `horas`
--
ALTER TABLE `horas`
  MODIFY `id_horas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `miembro`
--
ALTER TABLE `miembro`
  MODIFY `id_miembro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `postulacion`
--
ALTER TABLE `postulacion`
  MODIFY `id_postulacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `unidad_habitacional`
--
ALTER TABLE `unidad_habitacional`
  MODIFY `id_unidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `horas`
--
ALTER TABLE `horas`
  ADD CONSTRAINT `fk_horas_miembro` FOREIGN KEY (`id_miembro`) REFERENCES `miembro` (`id_miembro`);

--
-- Filtros para la tabla `miembro`
--
ALTER TABLE `miembro`
  ADD CONSTRAINT `fk_miembro_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidad_habitacional` (`id_unidad`);

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `fk_pago_miembro` FOREIGN KEY (`id_miembro`) REFERENCES `miembro` (`id_miembro`);

--
-- Filtros para la tabla `postulacion`
--
ALTER TABLE `postulacion`
  ADD CONSTRAINT `fk_postulacion_miembro` FOREIGN KEY (`id_miembro`) REFERENCES `miembro` (`id_miembro`);
COMMIT;

SELECT
    m.nombre AS nombre_completo,
    m.email,
    m.id_unidad,
    m.fecha_ingreso AS fecha_socio,
    m.foto_perfil AS foto_perfil_url,
    uh.metros_cuadrados,
    uh.estado_un
FROM
    miembro m
LEFT JOIN
    unidad_habitacional uh ON m.id_unidad = uh.id_unidad
WHERE
    m.id_miembro = 1; -- Reemplaza '1' con la variable del ID del usuario logueado
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
