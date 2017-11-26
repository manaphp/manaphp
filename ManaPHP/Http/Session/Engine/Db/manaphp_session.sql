CREATE TABLE `manaphp_session` (
  `session_id` char(32) CHARACTER SET ascii NOT NULL,
  `user_id` int(11) NOT NULL,
  `client_ip` char(15) NOT NULL,
  `data` text NOT NULL,
  `updated_time` int(11) NOT NULL,
  `expired_time` int(11) NOT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8