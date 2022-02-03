CREATE TABLE `manaphp_log`
(
    `log_id`       int(11) NOT NULL AUTO_INCREMENT,
    `hostname`     varchar(16)                     NOT NULL,
    `client_ip`    varchar(15) CHARACTER SET ascii NOT NULL,
    `request_id`   varchar(64) CHARACTER SET ascii NOT NULL,
    `category`     varchar(64) CHARACTER SET ascii NOT NULL,
    `level`        char(8) CHARACTER SET ascii     NOT NULL,
    `file`         varchar(64)                     NOT NULL,
    `line`         int(11) NOT NULL,
    `message`      varchar(4000)                   NOT NULL,
    `timestamp`    float                           NOT NULL,
    `created_time` int(11) NOT NULL,
    PRIMARY KEY (`log_id`),
    KEY            `created_time` (`created_time`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8