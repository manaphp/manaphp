<?php

namespace ManaPHP\Data\Mongodb;

class Table extends \ManaPHP\Data\Db\Table
{
    /**
     * @return string
     */
    public function getDb()
    {
        return 'mongodb';
    }
}