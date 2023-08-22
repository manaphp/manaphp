<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Mongodb\Event\MongodbConnect;
use ManaPHP\Mongodb\Exception as MongodbException;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query as MongodbQuery;
use MongoDB\Driver\ReadPreference;
use MongoDB\Driver\WriteConcern;
use MongoDB\Driver\WriteResult;
use Psr\EventDispatcher\EventDispatcherInterface;

class Connection implements ConnectionInterface
{
    #[Inject] protected EventDispatcherInterface $eventDispatcher;

    #[Value] protected string $uri;
    protected ?Manager $manager = null;
    protected ?WriteConcern $writeConcern = null;
    protected int $heartbeat = 60;
    protected ?float $last_heartbeat = null;

    /** @noinspection PhpUnusedLocalVariableInspection */
    protected function ping(): bool
    {
        try {
            $command = new Command(['ping' => 1]);
            $this->manager->executeCommand('admin', $command);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    protected function getManager(): Manager
    {
        $uri = $this->uri;

        if ($this->manager === null) {
            $this->eventDispatcher->dispatch(new MongodbConnect($this, $uri));
            $this->manager = new Manager($uri);
        }

        if (microtime(true) - $this->last_heartbeat > $this->heartbeat && !$this->ping()) {
            $this->close();
            $this->eventDispatcher->dispatch(new MongodbConnect($this, $uri));

            $this->manager = new Manager($this->uri);
        }

        $this->last_heartbeat = microtime(true);

        return $this->manager;
    }

    public function bulkWrite(string $namespace, BulkWrite $bulk): WriteResult
    {
        if ($this->writeConcern === null) {
            try {
                $this->writeConcern = new WriteConcern(WriteConcern::MAJORITY, 10000);
            } catch (\Exception $exception) {
                throw new MongodbException($exception);
            }
        }

        $start_time = microtime(true);
        if ($start_time - $this->last_heartbeat > 1.0) {
            $this->last_heartbeat = null;
        }
        try {
            $result = $this->getManager()->executeBulkWrite($namespace, $bulk, $this->writeConcern);
        } catch (\Exception $exception) {
            throw new MongodbException($exception);
        }

        return $result;
    }

    public function insert(string $namespace, array $document): int
    {
        $bulk = new BulkWrite();

        $bulk->insert($document);

        return $this->bulkWrite($namespace, $bulk)->getInsertedCount();
    }

    public function bulkInsert(string $namespace, array $documents): int
    {
        $bulk = new BulkWrite();

        foreach ($documents as $document) {
            $bulk->insert($document);
        }

        return $this->bulkWrite($namespace, $bulk)->getInsertedCount();
    }

    public function update(string $source, array $document, array $filter): int
    {
        $bulk = new BulkWrite();

        try {
            $bulk->update($filter, key($document)[0] === '$' ? $document : ['$set' => $document], ['multi' => true]);
        } catch (\Exception $exception) {
            throw new MongodbException($exception);
        }

        return $this->bulkWrite($source, $bulk)->getModifiedCount();
    }

    public function bulkUpdate(string $source, array $documents, string $primaryKey): int
    {
        $bulk = new BulkWrite();

        foreach ($documents as $document) {
            $pkValue = $document[$primaryKey];
            unset($document[$primaryKey]);
            try {
                $bulk->update([$primaryKey => $pkValue], key($document)[0] === '$' ? $document : ['$set' => $document]);
            } catch (\Exception $exception) {
                throw new MongodbException($exception);
            }
        }

        return $this->bulkWrite($source, $bulk)->getModifiedCount();
    }

    public function upsert(string $namespace, array $document, string $primaryKey): int
    {
        $bulk = new BulkWrite();

        try {
            $bulk->update([$primaryKey => $document[$primaryKey]], $document, ['upsert' => true]);
        } catch (\Exception $exception) {
            throw new MongodbException($exception);
        }

        return $this->bulkWrite($namespace, $bulk)->getUpsertedCount();
    }

    public function bulkUpsert(string $namespace, array $documents, string $primaryKey): int
    {
        $bulk = new BulkWrite();

        foreach ($documents as $document) {
            try {
                $bulk->update([$primaryKey => $document[$primaryKey]], $document, ['upsert' => true]);
            } catch (\Exception $exception) {
                throw new MongodbException($exception);
            }
        }

        return $this->bulkWrite($namespace, $bulk)->getUpsertedCount();
    }

    public function delete(string $namespace, array $filter): int
    {
        $bulk = new BulkWrite();

        try {
            $bulk->delete($filter);
        } catch (\Exception $exception) {
            throw new MongodbException($exception);
        }

        return $this->bulkWrite($namespace, $bulk)->getDeletedCount();
    }

    protected function fetchAllInternal(string $namespace, array $filter, array $options, ReadPreference $readPreference
    ): array {
        $cursor = $this->getManager()->executeQuery(
            $namespace, new MongodbQuery($filter, $options), $readPreference
        );
        $cursor->setTypeMap(['root' => 'array']);
        return $cursor->toArray();
    }

    public function fetchAll(string $namespace, array $filter = [], array $options = [], bool $secondaryPreferred = true
    ): array {
        if (is_bool($secondaryPreferred)) {
            if ($secondaryPreferred) {
                $readPreference = new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED);
            } else {
                $readPreference = new ReadPreference(ReadPreference::RP_PRIMARY);
            }
        } else {
            $readPreference = new ReadPreference($secondaryPreferred);
        }
        try {
            $result = $this->fetchAllInternal($namespace, $filter, $options, $readPreference);
        } catch (\Exception $exception) {
            $result = null;
            $failed = true;
            if (!$this->ping()) {
                try {
                    $this->close();
                    $result = $this->fetchAllInternal($namespace, $filter, $options, $readPreference);
                    $failed = false;
                } catch (\Exception $exception) {
                }
            }

            if ($failed) {
                throw new MongodbException($exception);
            }
        }

        return $result;
    }

    public function command(array $command, string $db): array
    {
        $start_time = microtime(true);
        if ($start_time - $this->last_heartbeat > 1.0) {
            $this->last_heartbeat = null;
        }
        try {
            $cursor = $this->getManager()->executeCommand($db, new Command($command));
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
            return $cursor->toArray();
        } catch (\Exception $exception) {
            throw new MongodbException($exception);
        }
    }

    public function close(): void
    {
        $this->manager = null;
        $this->last_heartbeat = null;
    }
}