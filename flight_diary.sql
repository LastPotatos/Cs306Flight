-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 21, 2026 at 09:19 AM
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
-- Database: `flight diary`
--

-- --------------------------------------------------------

--
-- Table structure for table `aircraft`
--

CREATE TABLE `aircraft` (
  `Registration-Number` varchar(8) NOT NULL,
  `ICAO code of the airline` varchar(3) NOT NULL,
  `Type` varchar(15) NOT NULL,
  `SeetConfigs` text NOT NULL,
  `Capacity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `airline`
--

CREATE TABLE `airline` (
  `Name` text NOT NULL,
  `ICAO` varchar(3) NOT NULL,
  `Fleet Size` int(11) NOT NULL,
  `IATA code` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `airport`
--

CREATE TABLE `airport` (
  `Gate Delay` int(11) NOT NULL,
  `Coordinate` varchar(40) NOT NULL,
  `ICAO code` varchar(10) NOT NULL,
  `IATA code` varchar(3) NOT NULL,
  `Runways` varchar(20) NOT NULL,
  `taxi length(minutes)` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flight`
--

CREATE TABLE `flight` (
  `E-mail` varchar(100) NOT NULL,
  `Aircraft-Registration` varchar(8) NOT NULL,
  `D_Airport_ICAO` varchar(6) NOT NULL,
  `A_Airport_ICAO` varchar(6) NOT NULL,
  `Date` datetime NOT NULL,
  `Flight Number` varchar(6) NOT NULL,
  `SDeparture` datetime NOT NULL,
  `SArrival` datetime NOT NULL,
  `Description` text NOT NULL,
  `ADeparture` datetime NOT NULL,
  `AArrival` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `path`
--

CREATE TABLE `path` (
  `Flight Number` varchar(6) NOT NULL,
  `TimeStamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Speed` int(11) NOT NULL,
  `Coordinate` varchar(20) NOT NULL,
  `Altitude` int(11) NOT NULL,
  `Heading` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket`
--

CREATE TABLE `ticket` (
  `E-mail` varchar(50) NOT NULL,
  `Flight Number` varchar(6) NOT NULL,
  `Date` datetime NOT NULL,
  `Seat` varchar(3) NOT NULL,
  `Class` varchar(50) NOT NULL,
  `Add-ons` varchar(100) NOT NULL,
  `Price` int(11) NOT NULL,
  `Currency` varchar(50) NOT NULL,
  `Airline Points` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `Name` varchar(50) NOT NULL,
  `E-mail` varchar(100) NOT NULL,
  `Password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aircraft`
--
ALTER TABLE `aircraft`
  ADD PRIMARY KEY (`Registration-Number`),
  ADD UNIQUE KEY `ICAO code of the airline` (`ICAO code of the airline`);

--
-- Indexes for table `airline`
--
ALTER TABLE `airline`
  ADD PRIMARY KEY (`ICAO`);

--
-- Indexes for table `flight`
--
ALTER TABLE `flight`
  ADD PRIMARY KEY (`Flight Number`,`Date`),
  ADD UNIQUE KEY `Date` (`Date`,`Flight Number`),
  ADD UNIQUE KEY `E-mail` (`E-mail`),
  ADD UNIQUE KEY `Aircraft-Registration` (`Aircraft-Registration`),
  ADD UNIQUE KEY `A_Airport_ICAO` (`A_Airport_ICAO`),
  ADD UNIQUE KEY `D_Airport_ICAO` (`D_Airport_ICAO`);

--
-- Indexes for table `path`
--
ALTER TABLE `path`
  ADD PRIMARY KEY (`Flight Number`,`TimeStamp`);

--
-- Indexes for table `ticket`
--
ALTER TABLE `ticket`
  ADD PRIMARY KEY (`Flight Number`,`Date`,`Seat`),
  ADD KEY `For Relation` (`Date`,`Flight Number`),
  ADD KEY `Buys Relation` (`E-mail`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`E-mail`),
  ADD UNIQUE KEY `E-mail` (`E-mail`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `aircraft`
--
ALTER TABLE `aircraft`
  ADD CONSTRAINT `owns relation` FOREIGN KEY (`ICAO code of the airline`) REFERENCES `airline` (`ICAO`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `flight`
--
ALTER TABLE `flight`
  ADD CONSTRAINT `Logs Relation` FOREIGN KEY (`E-mail`) REFERENCES `user` (`E-mail`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `uses relation` FOREIGN KEY (`Aircraft-Registration`) REFERENCES `aircraft` (`Registration-Number`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `path`
--
ALTER TABLE `path`
  ADD CONSTRAINT `Flies on relation` FOREIGN KEY (`Flight Number`) REFERENCES `flight` (`Flight Number`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ticket`
--
ALTER TABLE `ticket`
  ADD CONSTRAINT `Buys Relation` FOREIGN KEY (`E-mail`) REFERENCES `user` (`E-mail`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `For Relation` FOREIGN KEY (`Date`,`Flight Number`) REFERENCES `flight` (`Date`, `Flight Number`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
