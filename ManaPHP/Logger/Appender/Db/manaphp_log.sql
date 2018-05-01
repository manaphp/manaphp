CREATE TABLE `manaphp_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `host` varchar(16) NOT NULL,
  `process_id` int(11) NOT NULL,
  `category` varchar(64) NOT NULL,
  `level` char(8) CHARACTER SET ascii NOT NULL,
  `file` varchar(64) NOT NULL,
  `line` int(11) NOT NULL,
  `message` varchar(4000) NOT NULL,
  `created_time` int(11) NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `created_time` (`created_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8