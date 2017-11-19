CREATE TABLE `manaphp_counter` (
  `hash` char(32) CHARACTER SET ascii NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` int(11) NOT NULL,
  `created_time` int(11) NOT NULL,
  `updated_time` int(11) NOT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
