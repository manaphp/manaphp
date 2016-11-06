/*
SQLyog Ultimate v11.33 (32 bit)
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

DROP TABLE IF EXISTS `admin`;

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_name` char(16) NOT NULL,
  `salt` char(10) CHARACTER SET ascii NOT NULL,
  `password` char(32) CHARACTER SET ascii NOT NULL,
  `created_time` int(11) NOT NULL,
  `updated_time` int(11) NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `admin_name` (`admin_name`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

/*Data for the table `admin` */

insert  into `admin`(`admin_id`,`admin_name`,`salt`,`password`,`created_time`,`updated_time`) values (1,'admin','npNBNQsE','ab23b9b1c5fb91288753d70081fb72f2',0,0);

/*Table structure for table `admin_detail` */

DROP TABLE IF EXISTS `admin_detail`;

CREATE TABLE `admin_detail` (
  `admin_id` int(11) NOT NULL,
  `admin_name` char(16) NOT NULL,
  `email` varchar(128) NOT NULL,
  `created_time` int(11) NOT NULL,
  `updated_time` int(11) NOT NULL,
  PRIMARY KEY (`admin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `admin_detail` */

insert  into `admin_detail`(`admin_id`,`admin_name`,`email`,`created_time`,`updated_time`) values (1,'admin','mark@manaphp.com',0,0);

/*Table structure for table `admin_login` */

DROP TABLE IF EXISTS `admin_login`;

CREATE TABLE `admin_login` (
  `login_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `ip` char(15) NOT NULL,
  `udid` char(32) CHARACTER SET ascii NOT NULL,
  `user_agent` char(128) NOT NULL,
  `login_time` int(11) NOT NULL,
  `logout_time` int(11) NOT NULL,
  PRIMARY KEY (`login_id`,`admin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `admin_login` */

/*Table structure for table `rbac_permission` */

DROP TABLE IF EXISTS `rbac_permission`;

CREATE TABLE `rbac_permission` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_type` tinyint(4) NOT NULL,
  `module` char(32) CHARACTER SET ascii NOT NULL,
  `controller` char(32) CHARACTER SET ascii NOT NULL,
  `action` char(32) CHARACTER SET ascii NOT NULL,
  `description` char(128) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `module_controller_action` (`module`,`controller`,`action`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `rbac_permission` */

/*Table structure for table `rbac_role` */

DROP TABLE IF EXISTS `rbac_role`;

CREATE TABLE `rbac_role` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` char(64) NOT NULL,
  `description` char(128) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `rbac_role` */

/*Table structure for table `rbac_role_permission` */

DROP TABLE IF EXISTS `rbac_role_permission`;

CREATE TABLE `rbac_role_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_id_role_id` (`permission_id`,`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `rbac_role_permission` */

/*Table structure for table `rbac_user_role` */

DROP TABLE IF EXISTS `rbac_user_role`;

CREATE TABLE `rbac_user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_role_id` (`user_id`,`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Data for the table `rbac_user_role` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
