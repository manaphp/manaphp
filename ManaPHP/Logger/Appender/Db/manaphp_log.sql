CREATE TABLE `manaphp_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(32) NOT NULL,
  `level` char(8) NOT NULL,
  `category` varchar(64) NOT NULL,
  `location` varchar(128) NOT NULL,
  `message` varchar(4000) NOT NULL,
  `ip` char(15) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8