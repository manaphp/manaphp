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

/*Table structure for table `admin_action_log` */

CREATE TABLE `admin_action_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `ip` char(15) CHARACTER SET ascii NOT NULL,
  `udid` char(16) CHARACTER SET ascii NOT NULL,
  `path` varchar(32) CHARACTER SET ascii NOT NULL,
  `method` varchar(15) CHARACTER SET ascii NOT NULL,
  `url` varchar(128) NOT NULL,
  `data` text NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
