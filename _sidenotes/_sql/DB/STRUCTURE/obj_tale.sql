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
/*Table structure for table `tale` */

DROP TABLE IF EXISTS `tale`;

CREATE TABLE `tale` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `root_tale_id` bigint(20) DEFAULT NULL,
  `answer_to_tale_id` bigint(20) DEFAULT NULL,
  `level` tinyint(1) DEFAULT '0',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'POST' COMMENT 'POST, COMMENT, POSTCOMMENT',
  `owner_id` bigint(20) DEFAULT NULL,
  `header` text COLLATE utf8mb4_unicode_ci,
  `body` text COLLATE utf8mb4_unicode_ci,
  `body_txt` text COLLATE utf8mb4_unicode_ci,
  `created_at` bigint(20) DEFAULT NULL,
  `modified_at` bigint(20) DEFAULT NULL,
  `publish_at` bigint(20) DEFAULT NULL,
  `is_submitted` tinyint(1) DEFAULT '0',
  `lang` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'RU',
  PRIMARY KEY (`id`),
  KEY `IND_PUBLISH_AT` (`publish_at`)
) ENGINE=InnoDB AUTO_INCREMENT=425 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
