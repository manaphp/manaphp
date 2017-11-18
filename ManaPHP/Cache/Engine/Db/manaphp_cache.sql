CREATE TABLE `manaphp_cache` (
  `hash` char(32) CHARACTER SET ascii NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `ttl` int(11) NOT NULL,
  `expired_time` int(11) NOT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8