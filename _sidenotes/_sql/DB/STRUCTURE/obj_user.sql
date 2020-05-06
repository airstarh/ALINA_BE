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
/*Table structure for table `user` */

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mail` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstname` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emblem` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth` bigint(8) DEFAULT NULL,
  `language` char(5) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `timezone` bigint(20) DEFAULT '1',
  `is_verified` tinyint(1) DEFAULT '0',
  `banned_till` int(12) DEFAULT '0',
  `created_at` int(12) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT '0',
  `fingerprint` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `about_myself` text COLLATE utf8mb4_unicode_ci,
  `reset_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_required` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
