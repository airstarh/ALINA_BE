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
/*Table structure for table `watch_visit` */

DROP TABLE IF EXISTS `watch_visit`;

CREATE TABLE `watch_visit` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip` char(46) DEFAULT NULL,
  `browser_enc` varchar(256) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `query_string` text,
  `visited_at` bigint(8) DEFAULT NULL,
  `method` char(20) DEFAULT NULL,
  `data` text,
  `controller` char(50) DEFAULT NULL,
  `action` char(50) DEFAULT NULL,
  `ajax` tinyint(1) DEFAULT '0',
  `suspicious` tinyint(1) DEFAULT '0',
  `fingerprint` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
