<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Context\ContextInseparable;
use ManaPHP\Exception\MisuseException;

class DbContext implements ContextInseparable
{
    public ?ConnectionInterface $connection = null;
    public string $sql;
    public array $bind = [];
    public int $transaction_level = 0;
    public int $affected_rows;

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