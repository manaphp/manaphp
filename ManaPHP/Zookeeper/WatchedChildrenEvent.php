<?php
namespace ManaPHP\ZooKeeper;

class WatchedChildrenEvent extends WatchedEvent
{
    /**
     * @var array
     */
    public $children;
}