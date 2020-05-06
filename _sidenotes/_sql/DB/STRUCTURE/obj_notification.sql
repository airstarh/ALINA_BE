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
/*Table structure for table `notification` */

DROP TABLE IF EXISTS `notification`;

CREATE TABLE `notification` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `to_id` bigint(20) DEFAULT NULL,
  `from_id` bigint(20) DEFAULT '1',
  `txt` text COLLATE utf8mb4_unicode_ci,
  `params` text COLLATE utf8mb4_unicode_ci,
  `link` text COLLATE utf8mb4_unicode_ci,
  `severity_id` tinyint(20) DEFAULT '1',
  `is_shown` tinyint(4) DEFAULT NULL,
  `created_at` bigint(20) DEFAULT NULL,
  `id_root` bigint(20) DEFAULT NULL COMMENT 'parent',
  `id_answer` bigint(20) DEFAULT NULL COMMENT 'expand',
  `id_highlight` bigint(20) DEFAULT NULL COMMENT 'highlight',
  `tbl` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'tale',
  `bind_tbl` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bind_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
