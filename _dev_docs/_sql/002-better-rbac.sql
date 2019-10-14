/*
SQLyog Ultimate
MySQL - 5.6.37-log : Database - alina
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `XXX` */

DROP TABLE IF EXISTS `XXX`;

CREATE TABLE `XXX` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `integer` int(11) DEFAULT NULL,
  `varchar` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `XXX` */

/*Table structure for table `content` */

DROP TABLE IF EXISTS `content`;

CREATE TABLE `content` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) DEFAULT NULL,
  `content_type` enum('POST','COMMENT') DEFAULT 'POST',
  `title` varchar(500) DEFAULT NULL,
  `teaser` text,
  `text` text,
  `publish_at` int(11) DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `modified_at` int(11) DEFAULT '0',
  `modified_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;

/*Data for the table `content` */

insert  into `content`(`id`,`parent_id`,`content_type`,`title`,`teaser`,`text`,`publish_at`,`created_at`,`created_by`,`modified_at`,`modified_by`) values 
(1,NULL,'POST','<h2><strong style=\"color: rgb(240, 102, 102);\">Ratatata</strong></h2>','<p><span style=\"background-color: rgb(194, 133, 255);\">Teaser Teaser Teaser Teaser T</span>easer Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser T<span style=\"background-color: rgb(0, 138, 0);\">easer Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teas</span>er Teaser <span style=\"background-color: rgb(102, 185, 102);\">Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Tea</span>ser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser</p><p><br></p><p>Lalala</p><p>LalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalala</p>','dd',2147483647,NULL,NULL,0,NULL),
(2,NULL,'POST','Название 2','Привет мир','<h2><a href=\"https://codecraft.tv/courses/angular/custom-directives/inputs-and-configuration/#_configuration\" target=\"_blank\" style=\"color: rgb(0, 0, 0);\">Configuration</a></h2><p>In&nbsp;the last lecture we finished off our&nbsp;<code style=\"background-color: rgb(240, 242, 241); color: rgb(244, 100, 95);\">ccCardHover</code>&nbsp;directive. But its not very&nbsp;re-usable; we now want to&nbsp;be able to&nbsp;<em>configure</em>&nbsp;it so that it can be used in&nbsp;other situations.</p><p><br></p><p>One such configuration parameter is the&nbsp;query selector for&nbsp;the elemenent we want to&nbsp;hide or&nbsp;show, currently it’s hard coded to&nbsp;<code style=\"background-color: rgb(240, 242, 241); color: rgb(244, 100, 95);\">.card-text</code>, like so:</p><p><br></p><pre class=\"ql-syntax\" spellcheck=\"false\">Copy\nlet part = this.el.nativeElement.querySelector(\'.card-text\');\n</pre><p>The&nbsp;first thing to&nbsp;do is move the&nbsp;query selector to&nbsp;a&nbsp;<em>property</em>&nbsp;of&nbsp;our directive, but to&nbsp;future-proof ourselves i’m going to&nbsp;set it to&nbsp;a property of&nbsp;an&nbsp;<em>object</em>, like so:</p><p><br></p><pre class=\"ql-syntax\" spellcheck=\"false\">Copy\nconfig: Object = {\n  querySelector: \'.card-text\'\n}\n</pre><p>This way if we wanted to&nbsp;add further config params in&nbsp;the future we can just add them as&nbsp;properties to&nbsp;our config object.</p><p><br></p><p>Next up lets use this config object instead of&nbsp;our hard coded selector.</p><p><br></p><pre class=\"ql-syntax\" spellcheck=\"false\">Copy\nlet part = this.el.nat\n</pre><p><br></p>',2147483647,NULL,NULL,0,NULL),
(3,NULL,'POST','Заголовок 3','Hello World!!!','Hello World!!!',2147483647,NULL,NULL,0,NULL),
(4,NULL,'POST','asdf','asdf','asdf',2147483647,NULL,NULL,0,NULL),
(5,NULL,'POST','qwewr','qwerqwer','qwerqwerqwer',2147483647,NULL,NULL,0,NULL),
(6,NULL,'POST','asdf','<h1><em style=\"background-color: rgb(230, 0, 0);\">самук  кемкем е км е екемекм у мукм укемук ем</em></h1>','asdf',2147483647,NULL,NULL,0,NULL),
(7,NULL,'POST','йй','йййй','йййййй',0,NULL,NULL,0,NULL),
(8,NULL,'POST','йй','йййй','йййййй',0,NULL,NULL,0,NULL),
(9,NULL,'POST','fff','fff fff ','fff fff fff fff fff ',0,NULL,NULL,0,NULL);

/*Table structure for table `eg1` */

DROP TABLE IF EXISTS `eg1`;

CREATE TABLE `eg1` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `val` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8;

/*Data for the table `eg1` */

insert  into `eg1`(`id`,`val`) values 
(1,'DELETEME'),
(2,'DELETEME'),
(3,'DELETEME'),
(4,'DELETEME'),
(5,'DELETEME'),
(9,'DELETEME'),
(17,'DELETEME'),
(18,'DELETEME'),
(19,'DELETEME'),
(20,'DELETEME'),
(21,'DELETEME'),
(22,'DELETEME'),
(23,'DELETEME'),
(24,'DELETEME'),
(26,'DELETEME'),
(27,'DELETEME');

/*Table structure for table `eg2` */

DROP TABLE IF EXISTS `eg2`;

CREATE TABLE `eg2` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `val` varchar(100) DEFAULT NULL,
  `eg1_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;

/*Data for the table `eg2` */

insert  into `eg2`(`id`,`val`,`eg1_id`) values 
(17,'DELETEME',24),
(19,'DELETEME',26),
(20,'DELETEME',27);

/*Table structure for table `file` */

DROP TABLE IF EXISTS `file`;

CREATE TABLE `file` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `entity_id` bigint(20) DEFAULT NULL,
  `entity_table` varbinary(50) DEFAULT NULL,
  `name_fs` varchar(255) DEFAULT NULL,
  `name_human` varchar(255) DEFAULT NULL,
  `parent_id` bigint(20) DEFAULT NULL,
  `container` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

/*Data for the table `file` */

insert  into `file`(`id`,`entity_id`,`entity_table`,`name_fs`,`name_human`,`parent_id`,`container`) values 
(1,1,'user',NULL,NULL,NULL,NULL),
(2,2,'user',NULL,NULL,NULL,NULL),
(3,2,'user',NULL,NULL,NULL,NULL),
(4,3,'user',NULL,NULL,NULL,NULL),
(5,1,'user',NULL,NULL,NULL,NULL),
(6,1,'user',NULL,NULL,NULL,NULL),
(7,5,'user',NULL,NULL,NULL,NULL);

/*Table structure for table `rbac_permission` */

DROP TABLE IF EXISTS `rbac_permission`;

CREATE TABLE `rbac_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `rbac_permission` */

insert  into `rbac_permission`(`id`,`name`,`description`) values 
(1,'select',NULL),
(2,'insert',NULL),
(3,'update',NULL),
(4,'delete',''),
(5,'delete_totally',NULL);

/*Table structure for table `rbac_role` */

DROP TABLE IF EXISTS `rbac_role`;

CREATE TABLE `rbac_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `rbac_role` */

insert  into `rbac_role`(`id`,`name`,`description`) values 
(1,'admin','Site administrator'),
(4,'moderator','Site Moderator'),
(5,'servants','Other aite users');

/*Table structure for table `rbac_role_permission` */

DROP TABLE IF EXISTS `rbac_role_permission`;

CREATE TABLE `rbac_role_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_task_role` (`permission_id`,`role_id`),
  KEY `fk_task_role_role` (`role_id`),
  CONSTRAINT `fk_task_role_role` FOREIGN KEY (`role_id`) REFERENCES `rbac_role` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_task_role_task` FOREIGN KEY (`permission_id`) REFERENCES `rbac_permission` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `rbac_role_permission` */

insert  into `rbac_role_permission`(`id`,`role_id`,`permission_id`) values 
(2,1,1),
(8,4,1),
(4,1,2),
(9,4,2),
(5,1,3),
(3,4,3),
(6,1,4),
(11,4,4),
(7,1,5);

/*Table structure for table `rbac_user_role` */

DROP TABLE IF EXISTS `rbac_user_role`;

CREATE TABLE `rbac_user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_role_user` (`user_id`),
  KEY `fk_user_role_role` (`role_id`),
  CONSTRAINT `fk_user_role_role` FOREIGN KEY (`role_id`) REFERENCES `rbac_role` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_role_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `rbac_user_role` */

insert  into `rbac_user_role`(`id`,`user_id`,`role_id`) values 
(1,1,1),
(3,1,4),
(6,1,5);

/*Table structure for table `tag` */

DROP TABLE IF EXISTS `tag`;

CREATE TABLE `tag` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tag` */

insert  into `tag`(`id`,`name`) values 
(1,'One'),
(2,'Two'),
(3,'Three'),
(4,'Four'),
(5,'Five');

/*Table structure for table `tag_to_entity` */

DROP TABLE IF EXISTS `tag_to_entity`;

CREATE TABLE `tag_to_entity` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `entity_table` char(50) DEFAULT NULL,
  `entity_id` bigint(20) DEFAULT NULL,
  `tag_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*Data for the table `tag_to_entity` */

/*Table structure for table `timezone` */

DROP TABLE IF EXISTS `timezone`;

CREATE TABLE `timezone` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

/*Data for the table `timezone` */

insert  into `timezone`(`id`,`name`,`description`) values 
(1,'Первая Тайм Зона',NULL),
(2,'Вторая Тайм Зона',NULL),
(3,'The Third Тайм Зона',NULL),
(4,'4th Тайм Зона',NULL),
(5,'5th Тайм Зона',NULL);

/*Table structure for table `user` */

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mail` varbinary(300) NOT NULL,
  `firstname` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(4) DEFAULT NULL,
  `verified` tinyint(4) DEFAULT '0',
  `created` int(11) DEFAULT NULL,
  `lastenter` int(11) DEFAULT NULL,
  `picture` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timezone` bigint(20) DEFAULT NULL,
  `password` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `banned_till` int(11) DEFAULT '0',
  `ip` char(46) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'https://stackoverflow.com/a/166157/3142281',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `user` */

insert  into `user`(`id`,`mail`,`firstname`,`lastname`,`active`,`verified`,`created`,`lastenter`,`picture`,`timezone`,`password`,`banned_till`,`ip`) values 
(1,'my123@mail.ru','Vsevolod','Azovsky',1,1,0,0,'',1,'c4ca4238a0b923820dcc509a6f75849b',0,'ffff:ffff:ffff:ffff:ffff:ffff:ffff:eee'),
(2,'air_star_h@mail.ru','Иван','Подзалупный',NULL,NULL,NULL,NULL,NULL,2,'c4ca4238a0b923820dcc509a6f75849b',0,NULL),
(3,'vsevolod.azovsky@gmail.com','Третий','Фамилия3',NULL,NULL,NULL,NULL,NULL,3,'718b6dd54c8d1d3ad19eb99cb12f13e2',0,NULL),
(4,'_air_star_h@mail.ru','Четвертый 444','ASASASAS',0,0,0,0,'',4,'3dbe00a167653a1aaee01d93e77e730e',0,NULL),
(5,'vsevolod.azovskiy@dataart.com','Пятый','Фамилия5',NULL,NULL,NULL,NULL,NULL,5,'c81e728d9d4c2f636f067f89cc14862c',0,NULL),
(6,'vsevolod.azovskiy@dataart.com','Шастой ASASASAS','Фамилия6',0,0,0,0,'',1,'DELETEME',0,NULL),
(7,'air_star_h1234@mail.ru','йцук','йцук',0,1,0,0,'',0,'',0,NULL),
(8,'air_star_h12341@mail.ru','йцук','йцук',0,1,0,0,'',0,'',0,NULL),
(9,'my1234@mail.ru','','',0,0,0,0,'',0,'',0,NULL),
(10,'my123412@mail.ru','','',0,0,0,0,'',0,'',0,NULL);

/*Table structure for table `user_user` */

DROP TABLE IF EXISTS `user_user`;

CREATE TABLE `user_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `a_user_id` int(11) NOT NULL,
  `b_user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_bound` (`a_user_id`,`b_user_id`),
  KEY `fk_b_user` (`b_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `user_user` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
