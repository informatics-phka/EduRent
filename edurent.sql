-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Erstellungszeit: 15. Okt 2024 um 14:15
-- Server-Version: 5.7.42
-- PHP-Version: 8.3.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `web346_edurent`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `admins`
--

CREATE TABLE `admins` (
  `a_id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `department` int(11) NOT NULL DEFAULT '-1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `blocked`
--

CREATE TABLE `blocked` (
  `id` int(11) NOT NULL,
  `reason` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `blocked` (`id`, `reason`) VALUES
(0, 'Frei'),
(1, 'Dauerhaft Belegt'),
(2, 'Braucht Updates'),
(3, 'Fehlfunktion'),
(4, 'Reparatur'),
(5, 'Vor-Ort');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_de` varchar(128) NOT NULL,
  `department_en` varchar(128) NOT NULL,
  `announce1_de` varchar(256) NOT NULL,
  `announce1_en` varchar(256) NOT NULL,
  `mail` varchar(40) NOT NULL,
  `room` varchar(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `departments` (`department_id`, `department_de`, `department_en`, `announce1_de`, `announce1_en`, `mail`, `room`) VALUES
(-1, 'Deaktiviert', 'Deactivated', '', '', 'technikausleihe@ph-karlsruhe.de', 'keiner'),
(0, 'Alle', 'All', '', '', 'technikausleihe@ph-karlsruhe.de', 'keiner');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `devices_of_reservations`
--

CREATE TABLE `devices_of_reservations` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `device_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `device_list`
--

CREATE TABLE `device_list` (
  `device_id` int(11) NOT NULL,
  `device_type_id` int(11) DEFAULT NULL,
  `device_tag` int(10) NOT NULL,
  `serialnumber` varchar(16) DEFAULT NULL,
  `blocked` int(1) NOT NULL DEFAULT '0',
  `note` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `device_type`
--

CREATE TABLE `device_type` (
  `device_type_id` int(11) NOT NULL,
  `device_type_name` varchar(64) DEFAULT NULL,
  `device_type_indicator` varchar(2) DEFAULT NULL,
  `device_type_info` varchar(128) DEFAULT NULL,
  `tooltip` varchar(512) DEFAULT NULL,
  `device_type_storage` varchar(64) NOT NULL,
  `device_type_img_path` varchar(128) DEFAULT NULL,
  `home_department` int(11) NOT NULL,
  `max_loan_days` int(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `rent_days`
--

CREATE TABLE `rent_days` (
  `id` int(11) NOT NULL,
  `time` varchar(128) DEFAULT NULL,
  `dayofweek` int(1) DEFAULT NULL,
  `d_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `status` int(1) DEFAULT '1',
  `user_id` int(11) DEFAULT NULL,
  `orga` varchar(200) NOT NULL,
  `time_from` varchar(100) NOT NULL,
  `time_to` varchar(100) NOT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `room_from` varchar(50) NOT NULL,
  `room_to` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `server`
--

CREATE TABLE `server` (
  `id` int(11) NOT NULL,
  `days_bookable_in_advance` int(11) NOT NULL,
  `lead_time_days` int(11) NOT NULL,
  `max_loan_duration` int(11) NOT NULL,
  `debug` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `server` (`id`, `days_bookable_in_advance`, `lead_time_days`, `max_loan_duration`, `debug`) VALUES
(1, 30, 8, 14, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `type_department`
--

CREATE TABLE `type_department` (
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(30) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `fn` varchar(30) DEFAULT NULL,
  `ln` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`a_id`),
  ADD KEY `us_id` (`u_id`),
  ADD KEY `depart_id` (`department`);

--
-- Indizes für die Tabelle `blocked`
--
ALTER TABLE `blocked`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indizes für die Tabelle `devices_of_reservations`
--
ALTER TABLE `devices_of_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `Device_id` (`device_id`);

--
-- Indizes für die Tabelle `device_list`
--
ALTER TABLE `device_list`
  ADD PRIMARY KEY (`device_id`),
  ADD KEY `device_type_id` (`device_type_id`),
  ADD KEY `blocked` (`blocked`);

--
-- Indizes für die Tabelle `device_type`
--
ALTER TABLE `device_type`
  ADD PRIMARY KEY (`device_type_id`),
  ADD KEY `dep_home` (`home_department`);

--
-- Indizes für die Tabelle `rent_days`
--
ALTER TABLE `rent_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id3` (`d_id`);

--
-- Indizes für die Tabelle `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `department_id2` (`department_id`);

--
-- Indizes für die Tabelle `server`
--
ALTER TABLE `server`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `type_department`
--
ALTER TABLE `type_department`
  ADD PRIMARY KEY (`id`),
  ADD KEY `t_id` (`type_id`),
  ADD KEY `dep_id` (`department_id`);

--
-- Indizes für die Tabelle `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `admins`
--
ALTER TABLE `admins`
  MODIFY `a_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `blocked`
--
ALTER TABLE `blocked`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `devices_of_reservations`
--
ALTER TABLE `devices_of_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `device_list`
--
ALTER TABLE `device_list`
  MODIFY `device_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `device_type`
--
ALTER TABLE `device_type`
  MODIFY `device_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `rent_days`
--
ALTER TABLE `rent_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `server`
--
ALTER TABLE `server`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `type_department`
--
ALTER TABLE `type_department`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `depart_id` FOREIGN KEY (`department`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `us_id` FOREIGN KEY (`u_id`) REFERENCES `user` (`id`);

--
-- Constraints der Tabelle `devices_of_reservations`
--
ALTER TABLE `devices_of_reservations`
  ADD CONSTRAINT `Device_id` FOREIGN KEY (`device_id`) REFERENCES `device_list` (`device_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reservation_id` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `device_list`
--
ALTER TABLE `device_list`
  ADD CONSTRAINT `blocked` FOREIGN KEY (`blocked`) REFERENCES `blocked` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `device_type_id` FOREIGN KEY (`device_type_id`) REFERENCES `device_type` (`device_type_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `device_type`
--
ALTER TABLE `device_type`
  ADD CONSTRAINT `dep_home` FOREIGN KEY (`home_department`) REFERENCES `departments` (`department_id`);

--
-- Constraints der Tabelle `rent_days`
--
ALTER TABLE `rent_days`
  ADD CONSTRAINT `department_id3` FOREIGN KEY (`d_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `department_id2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `type_department`
--
ALTER TABLE `type_department`
  ADD CONSTRAINT `dep_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `t_id` FOREIGN KEY (`type_id`) REFERENCES `device_type` (`device_type_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
