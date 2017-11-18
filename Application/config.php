<?php

return [
    'debug' => true,
    'version' => '1.1.1',
    'timezone' => 'PRC',
    'master_key' => '',
    'services' => [
        'pay' => ['key' => '124', 'secret' => 'abc'],
    ],
    'params' => ['manaphp_brand_show' => 1],
    'aliases' => [
        '@xxx' => '@root/xvdfd'
    ],
    'modules' => ['Home' => '/', 'Admin' => '/admin', 'Api' => '/api'],
    'modules:test,prod' => ['Home' => '/home'],
    'components' => [
        'db' => ['mysql://root@localhost/manaphp_unit_test?charset=utf8'],
        'redis' => ['redis://localhost:6379/1?timeout=2&retry_interval=0&auth='],
        'mongodb' => ['mongodb://127.0.0.1/manaphp_unit_test'],
        'logger' => [
            'level' => 'debug',
            'appenders' => ['file' => ['class' => \ManaPHP\Logger\Appender\Db::class, 'filter' => ['level' => 'error', 'category' => '*']]],
        ]
    ],
    'bootstraps' => ['debugger']
];