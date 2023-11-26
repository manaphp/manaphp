<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Pooling\PoolsInterface;

class TransientDb extends Db
{
    protected DbContext $context;

    /** @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(PoolsInterface $pools,
        protected object $owner,
        ConnectionInterface $connection,
        protected string $type
    ) {
        $this->pools = $pools;

        $this->context = new  DbContext();
        $this->context->connection = $connection;
    }

    public function getContext(int $cid = 0): DbContext
    {
        return $this->context;
    }

    public function __destruct()
    {
        $this->pools->push($this->owner, $this->context->connection, $this->type);
    }

    public function getTransientCopy(?string $type = null): static
    {
        $type ??= $this->has_slave ? 'slave' : 'default';

        /** @var ConnectionInterface $connection */
        $connection = $this->pools->pop($this, $this->timeout, $type);

        return new TransientDb($this->pools, $this->owner, $connection, $type);
    }
}