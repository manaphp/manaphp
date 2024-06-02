/*
SQLyog Ultimate v13.1.1 (64 bit)
MySQL - 5.7.27 : Database - manaphp
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

-- DROP TABLE IF EXISTS `admin`;

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `type` tinyint(4) NOT NULL DEFAULT 1,
  `tag` int(11) NOT NULL DEFAULT 0,
  `email` varchar(64) CHARACTER SET ascii NOT NULL,
  `salt` char(16) CHARACTER SET ascii NOT NULL,
  `password` char(32) CHARACTER SET ascii NOT NULL,
  `white_ip` varchar(64) NOT NULL DEFAULT '*',
  `login_ip` char(15) CHARACTER SET ascii NOT NULL DEFAULT '',
  `login_time` int(11) NOT NULL DEFAULT 0,
  `session_id` varchar(64) NOT NULL DEFAULT '',
  `creator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `updator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `created_time` int(11) NOT NULL,
  `updated_time` int(11) NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `admin_name` (`admin_name`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*Data for the table `admin` */

insert  into `admin`(`admin_id`,`admin_name`,`status`,`type`,`tag`,`email`,`salt`,`password`,`login_ip`,`login_time`,`session_id`,`creator_name`,`updator_name`,`created_time`,`updated_time`) values 
(1,'admin',1,1,0,'admin@qq.com','c5c05ae460905af4','ed2a1a4f62f1717ff30b2197a283cc22','192.168.1.47',0,'gz7ne3p9iwj15bm0sqehljpi0hscp0g2','','admin',0,0);

/*Table structure for table `admin_action_log` */

-- DROP TABLE IF EXISTS `admin_action_log`;

CREATE TABLE `admin_action_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `method` varchar(15) CHARACTER SET ascii NOT NULL,
  `handler` varchar(64) CHARACTER SET ascii NOT NULL,
  `tag` int(11) NOT NULL DEFAULT 0,
  `url` varchar(128) NOT NULL,
  `data` text NOT NULL,
  `client_ip` char(15) CHARACTER SET ascii NOT NULL,
  `client_udid` char(16) CHARACTER SET ascii NOT NULL DEFAULT '',
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`admin_id`),
  KEY `handler` (`handler`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `admin_action_log` */

/*Table structure for table `admin_login_log` */

-- DROP TABLE IF EXISTS `admin_login_log`;

CREATE TABLE `admin_login_log` (
  `login_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `admin_name` char(16) CHARACTER SET ascii NOT NULL,
  `client_ip` char(15) CHARACTER SET ascii NOT NULL,
  `client_udid` char(16) CHARACTER SET ascii NOT NULL,
  `user_agent` char(255) CHARACTER SET ascii NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`login_id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `admin_login_log` */

/*Table structure for table `bos_bucket` */

-- DROP TABLE IF EXISTS `bos_bucket`;

CREATE TABLE `bos_bucket` (
  `bucket_id` int(11) NOT NULL AUTO_INCREMENT,
  `bucket_name` varchar(64) CHARACTER SET ascii NOT NULL,
  `base_url` varchar(128) CHARACTER SET ascii NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`bucket_id`),
  UNIQUE KEY `bucket_name` (`bucket_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*Data for the table `bos_bucket` */

insert  into `bos_bucket`(`bucket_id`,`bucket_name`,`base_url`,`created_time`) values 
(1,'www','http://cdn.manaphp.d/',0);

/*Table structure for table `bos_object` */

-- DROP TABLE IF EXISTS `bos_object`;

CREATE TABLE `bos_object` (
  `object_id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(256) NOT NULL,
  `bucket_id` int(11) NOT NULL,
  `bucket_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `original_name` varchar(128) NOT NULL,
  `mime_type` varchar(64) CHARACTER SET ascii NOT NULL,
  `extension` varchar(16) CHARACTER SET ascii NOT NULL,
  `width` int(11) NOT NULL DEFAULT 0,
  `height` int(11) NOT NULL DEFAULT 0,
  `size` int(11) NOT NULL,
  `md5` char(32) CHARACTER SET ascii NOT NULL,
  `ip` char(15) CHARACTER SET ascii NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`object_id`),
  UNIQUE KEY `bucket_name` (`bucket_name`,`key`),
  KEY `md5` (`md5`),
  KEY `extension` (`extension`),
  KEY `key` (`key`),
  KEY `bucket_id` (`bucket_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*Table structure for table `dotenv_log` */

-- DROP TABLE IF EXISTS `dotenv_log`;

CREATE TABLE `dotenv_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_id` varchar(32) CHARACTER SET ascii NOT NULL,
  `env` text NOT NULL,
  `created_date` int(11) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

/*Table structure for table `menu_group` */

-- DROP TABLE IF EXISTS `menu_group`;

CREATE TABLE `menu_group` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(64) NOT NULL,
  `icon` varchar(32) CHARACTER SET ascii NOT NULL,
  `display_order` tinyint(4) NOT NULL,
  `creator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `updator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `created_time` int(11) NOT NULL,
  `updated_time` int(11) NOT NULL,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `group_name` (`group_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*Data for the table `menu_group` */

insert  into `menu_group`(`group_id`,`group_name`,`icon`,`display_order`,`creator_name`,`updator_name`,`created_time`,`updated_time`) values 
(3,'权限管理','el-icon-caret-right',0,'admin','admin',0,0),
(5,'系统管理','el-icon-caret-right',0,'admin','admin',0,0),
(4,'日志管理','el-icon-caret-right',0,'admin','admin',0,0),
(2,'菜单管理','el-icon-caret-right',0,'admin','admin',0,0),
(1,'个人中心','el-icon-caret-right',1,'admin','admin',0,0),
(6,'存储管理','el-icon-caret-right',0,'admin','admin',0,0);

/*Table structure for table `menu_item` */

-- DROP TABLE IF EXISTS `menu_item`;

CREATE TABLE `menu_item` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(64) NOT NULL,
  `group_id` tinyint(4) NOT NULL,
  `display_order` tinyint(4) NOT NULL,
  `url` varchar(128) NOT NULL,
  `icon` varchar(32) CHARACTER SET ascii NOT NULL,
  `creator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `updator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `created_time` int(11) NOT NULL,
  `updated_time` int(11) NOT NULL,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `group_id` (`group_id`,`url`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*Data for the table `menu_item` */

insert  into `menu_item`(`item_id`,`item_name`,`group_id`,`display_order`,`url`,`icon`,`creator_name`,`updator_name`,`created_time`,`updated_time`) values 
(8,'角色权限',3,0,'/rbac/role-permission','el-icon-arrow-right','admin','admin',0,0),
(4,'用户',3,3,'/rbac/admin','el-icon-arrow-right','admin','admin',0,0),
(9,'系统信息',5,0,'/system/information','el-icon-arrow-right','admin','',0,0),
(5,'角色',3,0,'/rbac/role','el-icon-arrow-right','admin','admin',0,0),
(6,'权限',3,0,'/rbac/permission','el-icon-arrow-right','admin','admin',0,0),
(7,'用户角色',3,0,'/rbac/admin-role','el-icon-arrow-right','admin','admin',0,0),
(10,'登录日志',4,0,'/admin/login-log','el-icon-arrow-right','admin','admin',0,0),
(11,'菜单组',2,0,'/menu/group','el-icon-arrow-right','admin','',0,0),
(12,'菜单项',2,0,'/menu/item','el-icon-arrow-right','admin','admin',0,0),
(13,'动作日志',4,0,'/admin/action-log','el-icon-arrow-right','mark','mark',0,0),
(1,'最近登录',1,0,'/admin/login-log/latest','el-icon-arrow-right','admin','admin',0,0),
(2,'最近操作',1,0,'/admin/action-log/latest','el-icon-arrow-right','admin','admin',0,0),
(3,'修改密码',1,0,'/admin/password/change','el-icon-arrow-right','admin','admin',0,0),
(15,'存储对象管理',6,0,'/bos/object','el-icon-arrow-right','admin','admin',0,0),
(14,'存储桶管理',6,0,'/bos/bucket','el-icon-arrow-right','admin','admin',0,0);

/*Table structure for table `metadata_constant` */

-- DROP TABLE IF EXISTS `metadata_constant`;

CREATE TABLE `metadata_constant` (
  `id` varchar(32) NOT NULL,
  `constants` tinytext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*Data for the table `metadata_constant` */

/*Table structure for table `rbac_admin_role` */

-- DROP TABLE IF EXISTS `rbac_admin_role`;

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*Data for the table `rbac_admin_role` */

/*Table structure for table `rbac_permission` */

-- DROP TABLE IF EXISTS `rbac_permission`;

CREATE TABLE `rbac_permission` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `handler` varchar(64) CHARACTER SET ascii NOT NULL,
  `authorize` varchar(64) CHARACTER SET ascii NOT NULL,
  `grantable` tinyint(4) NOT NULL DEFAULT 1,
  `display_name` varchar(128) NOT NULL,
  `created_time` int(11) NOT NULL,
  `updated_time` int(11) NOT NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `handler` (`handler`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*Data for the table `rbac_permission` */

/*Table structure for table `rbac_role` */

-- DROP TABLE IF EXISTS `rbac_role`;

CREATE TABLE `rbac_role` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(64) NOT NULL,
  `display_name` varchar(64) NOT NULL,
  `builtin` tinyint(4) NOT NULL DEFAULT 1,
  `enabled` tinyint(4) NOT NULL DEFAULT 1,
  `permissions` text CHARACTER SET ascii NOT NULL,
  `creator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `updator_name` varchar(32) CHARACTER SET ascii NOT NULL,
  `created_time` int(11) NOT NULL,
  `updated_time` int(11) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`),
  UNIQUE KEY `display_name` (`display_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*Data for the table `rbac_role` */

insert  into `rbac_role`(`role_id`,`role_name`,`display_name`,`builtin`,`enabled`,`permissions`,`creator_name`,`updator_name`,`created_time`,`updated_time`) values
(1,'admin','超级管理员',1,1,'','admin','admin',0,0),
(2,'rbac','权限管理员',0,1,'','admin','admin',0,0),
(3,'menu','菜单管理员',0,1,'','admin','admin',0,0);

/*Table structure for table `rbac_role_permission` */

-- DROP TABLE IF EXISTS `rbac_role_permission`;

CREATE TABLE `rbac_role_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `creator_name` char(16) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_id_role_id` (`permission_id`,`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*Data for the table `rbac_role_permission` */

/*Table structure for table `test` */

-- DROP TABLE IF EXISTS `test`;

CREATE TABLE `test` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `balance` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*Data for the table `test` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
