/*
SQLyog Ultimate
MySQL - 5.7.25-log : Database - alina
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `error_log` */

DROP TABLE IF EXISTS `error_log`;

CREATE TABLE `error_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` char(46) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` text COLLATE utf8mb4_unicode_ci,
  `method` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ajax` tinyint(1) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `url_path` text COLLATE utf8mb4_unicode_ci,
  `query_string` text COLLATE utf8mb4_unicode_ci,
  `request` text COLLATE utf8mb4_unicode_ci,
  `error_class` text COLLATE utf8mb4_unicode_ci,
  `error_severity` text COLLATE utf8mb4_unicode_ci,
  `error_code` text COLLATE utf8mb4_unicode_ci,
  `error_text` text COLLATE utf8mb4_unicode_ci,
  `error_file` text COLLATE utf8mb4_unicode_ci,
  `error_line` text COLLATE utf8mb4_unicode_ci,
  `error_trace` text COLLATE utf8mb4_unicode_ci,
  `at` int(12) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=220 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
