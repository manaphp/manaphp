#ManaPHP\Cache\Adapter\Db\Model
CREATE TABLE `manaphp_cache` (
  `hash` char(32) CHARACTER SET ascii NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `expired_time` int(11) NOT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#ManaPHP\Store\Adapter\Db\Model
CREATE TABLE `manaphp_store` (
  `hash` CHAR(32) CHARACTER SET ASCII NOT NULL,
  `key` VARCHAR(255) NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

#ManaPHP\Http\Session\Adapter\Db\Model
CREATE TABLE `manaphp_session` (
  `session_id` char(32) CHARACTER SET ascii NOT NULL,
  `data` text NOT NULL,
  `expired_time` int(11) NOT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#ManaPHP\Authorization\Rbac\Models\Role
CREATE TABLE `rbac_role` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` char(64) NOT NULL,
  `description` char(128) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#ManaPHP\Authorization\Rbac\Models\Permission
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

#ManaPHP\Authorization\Rbac\Models\RolePermission
CREATE TABLE `rbac_role_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_id_role_id` (`permission_id`,`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 
#ManaPHP\Authorization\Rbac\Models\UserRole
CREATE TABLE `rbac_user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_role_id` (`user_id`,`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#ManaPHP\Meter\Linear\Model
CREATE TABLE `manaphp_linear_meter` (
  `hash` char(32) CHARACTER SET ascii NOT NULL,
  `type` char(16) NOT NULL,
  `id` char(64) NOT NULL,
  `count` int(11) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#ManaPHP\Meter\Round\Model
CREATE TABLE `manaphp_round_meter` (
  `hash` char(32) CHARACTER SET ascii NOT NULL,
  `type` char(16) NOT NULL,
  `id` char(32) NOT NULL,
  `begin_time` int(11) NOT NULL,
  `duration` int(11) NOT NULL,
  `count` int(11) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#ManaPHP\Message\Queue\Adapter\Db\Model
CREATE TABLE `manaphp_message_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deleted` tinyint(4) NOT NULL,
  `topic` char(16) NOT NULL,
  `body` varchar(4000) NOT NULL,
  `priority` tinyint(4) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;