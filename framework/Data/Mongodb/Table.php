<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb;

class Table extends \ManaPHP\Data\Db\Table
{
    public function connection(): string
    {
        return 'default';
    }
}