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

/*Table structure for table `manaphp_log` */

CREATE TABLE `manaphp_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(32) NOT NULL,
  `level` char(8) CHARACTER SET ascii NOT NULL,
  `category` varchar(64) NOT NULL,
  `location` varchar(128) NOT NULL,
  `caller` varchar(128) NOT NULL,
  `message` varchar(4000) NOT NULL,
  `client_ip` char(15) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `created_time` (`created_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `menu_group` */

CREATE TABLE `menu_group` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(64) NOT NULL,
  `icon` varchar(32) CHARACTER SET ascii NOT NULL,
  `display_order` tinyint(4) NOT NULL,
  `creator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `updator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `created_time` int(11) NOT NULL,
  `updated_time` int(11) NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
