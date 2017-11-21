CREATE TABLE `manaphp_message_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `priority` tinyint(4) NOT NULL,
  `topic` char(16) NOT NULL,
  `body` varchar(4000) NOT NULL,
  `created_time` int(11) NOT NULL,
  `deleted_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
