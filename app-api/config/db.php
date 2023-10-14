<?php
declare(strict_types=1);

use ManaPHP\Di\Pool;

return [
    'ManaPHP\Db\DbInterface' => new Pool([
        'default' => ['class' => 'ManaPHP\Db\Db', 'uri' => env('DB_URL')],
    ]),
];