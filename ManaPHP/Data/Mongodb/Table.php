<?php

namespace ManaPHP\Data\Mongodb;

class Table extends \ManaPHP\Data\Db\AbstractTable
{
    /**
     * @return string
     */
    public function db()
    {
        return 'mongodb';
    }
}