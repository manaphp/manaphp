/*
SQLyog Ultimate v12.5.0 (64 bit)
MySQL - 5.7.9 : Database - manaphp
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`manaphp` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `manaphp`;

/*Table structure for table `admin` */

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `email` varchar(64) CHARACTER SET ascii NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `salt` char(16) CHARACTER SET ascii NOT NULL,
  `password` char(32) CHARACTER SET ascii NOT NULL,
  `login_ip` char(15) CHARACTER SET ascii NOT NULL DEFAULT '',
  `login_time` int(11) NOT NULL DEFAULT '0',
  `creator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `updator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `created_time` int(11) NOT NULL,
  `updated_time` int(11) NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `admin_name` (`admin_name`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
insert  into `admin`(`admin_id`,`admin_name`,`email`,`status`,`salt`,`password`,`login_ip`,`login_time`,`creator_name`,`updator_name`,`created_time`,`updated_time`) values (1,'admin','manaphp@qq.com',1,'caBdg7IdpL6BcZvk','10e7bd2d7e098255e7e2b9e07c3f038e','127.0.0.1',0,'','',0,0);

/*Table structure for table `rbac_admin_role` */

CREATE TABLE `rbac_admin_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `role_id` int(11) NOT NULL,
  `role_name` char(32) NOT NULL,
  `creator_name` char(16) CHARACTER SET ascii NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_role_id` (`admin_id`,`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `rbac_permission` */

CREATE TABLE `rbac_permission` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(64) CHARACTER SET ascii NOT NULL,
  `description` varchar(128) NOT NULL,
  `created_time` int(11) NOT NULL,
  `updated_time` int(11) NOT NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `path` (`path`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `rbac_role` */

CREATE TABLE `rbac_role` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(64) NOT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `permissions` text CHARACTER SET ascii NOT NULL,
  `creator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `updator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `created_time` int(11) NOT NULL,
  `updated_time` int(11) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `rbac_role_permission` */

CREATE TABLE `rbac_role_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `creator_name` char(16) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_id_role_id` (`permission_id`,`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
