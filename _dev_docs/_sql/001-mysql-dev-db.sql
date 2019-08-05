/*
SQLyog Ultimate v12.14 (64 bit)
MySQL - 5.5.53-log : Database - alina
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`alina` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `alina`;

/*Table structure for table `XXX` */

DROP TABLE IF EXISTS `XXX`;

CREATE TABLE `XXX` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `integer` int(11) DEFAULT NULL,
  `varchar` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `XXX` */

/*Table structure for table `answer` */

DROP TABLE IF EXISTS `answer`;

CREATE TABLE `answer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` varchar(500) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8;

/*Data for the table `answer` */

insert  into `answer`(`id`,`text`,`price`) values 
(1,'1234',1234),
(2,'1234',0),
(3,'1234',0),
(4,'1234',0),
(5,'1234',0),
(6,'1234',0),
(7,'1234',0),
(8,'1234',0),
(9,'4321',4321),
(10,'11111111',1111111111),
(11,'1234',1234),
(17,'The best',4),
(19,'Good',3),
(20,'1234',1234),
(21,'1234',11234),
(22,'4321',4321),
(23,'4321',4321),
(24,'5',5),
(25,'4321',4321),
(34,'123412341234',1234),
(35,'Norm',2),
(36,'Bad',1),
(37,'Yes',1),
(38,'No',0),
(39,'I am',1),
(40,'A am not',0),
(41,'Yes',0),
(42,'I do',1),
(43,'Yes I do',3),
(44,'Never',0),
(45,'Sometime',2),
(46,'Always',3);

/*Table structure for table `article` */

DROP TABLE IF EXISTS `article`;

CREATE TABLE `article` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(500) DEFAULT NULL,
  `teaser` text,
  `text` text,
  `publish_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

/*Data for the table `article` */

insert  into `article`(`id`,`title`,`teaser`,`text`,`publish_at`) values 
(1,'<h2><strong style=\"color: rgb(240, 102, 102);\">Ratatata</strong></h2>','<p><span style=\"background-color: rgb(194, 133, 255);\">Teaser Teaser Teaser Teaser T</span>easer Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser T<span style=\"background-color: rgb(0, 138, 0);\">easer Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teas</span>er Teaser <span style=\"background-color: rgb(102, 185, 102);\">Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Tea</span>ser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser Teaser</p><p><br></p><p>Lalala</p><p>LalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalalaLalala</p>','dd','2018-03-14 13:02:31'),
(2,'Название 2','Привет мир','<h2><a href=\"https://codecraft.tv/courses/angular/custom-directives/inputs-and-configuration/#_configuration\" target=\"_blank\" style=\"color: rgb(0, 0, 0);\">Configuration</a></h2><p>In&nbsp;the last lecture we finished off our&nbsp;<code style=\"background-color: rgb(240, 242, 241); color: rgb(244, 100, 95);\">ccCardHover</code>&nbsp;directive. But its not very&nbsp;re-usable; we now want to&nbsp;be able to&nbsp;<em>configure</em>&nbsp;it so that it can be used in&nbsp;other situations.</p><p><br></p><p>One such configuration parameter is the&nbsp;query selector for&nbsp;the elemenent we want to&nbsp;hide or&nbsp;show, currently it’s hard coded to&nbsp;<code style=\"background-color: rgb(240, 242, 241); color: rgb(244, 100, 95);\">.card-text</code>, like so:</p><p><br></p><pre class=\"ql-syntax\" spellcheck=\"false\">Copy\nlet part = this.el.nativeElement.querySelector(\'.card-text\');\n</pre><p>The&nbsp;first thing to&nbsp;do is move the&nbsp;query selector to&nbsp;a&nbsp;<em>property</em>&nbsp;of&nbsp;our directive, but to&nbsp;future-proof ourselves i’m going to&nbsp;set it to&nbsp;a property of&nbsp;an&nbsp;<em>object</em>, like so:</p><p><br></p><pre class=\"ql-syntax\" spellcheck=\"false\">Copy\nconfig: Object = {\n  querySelector: \'.card-text\'\n}\n</pre><p>This way if we wanted to&nbsp;add further config params in&nbsp;the future we can just add them as&nbsp;properties to&nbsp;our config object.</p><p><br></p><p>Next up lets use this config object instead of&nbsp;our hard coded selector.</p><p><br></p><pre class=\"ql-syntax\" spellcheck=\"false\">Copy\nlet part = this.el.nat\n</pre><p><br></p>','2018-03-12 13:02:31'),
(3,'Заголовок 3','Hello World!!!','Hello World!!!','2018-03-14 13:02:36'),
(4,'asdf','asdf','asdf','2018-03-14 13:02:31'),
(5,'qwewr','qwerqwer','qwerqwerqwer','2018-03-14 13:02:31'),
(6,'asdf','<h1><em style=\"background-color: rgb(230, 0, 0);\">самук  кемкем е км е екемекм у мукм укемук ем</em></h1>','asdf','2018-03-14 13:02:31'),
(7,'йй','йййй','йййййй','0000-00-00 00:00:00'),
(8,'йй','йййй','йййййй','0000-00-00 00:00:00'),
(9,'fff','fff fff ','fff fff fff fff fff ','0000-00-00 00:00:00');

/*Table structure for table `attr` */

DROP TABLE IF EXISTS `attr`;

CREATE TABLE `attr` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name_sys` varchar(100) DEFAULT NULL COMMENT 'UNIQUE',
  `name_human` varchar(100) DEFAULT NULL,
  `val_table` varchar(100) DEFAULT 'value_varchar_500',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

/*Data for the table `attr` */

insert  into `attr`(`id`,`name_sys`,`name_human`,`val_table`) values 
(1,'AttrSysName','Attr human name','value_varchar_500'),
(2,'temperature','temperature','value_decimal_10_4'),
(5,'age','Age','value_int_11'),
(6,'description','Description','value_varchar_500');

/*Table structure for table `ea` */

DROP TABLE IF EXISTS `ea`;

CREATE TABLE `ea` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `attr_id` bigint(20) DEFAULT NULL COMMENT 'points to attr table',
  `ent_id` bigint(20) DEFAULT NULL,
  `ent_table` varbinary(100) DEFAULT NULL,
  `quantity` bigint(20) DEFAULT NULL,
  `order` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

/*Data for the table `ea` */

insert  into `ea`(`id`,`attr_id`,`ent_id`,`ent_table`,`quantity`,`order`) values 
(1,2,1,'product',100,1),
(2,3,1,'product',100,1),
(3,4,1,'product',100,1),
(4,5,1,'product',100,1),
(5,6,1,'product',3,1);

/*Table structure for table `eav` */

DROP TABLE IF EXISTS `eav`;

CREATE TABLE `eav` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ea_id` bigint(20) DEFAULT NULL,
  `val_id` bigint(20) DEFAULT NULL,
  `val_table` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8;

/*Data for the table `eav` */

insert  into `eav`(`id`,`ea_id`,`val_id`,`val_table`) values 
(86,1,85,'value_temperature_celsius'),
(87,1,86,'value_temperature_celsius'),
(88,1,87,'value_temperature_celsius'),
(89,1,88,'value_temperature_celsius');

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

/*Table structure for table `ent` */

DROP TABLE IF EXISTS `ent`;

CREATE TABLE `ent` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ent` */

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

/*Table structure for table `hero` */

DROP TABLE IF EXISTS `hero`;

CREATE TABLE `hero` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `afield` varchar(255) DEFAULT 'lalala',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

/*Data for the table `hero` */

insert  into `hero`(`id`,`name`,`afield`) values 
(1,'Вася 1234','lalala'),
(3,'Васенька 444','lalala'),
(4,'Васёнок 444','lalala'),
(5,'Гена','lalala'),
(6,'Геннадий','lalala'),
(7,'Генарёк','lalala'),
(16,'Привет 1234','lalala'),
(17,'Гер Захер Мазох-Окунишников Тра-та-та-та','lalala'),
(18,'dededede','lalala');

/*Table structure for table `operation` */

DROP TABLE IF EXISTS `operation`;

CREATE TABLE `operation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `operation` */

/*Table structure for table `operation_role` */

DROP TABLE IF EXISTS `operation_role`;

CREATE TABLE `operation_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operation_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_operation_role` (`operation_id`,`role_id`),
  KEY `fk_operation_role_role` (`role_id`),
  CONSTRAINT `fk_operation_role_operation` FOREIGN KEY (`operation_id`) REFERENCES `operation` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_operation_role_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `operation_role` */

/*Table structure for table `product` */

DROP TABLE IF EXISTS `product`;

CREATE TABLE `product` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

/*Data for the table `product` */

insert  into `product`(`id`,`name`) values 
(1,'Product Name');

/*Table structure for table `prototype` */

DROP TABLE IF EXISTS `prototype`;

CREATE TABLE `prototype` (
  `id` bigint(20) DEFAULT NULL,
  `created_at` bigint(20) DEFAULT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` bigint(20) DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  `lock` tinyint(4) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `prototype` */

/*Table structure for table `question` */

DROP TABLE IF EXISTS `question`;

CREATE TABLE `question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` varchar(300) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8;

/*Data for the table `question` */

insert  into `question`(`id`,`text`,`weight`) values 
(1,'How R U ? ',1),
(3,'1234',NULL),
(4,'ddfsw',NULL),
(5,'ddfsw',NULL),
(6,'ddfsw',NULL),
(7,'ddfsw',NULL),
(8,'ddfsw',NULL),
(9,'ddfsw',NULL),
(10,'1234',NULL),
(11,'1234',NULL),
(12,'1234',NULL),
(13,'qwerty123qwerty',1432678264),
(14,'qwerty123qwerty',1432678264),
(15,'qwerty123qwerty',1432678264),
(16,'qwerty123qwerty',1432678264),
(17,'qwerty123qwerty',1432678264),
(18,'qwerty123qwerty',1432678264),
(27,'21',1432703736),
(28,'2',1432703736),
(29,'3',1432703736),
(30,'саумаумкепмкемекумкему',1432705011),
(32,'1',1),
(33,'саумаумкепмкемекумкему',1432705011),
(41,'Do you?',3),
(42,'Did you?',44),
(43,'Want you?',1),
(44,'Are you?',2),
(45,'Do you dig deep?',1),
(46,'Do you like Earth?',2);

/*Table structure for table `question_answer` */

DROP TABLE IF EXISTS `question_answer`;

CREATE TABLE `question_answer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) DEFAULT NULL,
  `answer_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_question_answer_to_question` (`question_id`),
  KEY `fk_question_answer_to_answer` (`answer_id`),
  CONSTRAINT `fk_question_answer_to_answer` FOREIGN KEY (`answer_id`) REFERENCES `answer` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_question_answer_to_question` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8;

/*Data for the table `question_answer` */

insert  into `question_answer`(`id`,`question_id`,`answer_id`) values 
(1,32,1),
(2,32,2),
(3,32,3),
(4,32,4),
(5,32,5),
(6,32,6),
(7,32,7),
(8,32,8),
(9,30,9),
(10,30,10),
(11,30,11),
(17,1,17),
(19,1,19),
(34,33,34),
(35,1,35),
(36,1,36),
(37,43,37),
(38,43,38),
(39,44,39),
(40,44,40),
(41,41,41),
(42,41,42),
(43,41,43),
(44,42,44),
(45,42,45),
(46,42,46);

/*Table structure for table `role` */

DROP TABLE IF EXISTS `role`;

CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `active` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

/*Data for the table `role` */

insert  into `role`(`id`,`name`,`description`,`active`) values 
(1,'admin','Site administrator',1),
(2,'registered','Just registered',1),
(3,'moderator','Admin\'s friend',0);

/*Table structure for table `tag` */

DROP TABLE IF EXISTS `tag`;

CREATE TABLE `tag` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

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
  `tag_id` bigint(20) DEFAULT NULL,
  `entity_id` bigint(20) DEFAULT NULL,
  `entity_table` varbinary(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

/*Data for the table `tag_to_entity` */

insert  into `tag_to_entity`(`id`,`tag_id`,`entity_id`,`entity_table`) values 
(1,3,1,'user'),
(2,5,2,'user'),
(3,4,2,'user'),
(4,5,3,'user'),
(5,1,1,'user'),
(6,2,1,'user'),
(7,1,5,'user');

/*Table structure for table `task` */

DROP TABLE IF EXISTS `task`;

CREATE TABLE `task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `task` */

/*Table structure for table `task_operation` */

DROP TABLE IF EXISTS `task_operation`;

CREATE TABLE `task_operation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `operation_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_task_operation` (`task_id`,`operation_id`),
  KEY `task_operation_operation` (`operation_id`),
  CONSTRAINT `task_operation_operation` FOREIGN KEY (`operation_id`) REFERENCES `operation` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `task_operation_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `task_operation` */

/*Table structure for table `task_role` */

DROP TABLE IF EXISTS `task_role`;

CREATE TABLE `task_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_task_role` (`task_id`,`role_id`),
  KEY `fk_task_role_role` (`role_id`),
  CONSTRAINT `fk_task_role_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_task_role_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `task_role` */

/*Table structure for table `test` */

DROP TABLE IF EXISTS `test`;

CREATE TABLE `test` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(300) DEFAULT NULL,
  `description` longtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

/*Data for the table `test` */

insert  into `test`(`id`,`title`,`description`) values 
(3,'Road Rules','For beginners'),
(4,'How to dig','Professional test system');

/*Table structure for table `test_question` */

DROP TABLE IF EXISTS `test_question`;

CREATE TABLE `test_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_test_question_to_test` (`test_id`),
  KEY `fk_test_question_to_question` (`question_id`),
  CONSTRAINT `fk_test_question_to_question` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_question_to_test` FOREIGN KEY (`test_id`) REFERENCES `test` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8;

/*Data for the table `test_question` */

insert  into `test_question`(`id`,`test_id`,`question_id`) values 
(1,3,1),
(41,3,41),
(42,3,42),
(43,3,43),
(44,3,44),
(45,4,45),
(46,4,46);

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
  `firstname` varchar(150) DEFAULT NULL,
  `lastname` varchar(150) DEFAULT NULL,
  `active` tinyint(4) DEFAULT NULL,
  `verified` tinyint(4) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `lastenter` int(11) DEFAULT NULL,
  `picture` varchar(300) DEFAULT NULL,
  `timezone` bigint(20) DEFAULT NULL,
  `password` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

/*Data for the table `user` */

insert  into `user`(`id`,`mail`,`firstname`,`lastname`,`active`,`verified`,`created`,`lastenter`,`picture`,`timezone`,`password`) values 
(1,'my123@mail.ru','Администратор','Алинский',0,0,0,0,'',1,'c4ca4238a0b923820dcc509a6f75849b'),
(2,'air_star_h@mail.ru','Иван','Подзалупный',NULL,NULL,NULL,NULL,NULL,2,'c4ca4238a0b923820dcc509a6f75849b'),
(3,'vsevolod.azovsky@gmail.com','Третий','Фамилия3',NULL,NULL,NULL,NULL,NULL,3,'718b6dd54c8d1d3ad19eb99cb12f13e2'),
(4,'_air_star_h@mail.ru','Четвертый 444','Фамилия4',0,0,0,0,'',4,'3dbe00a167653a1aaee01d93e77e730e'),
(5,'vsevolod.azovskiy@dataart.com','Пятый','Фамилия5',NULL,NULL,NULL,NULL,NULL,5,'c81e728d9d4c2f636f067f89cc14862c'),
(6,'vsevolod.azovskiy@dataart.com','Шастой','Фамилия6',NULL,NULL,NULL,NULL,NULL,1,'DELETEME'),
(7,'air_star_h1234@mail.ru','йцук','йцук',0,1,0,0,'',0,''),
(8,'air_star_h12341@mail.ru','йцук','йцук',0,1,0,0,'',0,''),
(9,'my1234@mail.ru','','',0,0,0,0,'',0,''),
(10,'my123412@mail.ru','','',0,0,0,0,'',0,'');

/*Table structure for table `user_answer` */

DROP TABLE IF EXISTS `user_answer`;

CREATE TABLE `user_answer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `test_id` int(11) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `answer_id` int(11) DEFAULT NULL,
  `pass_id` varchar(50) DEFAULT '30',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

/*Data for the table `user_answer` */

insert  into `user_answer`(`id`,`user_id`,`test_id`,`question_id`,`answer_id`,`pass_id`) values 
(1,1,3,1,17,'pass_1432791694'),
(2,1,3,43,38,'pass_1432791694'),
(3,1,3,44,39,'pass_1432791694'),
(4,1,3,41,43,'pass_1432791694'),
(5,1,3,42,45,'pass_1432791694'),
(6,1,3,1,19,'pass_1432803054'),
(7,1,3,43,37,'pass_1432803054'),
(8,1,3,44,40,'pass_1432803054'),
(9,1,3,41,41,'pass_1432803054'),
(10,1,3,42,46,'pass_1432803054');

/*Table structure for table `user_role` */

DROP TABLE IF EXISTS `user_role`;

CREATE TABLE `user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_role_user` (`user_id`),
  KEY `fk_user_role_role` (`role_id`),
  CONSTRAINT `fk_user_role_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_role_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

/*Data for the table `user_role` */

insert  into `user_role`(`id`,`user_id`,`role_id`) values 
(1,1,1),
(2,1,2),
(3,2,2),
(4,3,3),
(5,4,3);

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

/*Table structure for table `value_decimal_10_4` */

DROP TABLE IF EXISTS `value_decimal_10_4`;

CREATE TABLE `value_decimal_10_4` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ea_id` bigint(20) DEFAULT NULL,
  `val` decimal(14,4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

/*Data for the table `value_decimal_10_4` */

insert  into `value_decimal_10_4`(`id`,`ea_id`,`val`) values 
(5,1,'1.0000'),
(6,1,'1.0000'),
(7,1,'1.0000'),
(8,1,'1509601680.0000');

/*Table structure for table `value_int_11` */

DROP TABLE IF EXISTS `value_int_11`;

CREATE TABLE `value_int_11` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ea_id` bigint(20) DEFAULT NULL,
  `val` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

/*Data for the table `value_int_11` */

insert  into `value_int_11`(`id`,`ea_id`,`val`) values 
(5,4,5),
(6,4,10),
(7,4,15),
(8,4,-33),
(9,4,1509601956);

/*Table structure for table `value_temperature_celsius` */

DROP TABLE IF EXISTS `value_temperature_celsius`;

CREATE TABLE `value_temperature_celsius` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `val` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `value_temperature_celsius` */

/*Table structure for table `value_temperature_fahrenheit` */

DROP TABLE IF EXISTS `value_temperature_fahrenheit`;

CREATE TABLE `value_temperature_fahrenheit` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `val` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `value_temperature_fahrenheit` */

/*Table structure for table `value_varchar_500` */

DROP TABLE IF EXISTS `value_varchar_500`;

CREATE TABLE `value_varchar_500` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ea_id` bigint(20) DEFAULT NULL,
  `val` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

/*Data for the table `value_varchar_500` */

insert  into `value_varchar_500`(`id`,`ea_id`,`val`) values 
(1,NULL,'ValueOfAttr');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
