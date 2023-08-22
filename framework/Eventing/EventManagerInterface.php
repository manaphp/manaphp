<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

use Psr\EventDispatcher\EventDispatcherInterface;

interface EventManagerInterface extends EventDispatcherInterface, EventSubscriberInterface
{

}