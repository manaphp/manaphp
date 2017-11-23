CREATE TABLE `manaphp_rate_limiter` (
  `hash` char(32) CHARACTER SET ascii NOT NULL,
  `type` char(64) NOT NULL,
  `id` char(32) NOT NULL,
  `times` int(11) NOT NULL,
  `expired_time` int(11) NOT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8