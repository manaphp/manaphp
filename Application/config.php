<?php

use ManaPHP\Db\Adapter\Mysql;
use ManaPHP\Redis;
use ManaPHP\Mongodb;

return [
    'debug' => true,
    'version' => '1.1.1',
    'timezone' => 'PRC',
    'master_key' => '',
    'aliases' => [
        '@xxx' => '@root/xvdfd'
    ],
    'modules' => ['Home' => '/', 'Admin' => '/admin', 'Api' => '/api'],
    'components' => [
        'db' => ['class' => Mysql::class, 'mysql://root@localhost/manaphp_unit_test?charset=utf8'],
        'redis' => ['class' => Redis::class, 'redis://localhost:6379/1/test?timeout=2&retry_interval=0&auth='],
        'mongodb' => ['class' => Mongodb::class, 'mongodb://127.0.0.1/manaphp_unit_test']
    ]
];