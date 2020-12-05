<?php

namespace ManaPHP\Data\Db;

class Table extends \ManaPHP\Data\Table
{
    /**
     * @return string
     */
    public function getDb()
    {
        return 'db';
    }

    /**
     * @param mixed $context =get_object_vars(new static)
     *
     * @return \ManaPHP\Data\DbInterface
     */
    public static function connection($context = null)
    {
        list($db) = static::sample()->getUniqueShard($context);

        return static::sample()->getShared($db);
    }
}