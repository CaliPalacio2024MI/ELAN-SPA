-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2025 at 02:09 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eswe_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `anfitriones`
--

CREATE TABLE `anfitriones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `spa_id` bigint(20) UNSIGNED NOT NULL,
  `RFC` varchar(255) NOT NULL,
  `apellido_paterno` varchar(255) NOT NULL,
  `apellido_materno` varchar(255) DEFAULT NULL,
  `nombre_usuario` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('master','administrador','recepcionista','anfitrion') NOT NULL DEFAULT 'anfitrion',
  `accesos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accesos`)),
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `anfitriones`
--

INSERT INTO `anfitriones` (`id`, `spa_id`, `RFC`, `apellido_paterno`, `apellido_materno`, `nombre_usuario`, `password`, `rol`, `accesos`, `activo`, `created_at`, `updated_at`) VALUES
(1, 1, 'MASTER001ABC', 'Control', 'Central', 'Master', '$2y$12$K2wmbeLn/q4rtmvjjDCQmuQQFRx2hBOdvX/U6F3xwMc9z5rSvnOle', 'master', '[1,2,3]', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(2, 1, 'SANC950827MOO', 'Gómez', 'Perez', 'Carlos', '$2y$12$K2wmbeLn/q4rtmvjjDCQmuQQFRx2hBOdvX/U6F3xwMc9z5rSvnOle', 'anfitrion', '[2,3]', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(3, 1, 'SANC950827KKP', 'Martínez', 'Perez', 'Ana', '$2y$12$K2wmbeLn/q4rtmvjjDCQmuQQFRx2hBOdvX/U6F3xwMc9z5rSvnOle', 'administrador', '[2]', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(4, 2, 'SANC950827MND', 'López', 'Marin', 'Raul', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(5, 2, 'SANC950827MNF', 'Ramírez', 'Medina', 'Maria', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(6, 3, 'SANC950827MNG', 'Fernández', 'Flores', 'Pedro', '$2y$12$K2wmbeLn/q4rtmvjjDCQmuQQFRx2hBOdvX/U6F3xwMc9z5rSvnOle', 'anfitrion', '[1]', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(7, 3, 'SANC950827MNH', 'Sánchez', 'Fe', 'Lucio', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(8, 1, 'SANC950827MNI', 'Chinchulin', 'Fernandez', 'Mai', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'administrador', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(9, 1, 'SANC950827MNJ', 'Morales', 'Díaz', 'Sandra', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(10, 1, 'SANC950827MNK', 'Torres', 'Nava', 'Hugo', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(11, 1, 'SANC950827MNZ', 'Paredes', 'García', 'Itzel', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(12, 1, 'SANC950827MNN', 'Vega', 'Ortega', 'Luis', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(13, 1, 'SANC950827MNO', 'Zamora', 'Ruiz', 'Brenda', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(14, 2, 'SANC950827MNP', 'Ríos', 'Vargas', 'Diana', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(15, 2, 'SANC950827MNQ', 'Salas', 'Montes', 'Oscar', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(16, 2, 'SANC950827MNR', 'Mejía', 'Guzmán', 'Andrea', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(17, 2, 'SANC950827MNS', 'Carrillo', 'Suárez', 'Fernando', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(18, 3, 'SANC950827MNT', 'Navarro', 'Salinas', 'Claudia', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(19, 3, 'SANC950827MNU', 'Silva', 'Luna', 'Jonathan', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(20, 3, 'SANC950827MNLV', 'Aguilar', 'Castro', 'Elena', '$2y$12$K/BWo5mgpJfaXZMNGh0igubAzDBeImTXiNw/ZnQA3rSXmcXLlXbdy', 'anfitrion', NULL, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08');

-- --------------------------------------------------------

--
-- Table structure for table `anfitrion_operativo`
--

CREATE TABLE `anfitrion_operativo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `anfitrion_id` bigint(20) UNSIGNED NOT NULL,
  `departamento` enum('spa','gym','valet','salon de belleza','global') NOT NULL DEFAULT 'spa',
  `clases_actividad` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`clases_actividad`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `anfitrion_operativo`
--

INSERT INTO `anfitrion_operativo` (`id`, `anfitrion_id`, `departamento`, `clases_actividad`, `created_at`, `updated_at`) VALUES
(1, 2, 'spa', '[\"Masajes\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(2, 4, 'gym', '[\"Entrenamiento\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(3, 5, 'spa', '[\"Faciales\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(4, 6, 'spa', '[\"Corporales\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(5, 7, 'spa', '[\"Masajes\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(6, 9, 'spa', '[\"Faciales\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(7, 10, 'spa', '[\"Masajes\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(8, 11, 'spa', '[\"Faciales\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(9, 12, 'spa', '[\"Corporales\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(10, 13, 'spa', '[\"Masajes\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(11, 14, 'spa', '[\"Faciales\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(12, 15, 'spa', '[\"Corporales\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(13, 16, 'spa', '[\"Masajes\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(14, 17, 'spa', '[\"Faciales\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(15, 18, 'spa', '[\"Masajes\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(16, 19, 'spa', '[\"Corporales\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(17, 20, 'spa', '[\"Faciales\"]', '2025-10-07 22:22:08', '2025-10-07 22:22:08');

-- --------------------------------------------------------

--
-- Table structure for table `blocked_slots`
--

CREATE TABLE `blocked_slots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `spa_id` bigint(20) UNSIGNED NOT NULL,
  `anfitrion_id` bigint(20) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `duracion` int(11) NOT NULL DEFAULT 10,
  `motivo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blocked_slots`
--

INSERT INTO `blocked_slots` (`id`, `spa_id`, `anfitrion_id`, `fecha`, `hora`, `duracion`, `motivo`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2025-10-07', '08:00:00', 30, NULL, '2025-10-07 23:43:04', '2025-10-07 23:43:04');

-- --------------------------------------------------------

--
-- Table structure for table `boutique_articulos`
--

CREATE TABLE `boutique_articulos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `numero_auxiliar` int(11) NOT NULL,
  `nombre_articulo` varchar(20) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `precio_publico_unidad` decimal(10,2) DEFAULT NULL,
  `fk_id_familia` bigint(20) UNSIGNED NOT NULL,
  `fk_id_hotel` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `boutique_articulos_familias`
--

CREATE TABLE `boutique_articulos_familias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `fk_id_hotel` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `boutique_articulos_familias`
--

INSERT INTO `boutique_articulos_familias` (`id`, `nombre`, `fk_id_hotel`, `created_at`, `updated_at`) VALUES
(1, 'Corporal', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(2, 'Facial', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(3, 'Cabello', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(4, 'Amenidad', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(5, 'Corporal', 2, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(6, 'Facial', 2, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(7, 'Cabello', 2, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(8, 'Amenidad', 2, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(9, 'Corporal', 3, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(10, 'Facial', 3, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(11, 'Cabello', 3, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(12, 'Amenidad', 3, '2025-10-07 22:22:08', '2025-10-07 22:22:08');

-- --------------------------------------------------------

--
-- Table structure for table `boutique_compras`
--

CREATE TABLE `boutique_compras` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fk_id_articulo` bigint(20) UNSIGNED NOT NULL,
  `tipo_compra` enum('normal','directa') NOT NULL DEFAULT 'normal',
  `folio_orden_compra` varchar(50) DEFAULT NULL,
  `folio_factura` varchar(50) NOT NULL,
  `costo_proveedor_unidad` decimal(10,2) NOT NULL,
  `cantidad_recibida` int(11) NOT NULL,
  `fecha_caducidad` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `boutique_compras_eliminadas`
--

CREATE TABLE `boutique_compras_eliminadas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fk_id_compra` bigint(20) UNSIGNED NOT NULL,
  `motivo` varchar(255) NOT NULL DEFAULT 'Eliminación manual',
  `cantidad_eliminada` int(11) NOT NULL,
  `usuario_elimino` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `boutique_config_ventas_clasificacion`
--

CREATE TABLE `boutique_config_ventas_clasificacion` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(15) NOT NULL,
  `minimo_ventas` int(11) NOT NULL,
  `fk_id_hotel` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `boutique_config_ventas_clasificacion`
--

INSERT INTO `boutique_config_ventas_clasificacion` (`id`, `nombre`, `minimo_ventas`, `fk_id_hotel`, `created_at`, `updated_at`) VALUES
(1, 'Rápido', 30, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(2, 'Lento', 10, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(3, 'Obsoleto', 0, 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(4, 'Rápido', 30, 2, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(5, 'Lento', 10, 2, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(6, 'Obsoleto', 0, 2, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(7, 'Rápido', 30, 3, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(8, 'Lento', 10, 3, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(9, 'Obsoleto', 0, 3, '2025-10-07 22:22:08', '2025-10-07 22:22:08');

-- --------------------------------------------------------

--
-- Table structure for table `boutique_formas_pago`
--

CREATE TABLE `boutique_formas_pago` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `boutique_formas_pago`
--

INSERT INTO `boutique_formas_pago` (`id`, `nombre`, `created_at`, `updated_at`) VALUES
(1, 'Cargo a Habitación', NULL, NULL),
(2, 'Tarjeta', NULL, NULL),
(3, 'Misceláneo', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `boutique_inventario`
--

CREATE TABLE `boutique_inventario` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fk_id_compra` bigint(20) UNSIGNED NOT NULL,
  `cantidad_actual` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `boutique_ventas`
--

CREATE TABLE `boutique_ventas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fk_id_hotel` bigint(20) UNSIGNED NOT NULL,
  `folio_venta` varchar(20) DEFAULT NULL,
  `fk_id_forma_pago` bigint(20) UNSIGNED NOT NULL,
  `referencia_pago` varchar(100) DEFAULT NULL,
  `fecha_venta` date NOT NULL,
  `hora_venta` time NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `boutique_ventas_detalles`
--

CREATE TABLE `boutique_ventas_detalles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fk_id_folio` bigint(20) UNSIGNED NOT NULL,
  `fk_id_compra` bigint(20) UNSIGNED NOT NULL,
  `fk_id_anfitrion` bigint(20) UNSIGNED NOT NULL,
  `cantidad` int(11) NOT NULL,
  `descuento` decimal(5,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cabinas`
--

CREATE TABLE `cabinas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `spa_id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `clase` varchar(255) NOT NULL DEFAULT 'individual',
  `clases_actividad` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`clases_actividad`)),
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cabinas`
--

INSERT INTO `cabinas` (`id`, `spa_id`, `nombre`, `clase`, `clases_actividad`, `activo`, `created_at`, `updated_at`) VALUES
(1, 1, 'Cabina Relax', 'individual', '[\"Masajes\"]', 1, '2025-10-07 22:22:07', '2025-10-07 22:22:07'),
(2, 1, 'Cabina Deluxe', 'doble', '[\"Masajes\",\"Corporales\"]', 1, '2025-10-07 22:22:07', '2025-10-07 22:22:07'),
(3, 1, 'Cabina VIP', 'vip', '[\"Masajes\",\"Corporales\"]', 1, '2025-10-07 22:22:07', '2025-10-07 22:22:07'),
(4, 1, 'Cabina gris', 'Gris', '[\"Masajes\"]', 1, '2025-10-07 22:22:07', '2025-10-07 22:22:07'),
(5, 2, 'Cabina Zen', 'individual', '[\"Masajes\",\"Corporales\"]', 1, '2025-10-07 22:22:07', '2025-10-07 22:22:07'),
(6, 2, 'Cabina Serenity', 'doble', '[\"Masajes\"]', 1, '2025-10-07 22:22:07', '2025-10-07 22:22:07'),
(7, 2, 'Cabina VIP Platinum', 'vip', '[\"Masajes\",\"Corporales\"]', 1, '2025-10-07 22:22:07', '2025-10-07 22:22:07'),
(8, 3, 'Cabina Harmony', 'individual', '[\"Masajes\"]', 1, '2025-10-07 22:22:07', '2025-10-07 22:22:07'),
(9, 3, 'Cabina Elegance', 'doble', '[\"Masajes\"]', 1, '2025-10-07 22:22:07', '2025-10-07 22:22:07'),
(10, 3, 'Cabina VIP Diamond', 'vip', '[\"Masajes\",\"Corporales\"]', 1, '2025-10-07 22:22:07', '2025-10-07 22:22:07');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('clasificaciones_boutique_hotel_1', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:3:{i:0;O:44:\"App\\Models\\BoutiqueConfigVentasClasificacion\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:36:\"boutique_config_ventas_clasificacion\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:1;s:6:\"nombre\";s:7:\"Rápido\";s:13:\"minimo_ventas\";i:30;s:11:\"fk_id_hotel\";i:1;s:10:\"created_at\";s:19:\"2025-10-07 16:22:08\";s:10:\"updated_at\";s:19:\"2025-10-07 16:22:08\";}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:1;s:6:\"nombre\";s:7:\"Rápido\";s:13:\"minimo_ventas\";i:30;s:11:\"fk_id_hotel\";i:1;s:10:\"created_at\";s:19:\"2025-10-07 16:22:08\";s:10:\"updated_at\";s:19:\"2025-10-07 16:22:08\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:3:{i:0;s:6:\"nombre\";i:1;s:13:\"minimo_ventas\";i:2;s:11:\"fk_id_hotel\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:44:\"App\\Models\\BoutiqueConfigVentasClasificacion\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:36:\"boutique_config_ventas_clasificacion\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:2;s:6:\"nombre\";s:5:\"Lento\";s:13:\"minimo_ventas\";i:10;s:11:\"fk_id_hotel\";i:1;s:10:\"created_at\";s:19:\"2025-10-07 16:22:08\";s:10:\"updated_at\";s:19:\"2025-10-07 16:22:08\";}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:2;s:6:\"nombre\";s:5:\"Lento\";s:13:\"minimo_ventas\";i:10;s:11:\"fk_id_hotel\";i:1;s:10:\"created_at\";s:19:\"2025-10-07 16:22:08\";s:10:\"updated_at\";s:19:\"2025-10-07 16:22:08\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:3:{i:0;s:6:\"nombre\";i:1;s:13:\"minimo_ventas\";i:2;s:11:\"fk_id_hotel\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:44:\"App\\Models\\BoutiqueConfigVentasClasificacion\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:36:\"boutique_config_ventas_clasificacion\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:3;s:6:\"nombre\";s:8:\"Obsoleto\";s:13:\"minimo_ventas\";i:0;s:11:\"fk_id_hotel\";i:1;s:10:\"created_at\";s:19:\"2025-10-07 16:22:08\";s:10:\"updated_at\";s:19:\"2025-10-07 16:22:08\";}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:3;s:6:\"nombre\";s:8:\"Obsoleto\";s:13:\"minimo_ventas\";i:0;s:11:\"fk_id_hotel\";i:1;s:10:\"created_at\";s:19:\"2025-10-07 16:22:08\";s:10:\"updated_at\";s:19:\"2025-10-07 16:22:08\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:3:{i:0;s:6:\"nombre\";i:1;s:13:\"minimo_ventas\";i:2;s:11:\"fk_id_hotel\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1759884514),
('familias_boutique_hotel_1', 'O:29:\"Illuminate\\Support\\Collection\":2:{s:8:\"\0*\0items\";a:4:{i:0;O:8:\"stdClass\":5:{s:2:\"id\";i:4;s:6:\"nombre\";s:8:\"Amenidad\";s:11:\"fk_id_hotel\";i:1;s:10:\"created_at\";s:19:\"2025-10-07 16:22:08\";s:10:\"updated_at\";s:19:\"2025-10-07 16:22:08\";}i:1;O:8:\"stdClass\":5:{s:2:\"id\";i:3;s:6:\"nombre\";s:7:\"Cabello\";s:11:\"fk_id_hotel\";i:1;s:10:\"created_at\";s:19:\"2025-10-07 16:22:08\";s:10:\"updated_at\";s:19:\"2025-10-07 16:22:08\";}i:2;O:8:\"stdClass\":5:{s:2:\"id\";i:1;s:6:\"nombre\";s:8:\"Corporal\";s:11:\"fk_id_hotel\";i:1;s:10:\"created_at\";s:19:\"2025-10-07 16:22:08\";s:10:\"updated_at\";s:19:\"2025-10-07 16:22:08\";}i:3;O:8:\"stdClass\":5:{s:2:\"id\";i:2;s:6:\"nombre\";s:6:\"Facial\";s:11:\"fk_id_hotel\";i:1;s:10:\"created_at\";s:19:\"2025-10-07 16:22:08\";s:10:\"updated_at\";s:19:\"2025-10-07 16:22:08\";}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1759884514);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `apellido_paterno` varchar(255) NOT NULL,
  `apellido_materno` varchar(255) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) NOT NULL,
  `tipo_visita` enum('palacio mundo imperial','princess mundo imperial','pierre mundo imperial','condominio','locales') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `nombre`, `apellido_paterno`, `apellido_materno`, `correo`, `telefono`, `tipo_visita`, `created_at`, `updated_at`) VALUES
(1, 'Luis', 'Hernández', 'Abarca', 'luis@example.com', '5551234567', 'palacio mundo imperial', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(2, 'Elena', 'Ramírez', 'Filemon', 'elena@example.com', '5552345678', 'palacio mundo imperial', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(3, 'Carlos', 'Gómez', 'Smith', 'carlos@example.com', '5553456789', 'princess mundo imperial', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(4, 'Sofía', 'Díaz', 'Perez', 'sofia@example.com', '5554567890', 'princess mundo imperial', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(5, 'Javier', 'Pérez', 'Cruz', 'javier@example.com', '5555678901', 'pierre mundo imperial', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(6, 'Marta', 'Lopez', 'Temertizo', 'marta@example.com', '5556789012', 'pierre mundo imperial', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(7, 'Juan', 'Santana', 'Red', 'juan@example.com', '7444444444', 'condominio', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(8, 'José', 'Torre', 'Bello', 'jose@example.com', '7444444455', 'locales', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(9, 'Daniela', 'Morán', 'Salmeron', 'daniela@example.com', '9330000000', 'palacio mundo imperial', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(10, 'Daniela', 'Luz', 'mg', 'daniela2@example.com', '7443434343', 'palacio mundo imperial', '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(11, 'Ramón', 'Valdez', 'Monte', 'ramon@example.com', '5550000000', 'locales', '2025-10-07 22:22:08', '2025-10-07 22:22:08');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_forms`
--

CREATE TABLE `evaluation_forms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `reservation_id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `preguntas_respuestas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`preguntas_respuestas`)),
  `observaciones` text DEFAULT NULL,
  `firma_paciente_url` varchar(255) DEFAULT NULL,
  `firma_tutor_url` varchar(255) DEFAULT NULL,
  `firma_doctor_url` varchar(255) DEFAULT NULL,
  `firma_testigo1_url` varchar(255) DEFAULT NULL,
  `firma_testigo2_url` varchar(255) DEFAULT NULL,
  `firma_padre_url` varchar(255) DEFAULT NULL,
  `firma_terapeuta_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `experiences`
--

CREATE TABLE `experiences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `spa_id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `clase` varchar(255) NOT NULL,
  `duracion` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `color` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `experiences`
--

INSERT INTO `experiences` (`id`, `spa_id`, `nombre`, `clase`, `duracion`, `descripcion`, `precio`, `color`, `activo`, `created_at`, `updated_at`) VALUES
(1, 1, 'Masaje Relajante', 'Masajes', 60, 'Un masaje de cuerpo completo con aceites esenciales para aliviar el estrés.', 1200.00, '#FFC0CB', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(2, 1, 'Facial Hidratante', 'Faciales', 45, 'Tratamiento facial profundo para rejuvenecer la piel.', 900.00, '#ADD8E6', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(3, 2, 'Terapia de Piedras Calientes', 'Masajes', 75, 'Masaje con piedras volcánicas para liberar la tensión muscular.', 1500.00, '#FFA07A', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(4, 2, 'Exfoliación Corporal', 'Corporales', 50, 'Tratamiento exfoliante para eliminar células muertas de la piel.', 1100.00, '#FFE4B5', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(5, 3, 'Envoltura de Chocolate', 'Corporales', 60, 'Terapia relajante con envoltura de cacao para nutrir la piel.', 1300.00, '#D2691E', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(6, 3, 'Reflexología Podal', 'Masajes', 40, 'Masaje terapéutico en los pies para mejorar la circulación.', 800.00, '#90EE90', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(7, 1, 'Masaje calmante', 'Corporales', 120, 'ni idea', 120.00, '#FFB6C1', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(8, 1, 'Masaje de espalda', 'Masajes', 83, 'es un masaje en la espalda', 2000.00, '#FA8072', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(9, 1, 'Masaje de codos', 'Masajes', 30, 'masaje en codos', 3000.00, '#F0E68C', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(10, 1, 'Masaje reparador', 'Masajes', 90, 'es un masaje', 3000.00, '#E6E6FA', 1, '2025-10-07 22:22:08', '2025-10-07 22:22:08');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gimnasio_config_qr_code`
--

CREATE TABLE `gimnasio_config_qr_code` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fk_id_hotel` bigint(20) UNSIGNED NOT NULL,
  `tiempo_renovacion_qr` int(11) NOT NULL DEFAULT 60,
  `tiempo_validez_qr` int(11) NOT NULL DEFAULT 120,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gimnasio_config_qr_code`
--

INSERT INTO `gimnasio_config_qr_code` (`id`, `fk_id_hotel`, `tiempo_renovacion_qr`, `tiempo_validez_qr`, `created_at`, `updated_at`) VALUES
(1, 1, 60, 60, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(2, 2, 60, 60, '2025-10-07 22:22:08', '2025-10-07 22:22:08'),
(3, 3, 60, 60, '2025-10-07 22:22:08', '2025-10-07 22:22:08');

-- --------------------------------------------------------

--
-- Table structure for table `gimnasio_qrcodes`
--

CREATE TABLE `gimnasio_qrcodes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `fk_id_hotel` bigint(20) UNSIGNED NOT NULL,
  `contexto` enum('interno','externo') NOT NULL DEFAULT 'externo',
  `fecha_expiracion` datetime NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gimnasio_registros_adultos`
--

CREATE TABLE `gimnasio_registros_adultos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fk_id_hotel` bigint(20) UNSIGNED NOT NULL,
  `origen_registro` enum('interno','externo') NOT NULL DEFAULT 'externo',
  `nombre_huesped` varchar(100) NOT NULL,
  `firma_huesped` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gimnasio_registros_menores`
--

CREATE TABLE `gimnasio_registros_menores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fk_id_hotel` bigint(20) UNSIGNED NOT NULL,
  `origen_registro` enum('interno','externo') NOT NULL DEFAULT 'externo',
  `nombre_menor` varchar(100) NOT NULL,
  `edad` int(11) NOT NULL,
  `nombre_tutor` varchar(100) NOT NULL,
  `telefono_tutor` varchar(20) NOT NULL,
  `firma_tutor` text NOT NULL,
  `nombre_anfitrion` varchar(100) NOT NULL,
  `firma_anfitrion` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grupo_reservas`
--

CREATE TABLE `grupo_reservas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `horarios_anfitrion`
--

CREATE TABLE `horarios_anfitrion` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `anfitrion_id` bigint(20) UNSIGNED NOT NULL,
  `horarios` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`horarios`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `horarios_anfitrion`
--

INSERT INTO `horarios_anfitrion` (`id`, `anfitrion_id`, `horarios`, `created_at`, `updated_at`) VALUES
(1, 2, '{\"lunes\":[\"08:00\",\"08:30\",\"09:00\",\"09:30\",\"10:00\",\"10:30\",\"11:00\",\"11:30\",\"12:00\",\"12:30\",\"13:00\",\"13:30\",\"14:00\",\"14:30\",\"15:00\",\"15:30\",\"16:00\",\"16:30\",\"17:00\",\"17:30\",\"18:00\",\"18:30\",\"19:00\",\"19:30\",\"20:00\",\"20:30\"],\"martes\":[\"08:00\",\"08:30\",\"09:00\",\"09:30\",\"10:00\"],\"mi\\u00e9rcoles\":[\"08:00\",\"08:30\",\"09:00\"],\"jueves\":[\"08:00\",\"08:30\",\"09:00\"],\"viernes\":[\"08:00\",\"08:30\",\"09:00\"],\"s\\u00e1bado\":[\"08:00\",\"08:30\",\"09:00\"],\"domingo\":[\"08:00\",\"08:30\",\"09:00\"]}', '2025-10-07 01:27:52', '2025-10-07 23:42:45'),
(2, 9, '{\"lunes\": [\"08:00\", \"08:30\", \"09:00\", \"09:30\", \"10:00\", \"10:30\", \"11:00\", \"11:30\", \"12:00\", \"12:30\", \"13:00\", \"13:30\", \"14:00\", \"14:30\", \"15:00\", \"15:30\", \"16:00\", \"16:30\", \"17:00\", \"17:30\", \"18:00\", \"18:30\", \"19:00\", \"19:30\", \"20:00\", \"20:30\"]}', '2025-10-07 01:28:03', '2025-10-07 01:28:03');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_02_16_000001_create_spas_table', 1),
(5, '2025_02_16_024929_create_cabinas_table', 1),
(6, '2025_02_16_025532_create_anfitriones_table', 1),
(7, '2025_02_16_030610_create_clients_table', 1),
(8, '2025_02_16_030629_create_experiences_table', 1),
(9, '2025_02_16_030630_create_reservation_structure', 1),
(10, '2025_02_16_030632_create_sales_table', 1),
(11, '2025_03_23_133844_create_boutique_table', 1),
(12, '2025_04_20_170133_create_blocked_slots_table', 1),
(13, '2025_05_29_192454_create_gimnasio_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `spa_id` bigint(20) UNSIGNED NOT NULL,
  `experiencia_id` bigint(20) UNSIGNED NOT NULL,
  `cabina_id` bigint(20) UNSIGNED DEFAULT NULL,
  `anfitrion_id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `grupo_reserva_id` bigint(20) UNSIGNED DEFAULT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 1,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `observaciones` text DEFAULT NULL,
  `check_in` tinyint(1) NOT NULL DEFAULT 0,
  `check_out` tinyint(1) NOT NULL DEFAULT 0,
  `locker` varchar(255) DEFAULT NULL,
  `estado` enum('activa','cancelada') NOT NULL DEFAULT 'activa',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `grupo_reserva_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `spa_id` bigint(20) UNSIGNED NOT NULL,
  `reservacion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `forma_pago` enum('efectivo','tarjeta_debito','tarjeta_credito','habitacion','recepcion','transferencia','otro') NOT NULL,
  `referencia_pago` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `impuestos` decimal(10,2) NOT NULL,
  `propina` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `cobrado` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('lKtAuPKJv9aZbzfdhI2ourYZQENwLlvsLpBL7yGp', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZWJjVlBSTVdjT0V1NThJeVYxU0lHZVJyb25hbGFTS01kcVpkREtxVCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7fX0=', 1759881384);

-- --------------------------------------------------------

--
-- Table structure for table `spas`
--

CREATE TABLE `spas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `spas`
--

INSERT INTO `spas` (`id`, `nombre`, `direccion`, `created_at`, `updated_at`) VALUES
(1, 'Palacio', '', '2025-10-07 22:22:05', '2025-10-07 22:22:05'),
(2, 'Pierre', '', '2025-10-07 22:22:05', '2025-10-07 22:22:05'),
(3, 'Princess', '', '2025-10-07 22:22:05', '2025-10-07 22:22:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `rfc` varchar(255) NOT NULL,
  `rol` varchar(255) NOT NULL,
  `area` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `rfc`, `rol`, `area`, `password`, `created_at`, `updated_at`) VALUES
(1, 'Admin Pierre', 'PERJ850412ABC', 'administrador', 'pierre', '$2y$12$HXvZrmVaIEbF4axR8CPDQ.Okzsy2t.KlOepX7g0ZvQHPbngpQ5VjC', '2025-10-07 22:22:05', '2025-10-07 22:22:05'),
(2, 'Admin Princess', 'GARC891015XYZ', 'administrador', 'princess', '$2y$12$8xQNjf86mkKidF97cJ7MDuCaFFCq5vPJcEnbJ43.cCLyhErQ2.TRu', '2025-10-07 22:22:05', '2025-10-07 22:22:05'),
(3, 'Admin Palacio', 'LOPM810726DEF', 'administrador', 'palacio', '$2y$12$./sDhIEyQFQQDXjF3gtI/.DOzdhobgkhbFPZc8XHXcVLw9YCXFBWC', '2025-10-07 22:22:06', '2025-10-07 22:22:06'),
(4, 'Recepcionista Palacio', 'HERM920312JKL', 'recepcionista', 'palacio', '$2y$12$3a3SJDiA.t.SDdfkxk0HBu/hJUHz3NOIhbxnDcg488wynrfOkqKyS', '2025-10-07 22:22:06', '2025-10-07 22:22:06'),
(5, 'Recepcionista Pierre', 'SANC950827MNO', 'recepcionista', 'pierre', '$2y$12$Y5ZZr5rrZescYPH8qHCR9OyU3heRM7g5SaYQR2zgRnDXhudDhXUE.', '2025-10-07 22:22:06', '2025-10-07 22:22:06'),
(6, 'Recepcionista Princess', 'TORR870204PQR', 'recepcionista', 'princess', '$2y$12$s21tZY0jjav8h09bMiDgNeAtn3KxihGEkYcfssM6oOL71zmly/SS.', '2025-10-07 22:22:07', '2025-10-07 22:22:07'),
(7, 'Master', 'CAST800610STU', 'master', 'todas', '$2y$12$X8lJa7VNgyDSU26TKhVe9O4oJCvAjOmUAG02bWiji.VDcZ2WDSMa.', '2025-10-07 22:22:07', '2025-10-07 22:22:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anfitriones`
--
ALTER TABLE `anfitriones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `anfitriones_rfc_unique` (`RFC`),
  ADD KEY `anfitriones_spa_id_foreign` (`spa_id`);

--
-- Indexes for table `anfitrion_operativo`
--
ALTER TABLE `anfitrion_operativo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anfitrion_operativo_anfitrion_id_foreign` (`anfitrion_id`);

--
-- Indexes for table `blocked_slots`
--
ALTER TABLE `blocked_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `blocked_slots_spa_id_foreign` (`spa_id`),
  ADD KEY `blocked_slots_anfitrion_id_foreign` (`anfitrion_id`);

--
-- Indexes for table `boutique_articulos`
--
ALTER TABLE `boutique_articulos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_numero_auxiliar_hotel` (`numero_auxiliar`,`fk_id_hotel`),
  ADD KEY `boutique_articulos_fk_id_familia_foreign` (`fk_id_familia`),
  ADD KEY `boutique_articulos_fk_id_hotel_foreign` (`fk_id_hotel`);

--
-- Indexes for table `boutique_articulos_familias`
--
ALTER TABLE `boutique_articulos_familias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_familia_hotel` (`nombre`,`fk_id_hotel`),
  ADD KEY `boutique_articulos_familias_fk_id_hotel_foreign` (`fk_id_hotel`);

--
-- Indexes for table `boutique_compras`
--
ALTER TABLE `boutique_compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `boutique_compras_fk_id_articulo_foreign` (`fk_id_articulo`);

--
-- Indexes for table `boutique_compras_eliminadas`
--
ALTER TABLE `boutique_compras_eliminadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `boutique_compras_eliminadas_fk_id_compra_foreign` (`fk_id_compra`);

--
-- Indexes for table `boutique_config_ventas_clasificacion`
--
ALTER TABLE `boutique_config_ventas_clasificacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_config_ventas_clasificacion_hotel` (`nombre`,`fk_id_hotel`),
  ADD KEY `boutique_config_ventas_clasificacion_fk_id_hotel_foreign` (`fk_id_hotel`);

--
-- Indexes for table `boutique_formas_pago`
--
ALTER TABLE `boutique_formas_pago`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `boutique_formas_pago_nombre_unique` (`nombre`);

--
-- Indexes for table `boutique_inventario`
--
ALTER TABLE `boutique_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `boutique_inventario_fk_id_compra_foreign` (`fk_id_compra`);

--
-- Indexes for table `boutique_ventas`
--
ALTER TABLE `boutique_ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `boutique_ventas_fk_id_hotel_foreign` (`fk_id_hotel`),
  ADD KEY `boutique_ventas_fk_id_forma_pago_foreign` (`fk_id_forma_pago`);

--
-- Indexes for table `boutique_ventas_detalles`
--
ALTER TABLE `boutique_ventas_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `boutique_ventas_detalles_fk_id_folio_foreign` (`fk_id_folio`),
  ADD KEY `boutique_ventas_detalles_fk_id_compra_foreign` (`fk_id_compra`),
  ADD KEY `boutique_ventas_detalles_fk_id_anfitrion_foreign` (`fk_id_anfitrion`);

--
-- Indexes for table `cabinas`
--
ALTER TABLE `cabinas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cabinas_spa_id_foreign` (`spa_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clients_correo_unique` (`correo`);

--
-- Indexes for table `evaluation_forms`
--
ALTER TABLE `evaluation_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evaluation_forms_reservation_id_foreign` (`reservation_id`),
  ADD KEY `evaluation_forms_cliente_id_foreign` (`cliente_id`);

--
-- Indexes for table `experiences`
--
ALTER TABLE `experiences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `experiences_spa_id_foreign` (`spa_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `gimnasio_config_qr_code`
--
ALTER TABLE `gimnasio_config_qr_code`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gimnasio_config_qr_code_fk_id_hotel_foreign` (`fk_id_hotel`);

--
-- Indexes for table `gimnasio_qrcodes`
--
ALTER TABLE `gimnasio_qrcodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `gimnasio_qrcodes_token_unique` (`token`),
  ADD KEY `gimnasio_qrcodes_fk_id_hotel_contexto_activo_index` (`fk_id_hotel`,`contexto`,`activo`),
  ADD KEY `gimnasio_qrcodes_fecha_expiracion_index` (`fecha_expiracion`);

--
-- Indexes for table `gimnasio_registros_adultos`
--
ALTER TABLE `gimnasio_registros_adultos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gimnasio_registros_adultos_fk_id_hotel_foreign` (`fk_id_hotel`);

--
-- Indexes for table `gimnasio_registros_menores`
--
ALTER TABLE `gimnasio_registros_menores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gimnasio_registros_menores_fk_id_hotel_foreign` (`fk_id_hotel`);

--
-- Indexes for table `grupo_reservas`
--
ALTER TABLE `grupo_reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grupo_reservas_cliente_id_foreign` (`cliente_id`);

--
-- Indexes for table `horarios_anfitrion`
--
ALTER TABLE `horarios_anfitrion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `horarios_anfitrion_anfitrion_id_foreign` (`anfitrion_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservations_spa_id_foreign` (`spa_id`),
  ADD KEY `reservations_experiencia_id_foreign` (`experiencia_id`),
  ADD KEY `reservations_cabina_id_foreign` (`cabina_id`),
  ADD KEY `reservations_anfitrion_id_foreign` (`anfitrion_id`),
  ADD KEY `reservations_cliente_id_foreign` (`cliente_id`),
  ADD KEY `reservations_grupo_reserva_id_foreign` (`grupo_reserva_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_grupo_reserva_id_foreign` (`grupo_reserva_id`),
  ADD KEY `sales_cliente_id_foreign` (`cliente_id`),
  ADD KEY `sales_spa_id_foreign` (`spa_id`),
  ADD KEY `sales_reservacion_id_foreign` (`reservacion_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `spas`
--
ALTER TABLE `spas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `spas_nombre_unique` (`nombre`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_rfc_unique` (`rfc`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anfitriones`
--
ALTER TABLE `anfitriones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `anfitrion_operativo`
--
ALTER TABLE `anfitrion_operativo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `blocked_slots`
--
ALTER TABLE `blocked_slots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `boutique_articulos`
--
ALTER TABLE `boutique_articulos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `boutique_articulos_familias`
--
ALTER TABLE `boutique_articulos_familias`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `boutique_compras`
--
ALTER TABLE `boutique_compras`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `boutique_compras_eliminadas`
--
ALTER TABLE `boutique_compras_eliminadas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `boutique_config_ventas_clasificacion`
--
ALTER TABLE `boutique_config_ventas_clasificacion`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `boutique_formas_pago`
--
ALTER TABLE `boutique_formas_pago`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `boutique_inventario`
--
ALTER TABLE `boutique_inventario`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `boutique_ventas`
--
ALTER TABLE `boutique_ventas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `boutique_ventas_detalles`
--
ALTER TABLE `boutique_ventas_detalles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cabinas`
--
ALTER TABLE `cabinas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `evaluation_forms`
--
ALTER TABLE `evaluation_forms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `experiences`
--
ALTER TABLE `experiences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gimnasio_config_qr_code`
--
ALTER TABLE `gimnasio_config_qr_code`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gimnasio_qrcodes`
--
ALTER TABLE `gimnasio_qrcodes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gimnasio_registros_adultos`
--
ALTER TABLE `gimnasio_registros_adultos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gimnasio_registros_menores`
--
ALTER TABLE `gimnasio_registros_menores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grupo_reservas`
--
ALTER TABLE `grupo_reservas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `horarios_anfitrion`
--
ALTER TABLE `horarios_anfitrion`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spas`
--
ALTER TABLE `spas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `anfitriones`
--
ALTER TABLE `anfitriones`
  ADD CONSTRAINT `anfitriones_spa_id_foreign` FOREIGN KEY (`spa_id`) REFERENCES `spas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `anfitrion_operativo`
--
ALTER TABLE `anfitrion_operativo`
  ADD CONSTRAINT `anfitrion_operativo_anfitrion_id_foreign` FOREIGN KEY (`anfitrion_id`) REFERENCES `anfitriones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blocked_slots`
--
ALTER TABLE `blocked_slots`
  ADD CONSTRAINT `blocked_slots_anfitrion_id_foreign` FOREIGN KEY (`anfitrion_id`) REFERENCES `anfitriones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blocked_slots_spa_id_foreign` FOREIGN KEY (`spa_id`) REFERENCES `spas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `boutique_articulos`
--
ALTER TABLE `boutique_articulos`
  ADD CONSTRAINT `boutique_articulos_fk_id_familia_foreign` FOREIGN KEY (`fk_id_familia`) REFERENCES `boutique_articulos_familias` (`id`),
  ADD CONSTRAINT `boutique_articulos_fk_id_hotel_foreign` FOREIGN KEY (`fk_id_hotel`) REFERENCES `spas` (`id`);

--
-- Constraints for table `boutique_articulos_familias`
--
ALTER TABLE `boutique_articulos_familias`
  ADD CONSTRAINT `boutique_articulos_familias_fk_id_hotel_foreign` FOREIGN KEY (`fk_id_hotel`) REFERENCES `spas` (`id`);

--
-- Constraints for table `boutique_compras`
--
ALTER TABLE `boutique_compras`
  ADD CONSTRAINT `boutique_compras_fk_id_articulo_foreign` FOREIGN KEY (`fk_id_articulo`) REFERENCES `boutique_articulos` (`id`);

--
-- Constraints for table `boutique_compras_eliminadas`
--
ALTER TABLE `boutique_compras_eliminadas`
  ADD CONSTRAINT `boutique_compras_eliminadas_fk_id_compra_foreign` FOREIGN KEY (`fk_id_compra`) REFERENCES `boutique_compras` (`id`);

--
-- Constraints for table `boutique_config_ventas_clasificacion`
--
ALTER TABLE `boutique_config_ventas_clasificacion`
  ADD CONSTRAINT `boutique_config_ventas_clasificacion_fk_id_hotel_foreign` FOREIGN KEY (`fk_id_hotel`) REFERENCES `spas` (`id`);

--
-- Constraints for table `boutique_inventario`
--
ALTER TABLE `boutique_inventario`
  ADD CONSTRAINT `boutique_inventario_fk_id_compra_foreign` FOREIGN KEY (`fk_id_compra`) REFERENCES `boutique_compras` (`id`);

--
-- Constraints for table `boutique_ventas`
--
ALTER TABLE `boutique_ventas`
  ADD CONSTRAINT `boutique_ventas_fk_id_forma_pago_foreign` FOREIGN KEY (`fk_id_forma_pago`) REFERENCES `boutique_formas_pago` (`id`),
  ADD CONSTRAINT `boutique_ventas_fk_id_hotel_foreign` FOREIGN KEY (`fk_id_hotel`) REFERENCES `spas` (`id`);

--
-- Constraints for table `boutique_ventas_detalles`
--
ALTER TABLE `boutique_ventas_detalles`
  ADD CONSTRAINT `boutique_ventas_detalles_fk_id_anfitrion_foreign` FOREIGN KEY (`fk_id_anfitrion`) REFERENCES `anfitriones` (`id`),
  ADD CONSTRAINT `boutique_ventas_detalles_fk_id_compra_foreign` FOREIGN KEY (`fk_id_compra`) REFERENCES `boutique_compras` (`id`),
  ADD CONSTRAINT `boutique_ventas_detalles_fk_id_folio_foreign` FOREIGN KEY (`fk_id_folio`) REFERENCES `boutique_ventas` (`id`);

--
-- Constraints for table `cabinas`
--
ALTER TABLE `cabinas`
  ADD CONSTRAINT `cabinas_spa_id_foreign` FOREIGN KEY (`spa_id`) REFERENCES `spas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluation_forms`
--
ALTER TABLE `evaluation_forms`
  ADD CONSTRAINT `evaluation_forms_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `evaluation_forms_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `experiences`
--
ALTER TABLE `experiences`
  ADD CONSTRAINT `experiences_spa_id_foreign` FOREIGN KEY (`spa_id`) REFERENCES `spas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gimnasio_config_qr_code`
--
ALTER TABLE `gimnasio_config_qr_code`
  ADD CONSTRAINT `gimnasio_config_qr_code_fk_id_hotel_foreign` FOREIGN KEY (`fk_id_hotel`) REFERENCES `spas` (`id`);

--
-- Constraints for table `gimnasio_qrcodes`
--
ALTER TABLE `gimnasio_qrcodes`
  ADD CONSTRAINT `gimnasio_qrcodes_fk_id_hotel_foreign` FOREIGN KEY (`fk_id_hotel`) REFERENCES `spas` (`id`);

--
-- Constraints for table `gimnasio_registros_adultos`
--
ALTER TABLE `gimnasio_registros_adultos`
  ADD CONSTRAINT `gimnasio_registros_adultos_fk_id_hotel_foreign` FOREIGN KEY (`fk_id_hotel`) REFERENCES `spas` (`id`);

--
-- Constraints for table `gimnasio_registros_menores`
--
ALTER TABLE `gimnasio_registros_menores`
  ADD CONSTRAINT `gimnasio_registros_menores_fk_id_hotel_foreign` FOREIGN KEY (`fk_id_hotel`) REFERENCES `spas` (`id`);

--
-- Constraints for table `grupo_reservas`
--
ALTER TABLE `grupo_reservas`
  ADD CONSTRAINT `grupo_reservas_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `horarios_anfitrion`
--
ALTER TABLE `horarios_anfitrion`
  ADD CONSTRAINT `horarios_anfitrion_anfitrion_id_foreign` FOREIGN KEY (`anfitrion_id`) REFERENCES `anfitriones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_anfitrion_id_foreign` FOREIGN KEY (`anfitrion_id`) REFERENCES `anfitriones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_cabina_id_foreign` FOREIGN KEY (`cabina_id`) REFERENCES `cabinas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservations_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_experiencia_id_foreign` FOREIGN KEY (`experiencia_id`) REFERENCES `experiences` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_grupo_reserva_id_foreign` FOREIGN KEY (`grupo_reserva_id`) REFERENCES `grupo_reservas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservations_spa_id_foreign` FOREIGN KEY (`spa_id`) REFERENCES `spas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_grupo_reserva_id_foreign` FOREIGN KEY (`grupo_reserva_id`) REFERENCES `grupo_reservas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_reservacion_id_foreign` FOREIGN KEY (`reservacion_id`) REFERENCES `reservations` (`id`),
  ADD CONSTRAINT `sales_spa_id_foreign` FOREIGN KEY (`spa_id`) REFERENCES `spas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
