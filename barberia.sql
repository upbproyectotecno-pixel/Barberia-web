-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 30-08-2025 a las 19:52:42
-- Versión del servidor: 8.0.30
-- Versión de PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `barberia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` int NOT NULL,
  `user_citas` int NOT NULL,
  `barbero_id` int DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id`, `user_citas`, `barbero_id`, `fecha`, `hora`) VALUES
(1, 1, NULL, '2025-08-15', '10:22:00'),
(2, 1, NULL, '2025-08-08', '10:32:00'),
(3, 1, NULL, '2025-08-26', '10:33:00'),
(4, 1, NULL, '2025-08-06', '10:39:00'),
(5, 1, NULL, '2025-08-12', '22:39:00'),
(6, 1, NULL, '2025-08-08', '01:36:00'),
(7, 3, NULL, '2025-08-06', '11:59:00'),
(11, 8, 7, '2025-08-15', '16:00:00'),
(12, 9, 7, '2025-08-16', '16:00:00'),
(13, 10, 7, '2025-08-16', '17:00:00'),
(18, 1, 5, '2025-08-03', '14:08:00'),
(19, 1, 5, '3323-03-23', '13:02:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cita_servicio`
--

CREATE TABLE `cita_servicio` (
  `id` int NOT NULL,
  `cita_id` int NOT NULL,
  `servicio_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `cita_servicio`
--

INSERT INTO `cita_servicio` (`id`, `cita_id`, `servicio_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 2, 5),
(4, 3, 3),
(5, 4, 1),
(6, 5, 2),
(7, 5, 4),
(8, 6, 2),
(9, 6, 3),
(10, 7, 1),
(11, 7, 3),
(17, 11, 4),
(18, 12, 2),
(19, 13, 4),
(28, 18, 1),
(29, 19, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id`, `nombre`, `precio`) VALUES
(1, 'Corte de cabello', 17000.00),
(2, 'Limpieza facial', 22000.00),
(3, 'Cejas', 5000.00),
(4, 'Barba', 10000.00),
(5, 'Combo Completo', 45900.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `user_id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('usuario','admin','barbero') DEFAULT 'usuario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`user_id`, `nombre`, `email`, `password`, `rol`) VALUES
(1, 'O', 'p@gmail.com', 'p', 'usuario'),
(2, 'Admin', 'administradorbarber@gmail.com', '123', 'admin'),
(3, 'hincapie', 'lemussaldarriaga@gmail.com', '$2y$10$kioJoA7lceq.qFoGz7sJgui8cROEnpEwphudXbgy8LA7Y0qm2S01a', 'usuario'),
(4, 'lulo', 'alejobedoyam2008@gmail.com', '$2y$10$6C1A/iFaWNMVvd8iaH.y0OBYgNqeUczlGCRXxfLuCy21SR4K1CSN2', 'usuario'),
(5, 'Barbero Juan', 'juan@barberia.com', '123456', 'barbero'),
(6, 'Barbero Pedro', 'pedro@barberia.com', '123456', 'barbero'),
(7, 'Barbero Luis', 'luis@barberia.com', '123456', 'barbero'),
(8, 'r', 'isabela.combatt@upb.edu.co', '$2y$10$.KuOEOOBAjd4/akDMBWOO.1ueKIRU27TBm0kaqf3Xact1Uv/kd7G2', 'usuario'),
(9, 'rrr', 'mariap.correav@upb.edu.co', '$2y$10$qmpsbK6rDY9uPKBZjUC1hOQs1j6NHStOM7vrJFvoWfACdOFpqOoeO', 'usuario'),
(10, 'ee', 'pipegamboarestrepo@gmail.com', '$2y$10$0RvzJMRhw1l.JXurF5amsua0BRMt6UqATdau1LTqD/KvB47REmFPC', 'usuario');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_citas` (`user_citas`),
  ADD KEY `fk_barbero` (`barbero_id`);

--
-- Indices de la tabla `cita_servicio`
--
ALTER TABLE `cita_servicio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cita_id` (`cita_id`),
  ADD KEY `servicio_id` (`servicio_id`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `cita_servicio`
--
ALTER TABLE `cita_servicio`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`user_citas`) REFERENCES `usuario` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_barbero` FOREIGN KEY (`barbero_id`) REFERENCES `usuario` (`user_id`);

--
-- Filtros para la tabla `cita_servicio`
--
ALTER TABLE `cita_servicio`
  ADD CONSTRAINT `cita_servicio_ibfk_1` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cita_servicio_ibfk_2` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
