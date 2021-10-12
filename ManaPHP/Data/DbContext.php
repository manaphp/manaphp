<?php

namespace ManaPHP\Data;

use ManaPHP\Coroutine\Context\Inseparable;
use ManaPHP\Exception\MisuseException;

class DbContext implements Inseparable
{
    /**
     * @var \ManaPHP\Data\Db\ConnectionInterface
     */
    public $connection;

    /**
     * Active SQL Statement
     *
     * @var string
     */
    public $sql;

    /**
     * Active SQL bound parameter variables
     *
     * @var array
     */
    public $bind = [];

    /**
     * Current transaction level
     *
     * @var int
     */
    public $transaction_level = 0;

    /**
     * Last affected rows
     *
     * @var int
     */
    public $affected_rows;

    public function __destruct()
    {
        if ($this->transaction_level !== 0) {
            throw new MisuseException('transaction is not close correctly');
        }

        if ($this->connection !== null) {
            throw new MisuseException('connection is not released to pool');
        }
    }
}