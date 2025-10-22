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
(1, 0, 101, NULL, 1, 0, '|asistio=1;horas=11;actividad=|asistio=1;horas=11;actividad=|asistio=1;horas=11;actividad=|asistio=1;horas=33;actividad=Aprobadas por admin|asistio=1;horas=3;actividad=|asistio=1;horas=3;actividad=Aprobadas por admin|asistio=1;horas=4;actividad=|asistio=0;justificativo=xq ta wacho justificativos/just_1_1757871999.pdf|asistio=0;justificativo=sqwad justificativos/just_1_1757873110.png|asistio=1;horas=4;actividad=Aprobadas por admin|asistio=0;justificativo=dwada justificativos/just_1_1757874992.jpeg|asistio=0;justificativo=wdw justificativos/just_1_1757875621.jpeg|asistio=0;justificativo=no pude|asistio=0;justificativo=no pude justificativos/just_1_1757876780.jpg'),
(2, 10, 69, NULL, 3, 0, '|asistio=1;horas=4;actividad=caca|asistio=1;horas=21;actividad=Aprobadas por admin|asistio=0;justificativo=pq no pude|asistio=1;horas=3;actividad=swd|asistio=1;horas=11;actividad=|asistio=1;horas=14;actividad=Aprobadas por admin'),
(3, 0, 0, NULL, 2, 0, ''),
(4, 10, 0, NULL, 7, 0, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `miembro`
--

CREATE TABLE `miembro` (
  `id_miembro` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `aprobado` tinyint(1) DEFAULT 0,
  `es_miembro` tinyint(1) DEFAULT 0,
  `admin` tinyint(1) DEFAULT 0,
  `estado` varchar(100) DEFAULT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `miembro`
--

INSERT INTO `miembro` (`id_miembro`, `nombre`, `email`, `password`, `aprobado`, `es_miembro`, `admin`, `estado`, `id_unidad`, `fecha_ingreso`) VALUES
(1, 'Tomas', 'tomas@gmail.com', '$2y$10$EB5bCH08G3dluMHJttBIVOBaLfcA7r40Fp4ttkKghE7kv6t2MTsle', 1, 1, 1, 'pendiente', 66, NULL),
(2, 'Admin General', 'admin@coop.com', '$2y$10$KjS5Epbjs5SduXUqTflNOek5rDn6Xnv5OGAzpWw/szXmtgLOgRzXy', 1, 0, 0, 'aprobado', NULL, NULL),
(3, 'CuloCon', 'culo@gmail.com', '$2y$10$l.osSp1Yx8OVshXJJNToZOf6NaKDD1bh/pWiXwDGE3OSoaqpKuJMC', 1, 1, 0, NULL, NULL, NULL),
(4, 'Alberto', 'alberto@gmail.com', '$2y$10$SAJ/SBaKZU7G4ePeRny7ae9mrkbs/OggUtTofZEFiLLlgZKpAk2lW', 1, 0, 0, NULL, NULL, NULL),
(5, 'caca', 'caca@gmail.com', '$2y$10$oLbDloLqX1YhJ1zRM1IJBuhK2q86EO.vrFxD4ymaa5ZuLIt7iK1Iu', 1, 0, 0, NULL, NULL, NULL),
(6, 'Santino', 'santi@gmail.com', '$2y$10$1M/CMVSKhsyWn4wVGmj8meVR/JTQXZHEV826yVDNJm9JUMXzenAiW', 1, 0, 0, NULL, NULL, NULL),
(7, 'Admin', 'admin@cooperativa.com', '$2y$10$GYi1Y560hitzs8MVotsWgetP9EOjsXD6Fe/C5vdwBjLWHhjILXIgC', 1, 1, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

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
(1, 0.18, 'Cuota Septiembre', 'pendiente', '2025-09-11', 'uploads/version2_fix.zip', 'efectivo', 1),
(2, 453.00, 'Couta abril', 'aprobado', '2025-09-10', 'comprobantes/1757543214_IMG_20250513_214946.jpg', 'efectivo', 3);

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
(1, '2025-09-11', 'pendiente', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-11 02:36:39'),
(2, NULL, 'pendiente', NULL, 5, 4, 'ingeniero', 'permanente', 0.40, 3424.00, 'no', 'caca', 'culo', 0.37, 'utu', 1, 'casa', 'no', 'no', 'nadie', 'nadie', '2025-09-11 02:48:57'),
(3, NULL, 'pendiente', NULL, 4, 12, 'ingeniero', 'permanente', 1212.00, 21.00, '231', '23123', '213', 231212.97, 'utu', 12, '2112', '12313', '3213', '13232', '13213', '2025-09-11 03:00:12'),
(4, NULL, 'pendiente', NULL, 6, 11, 'dwad', 'informal', 21.00, 2313.00, '12312', '3312312', '213321', 31223.00, '12312', 12312, '312', '231312', '12312', '132312', '3213', '2025-09-11 03:02:07');

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
(2, 45.00, 'disponible'),
(3, 45.00, 'ocupada'),
(4, 45.00, 'disponible'),
(5, 45.00, 'disponible'),
(6, 45.00, 'ocupada'),
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
(26, 45.00, 'ocupada'),
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
(66, 45.00, 'ocupada'),
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
(77, 45.00, 'ocupada'),
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
(100, 45.00, 'ocupada');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `horas`
--
ALTER TABLE `horas`
  ADD PRIMARY KEY (`id_horas`),
  ADD KEY `fk_horas_miembro` (`id_miembro`);

--
-- Indices de la tabla `miembro`
--
ALTER TABLE `miembro`
  ADD PRIMARY KEY (`id_miembro`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_miembro_unidad` (`id_unidad`);

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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
