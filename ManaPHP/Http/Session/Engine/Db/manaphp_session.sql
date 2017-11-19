CREATE TABLE `manaphp_session` (
  `session_id` char(32) CHARACTER SET ascii NOT NULL,
  `data` text NOT NULL,
  `ttl` int(11) NOT NULL,
  `expired_time` int(11) NOT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8