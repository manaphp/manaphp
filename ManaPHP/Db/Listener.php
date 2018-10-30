<?php
namespace ManaPHP\Db;

class Listener extends \ManaPHP\Event\Listener
{
    /**
     * @param \ManaPHP\DbInterface $db
     * @param array                $data
     *
     * @return void
     */
    public function onBeforeConnect($db, $data)
    {

    }

    /**
     * @param \ManaPHP\DbInterface $db
     *
     * @return void
     */
    public function onAfterConnect($db)
    {

    }

    /**
     * @param \ManaPHP\DbInterface $db
     *
     * @return void
     */
    public function onBeforeQuery($db)
    {

    }

    /**
     * @param \ManaPHP\DbInterface $db
     *
     * @return void
     */
    public function onAfterQuery($db)
    {

    }

    /**
     * @param \ManaPHP\DbInterface $db
     *
     * @return void
     */
    public function onBeforeExecute($db)
    {

    }

    /**
     * @param \ManaPHP\DbInterface $db
     *
     * @return void
     */
    public function onAfterExecute($db)
    {

    }

    /**
     * @param \ManaPHP\DbInterface $db
     *
     * @return void
     */
    public function onBeginTransaction($db)
    {

    }

    /**
     * @param \ManaPHP\DbInterface $db
     *
     * @return void
     */
    public function onRollbackTransaction($db)
    {

    }

    /**
     * @param \ManaPHP\DbInterface $db
     *
     * @return void
     */
    public function onCommitTransaction($db)
    {

    }
}