-- phpMyAdmin SQL Dump
-- version 4.7.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 19, 2017 at 04:01 PM
-- Server version: 10.1.28-MariaDB
-- PHP Version: 7.1.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `umabis`
--
CREATE DATABASE IF NOT EXISTS `umabis` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `umabis`;

-- --------------------------------------------------------

--
-- Table structure for table `blacklisting_categories`
--

CREATE TABLE `blacklisting_categories` (
  `category` varchar(535) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blacklisting_categories`
--

INSERT INTO `blacklisting_categories` (`category`, `description`) VALUES
('cheat', 'The player\'s client is hacked in a such way that it allows the player to cheat. Cheating can be e.g. flying without the fly privilege, displaying the minimap on a no-minimap server, passing through walls...\r\nClient-side mods are never considered cheat, except if they make usage of API functions that the server forbids.'),
('grief', 'Griefing is the act of irritating and angering people in video games through the use of destruction, construction, or social engineering.\r\nOnly vicious and fully intentional griefing acts should be globally blacklisted.'),
('other', 'Use this category if someone has done a serious and malicious act which should be globally blacklisted and that fits in no category.\r\nPlease make a complete description in the \"reason\" field.'),
('spam', 'Spamming or flooding is the act of sending a large number of messages in the chat.\r\nOnly spamming acts made with the only intention to harm the sever should be globally blacklisted.');

-- --------------------------------------------------------

--
-- Table structure for table `blacklist_entries`
--

CREATE TABLE `blacklist_entries` (
  `ID` int(10) UNSIGNED NOT NULL,
  `nick` varchar(535) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `date` datetime NOT NULL,
  `source_moderator` varchar(535) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `reason` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `category` varchar(535) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `expiration_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `global_moderators`
--

CREATE TABLE `global_moderators` (
  `nick` varchar(535) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `servers`
--

CREATE TABLE `servers` (
  `name` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `address` varchar(535) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `port` mediumint(8) UNSIGNED NOT NULL,
  `ID` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Server informations, as displayed in the public list.';

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `token` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `nick` varchar(535) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `expiration_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `nick` varchar(535) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `email` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `is_email_public` tinyint(1) NOT NULL DEFAULT '1',
  `language_main` tinytext CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `language_fallback_1` tinytext CHARACTER SET ascii COLLATE ascii_bin,
  `language_fallback_2` tinytext CHARACTER SET ascii COLLATE ascii_bin,
  `password_hash` text CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_IPs`
--

CREATE TABLE `user_IPs` (
  `ID` int(11) NOT NULL,
  `nick` varchar(535) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `IP_address` varchar(39) CHARACTER SET ascii COLLATE ascii_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blacklisting_categories`
--
ALTER TABLE `blacklisting_categories`
  ADD PRIMARY KEY (`category`);

--
-- Indexes for table `blacklist_entries`
--
ALTER TABLE `blacklist_entries`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `source_moderator` (`source_moderator`),
  ADD KEY `nick` (`nick`),
  ADD KEY `category` (`category`);

--
-- Indexes for table `global_moderators`
--
ALTER TABLE `global_moderators`
  ADD PRIMARY KEY (`nick`);

--
-- Indexes for table `servers`
--
ALTER TABLE `servers`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `address` (`address`,`port`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`nick`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`nick`);

--
-- Indexes for table `user_IPs`
--
ALTER TABLE `user_IPs`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `IP_address` (`IP_address`),
  ADD KEY `nick` (`nick`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blacklist_entries`
--
ALTER TABLE `blacklist_entries`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `servers`
--
ALTER TABLE `servers`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_IPs`
--
ALTER TABLE `user_IPs`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blacklist_entries`
--
ALTER TABLE `blacklist_entries`
  ADD CONSTRAINT `blacklist_entries_ibfk_4` FOREIGN KEY (`nick`) REFERENCES `users` (`nick`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `blacklist_entries_ibfk_5` FOREIGN KEY (`category`) REFERENCES `blacklisting_categories` (`category`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `blacklist_entries_ibfk_6` FOREIGN KEY (`source_moderator`) REFERENCES `users` (`nick`) ON UPDATE CASCADE;

--
-- Constraints for table `global_moderators`
--
ALTER TABLE `global_moderators`
  ADD CONSTRAINT `global_moderators_ibfk_1` FOREIGN KEY (`nick`) REFERENCES `users` (`nick`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`nick`) REFERENCES `users` (`nick`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_IPs`
--
ALTER TABLE `user_IPs`
  ADD CONSTRAINT `user_IPs_ibfk_1` FOREIGN KEY (`nick`) REFERENCES `users` (`nick`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
