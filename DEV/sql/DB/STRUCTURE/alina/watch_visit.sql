-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               5.7.29-0ubuntu0.16.04.1 - (Ubuntu)
-- Server OS:                    Linux
-- HeidiSQL Version:             11.1.0.6145
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table alina.watch_visit
DROP TABLE IF EXISTS `watch_visit`;
CREATE TABLE IF NOT EXISTS `watch_visit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip` char(46) DEFAULT NULL,
  `browser_enc` varchar(256) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `query_string` text,
  `visited_at` bigint(8) DEFAULT NULL,
  `method` char(20) DEFAULT NULL,
  `data` longtext,
  `controller` char(50) DEFAULT NULL,
  `action` char(50) DEFAULT NULL,
  `ajax` tinyint(1) DEFAULT '0',
  `suspicious` tinyint(1) DEFAULT '0',
  `fingerprint` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IND_WV_VISITED_AT` (`visited_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data exporting was unselected.

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
