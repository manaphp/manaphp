<?php

namespace ManaPHP\Data\Db;

use ManaPHP\Event\EventArgs;

class Tracer extends \ManaPHP\Event\Tracer
{
    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->_verbose && $this->attachEvent('db:connect', [$this, 'onConnect']);
        $this->attachEvent('db:queried', [$this, 'onQueried']);
        $this->attachEvent('db:executed', [$this, 'onExecuted']);
        $this->attachEvent('db:inserted', [$this, 'onInserted']);
        $this->attachEvent('db:begin', [$this, 'onBegin']);
        $this->attachEvent('db:rollback', [$this, 'onRollback']);
        $this->attachEvent('db:commit', [$this, 'onCommit']);
        $this->_verbose && $this->attachEvent('db:metadata', [$this, 'onMetadata']);
        $this->attachEvent('db:abnormal', [$this, 'onAbnormal']);
    }

    public function onConnect(EventArgs $eventArgs)
    {
        $this->logger->debug(['connect to `:dsn`', 'dsn' => $eventArgs->data], 'db.connect');
    }

    public function onExecuted(EventArgs $eventArgs)
    {
        $data = $eventArgs->data;

        $this->logger->info($data, 'db.' . $data['type']);
    }

    public function onQueried(EventArgs $eventArgs)
    {
        $data = $eventArgs->data;

        if (!$this->_verbose) {
            unset($data['result']);
        }
        $this->logger->debug($data, 'db.query');
    }

    public function onInserted(EventArgs $eventArgs)
    {
        $this->logger->info($eventArgs->data, 'db.insert');
    }

    public function onBegin()
    {
        $this->logger->info('transaction begin', 'db.begin');
    }

    public function onRollback()
    {
        $this->logger->info('transaction rollback', 'db.rollback');
    }

    public function onCommit()
    {
        $this->logger->info('transaction commit', 'db.commit');
    }

    public function onMetadata(EventArgs $eventArgs)
    {
        $this->logger->debug($eventArgs->data, 'db.metadata');
    }

    public function onAbnormal()
    {
        $this->logger->error('transaction is not close correctly', 'db.abnormal');
    }
}