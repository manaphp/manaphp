<?php

namespace ManaPHP\Data\Db;

use ManaPHP\Event\EventArgs;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 */
class Tracer extends \ManaPHP\Tracing\Tracer
{
    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->verbose && $this->attachEvent('db:connecting', [$this, 'onConnecting']);
        $this->attachEvent('db:queried', [$this, 'onQueried']);
        $this->attachEvent('db:executed', [$this, 'onExecuted']);
        $this->attachEvent('db:inserted', [$this, 'onInserted']);
        $this->attachEvent('db:begin', [$this, 'onBegin']);
        $this->attachEvent('db:rollback', [$this, 'onRollback']);
        $this->attachEvent('db:commit', [$this, 'onCommit']);
        $this->verbose && $this->attachEvent('db:metadata', [$this, 'onMetadata']);
        $this->attachEvent('db:abnormal', [$this, 'onAbnormal']);
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onConnecting(EventArgs $eventArgs)
    {
        $this->logger->debug(['connecting to `:dsn`', 'dsn' => $eventArgs->data['dsn']], 'db.connect');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onExecuted(EventArgs $eventArgs)
    {
        $data = $eventArgs->data;

        $this->logger->info($data, 'db.' . $data['type']);
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onQueried(EventArgs $eventArgs)
    {
        $data = $eventArgs->data;

        if (!$this->verbose) {
            unset($data['result']);
        }
        $this->logger->debug($data, 'db.query');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onInserted(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'db.insert');
    }

    /**
     * @return void
     */
    public function onBegin()
    {
        $this->logger->info('transaction begin', 'db.begin');
    }

    /**
     * @return void
     */
    public function onRollback()
    {
        $this->logger->info('transaction rollback', 'db.rollback');
    }

    /**
     * @return void
     */
    public function onCommit()
    {
        $this->logger->info('transaction commit', 'db.commit');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onMetadata(EventArgs $eventArgs)
    {
        $this->logger->debug($eventArgs->data, 'db.metadata');
    }

    /**
     * @return void
     */
    public function onAbnormal()
    {
        $this->logger->error('transaction is not close correctly', 'db.abnormal');
    }
}