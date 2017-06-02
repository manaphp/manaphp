<?php

namespace ManaPHP\ZooKeeper;

abstract class WatchedEvent
{
    /**
     * @var float
     */
    public $time;

    /**
     * @var int
     */
    public $type;

    /**
     * @var string
     */
    public $path;

    /**
     * @var int
     */
    public $stat;
}