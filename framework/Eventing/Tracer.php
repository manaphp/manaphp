<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Logging\Logger\Event\LoggerLog;
use ManaPHP\Mongodb\Event\MongodbCommanded;
use ManaPHP\Redis\Event\RedisCalled;
use ManaPHP\Redis\Event\RedisCalling;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionUnionType;
use Stringable;

class Tracer implements TracerInterface
{
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected ListenerProviderInterface $listenerProvider;
    #[Autowired] protected LoggerInterface $logger;

    #[Autowired] protected array $ignores = [];
    #[Autowired] protected array $events = [];
    #[Autowired] protected bool $verbose = true;

    protected array $listeners = [];

    protected function getListeners(): array
    {
        $listeners = [];

        $rClass = new ReflectionClass($this);
        foreach ($rClass->getMethods(ReflectionMethod::IS_PUBLIC) as $rMethod) {
            if (count($rParameters = $rMethod->getParameters()) !== 1) {
                continue;
            }
            $rParameter = $rParameters[0];
            if ($rParameter->getAttributes(Event::class) !== []) {
                $method = $rMethod->getName();

                $rType = $rParameter->getType();
                if ($rType instanceof ReflectionUnionType) {
                    foreach ($rType->getTypes() as $rType) {
                        $listeners[$rType->getName()] = $method;
                    }
                } else {
                    $listeners[$rType->getName()] = $method;
                }
            }
        }

        return $listeners;
    }

    public function onEvent(object $event): void
    {
        $name = $event::class;

        foreach ($this->ignores as $ignore) {
            if (str_starts_with($name, $ignore)) {
                return;
            }
        }

        if (($listener = $this->listeners[$name] ?? null) !== null) {
            $this->$listener($event);
        } else {
            if (($evt = $this->events[$name] ?? null) !== null) {
                if ($evt === '') {
                    return;
                }

                if (str_contains($evt, ':')) {
                    list($level, $fields) = explode($evt, ':', 2);
                } else {
                    $level = 'debug';
                    $fields = $evt;
                }
                $message = new EventWrapper($event, $fields);
            } else {
                $message = $event instanceof Stringable ? $event : new EventWrapper($event);
                $level = 'debug';
            }

            $this->logger->$level($message, ['category' => str_replace('\\', '.', $name)]);
        }
    }

    public function start(): void
    {
        $this->listeners = $this->getListeners();

        $this->listenerProvider->on('*', [$this, 'onEvent']);
    }

    public function onLoggerLog(#[Event] LoggerLog $event): void
    {
        null;
    }

    public function onRedisCalling(#[Event] RedisCalling $event): void
    {
        $method = $event->method;
        $arguments = $event->arguments;

        $args = substr(json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR), 1, -1);
        if (stripos(',blPop,brPop,brpoplpush,subscribe,psubscribe,', ",$method,") !== false) {
            $this->logger->debug("\$redis->$method({0}) ... blocking", [$args, 'category' => 'redis.' . $method]);
        }
    }

    public function onRedisCalled(#[Event] RedisCalled $event): void
    {
        $method = $event->method;
        $arguments = $event->arguments;
        foreach ($arguments as $k => $v) {
            if (is_string($v) && strlen($v) > 128) {
                $arguments[$k] = substr($v, 0, 128) . '...';
            }
        }

        if ($this->verbose) {
            $arguments = json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);
            $return = json_stringify($event->return, JSON_PARTIAL_OUTPUT_ON_ERROR);

            $ret = strlen($return) > 64 ? substr($return, 0, 64) . '...' : $return;
            $args = strlen($arguments) > 256 ? substr($arguments, 1, 256) . '...)' : substr($arguments, 1, -1);
            $this->logger->debug("\$redis->$method({0}) => {1}", [$args, $ret, 'category' => 'redis.' . $method]);
        } else {
            $key = $arguments[0] ?? false;
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!$this->config->get('debug') && is_string($key) && str_starts_with($key, 'cache:')) {
                return;
            }
            $arguments = json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);

            $args = strlen($arguments) > 256 ? substr($arguments, 1, 256) . '...)' : substr($arguments, 1, -1);
            $this->logger->debug("\$redis->$method({0})", [$args, 'category' => 'redis.' . $method]);
        }
    }

    public function onMongodbCommanded(#[Event] MongodbCommanded $event): void
    {
        $command_name = key($event->command);

        if (str_contains(
            'ping,aggregate,count,distinct,group,mapReduce,geoNear,geoSearch,find,' .
            'authenticate,listDatabases,listCollections,listIndexes', $command_name
        )
        ) {
            $this->logger->debug($event, ['category' => 'mongodb.command.' . $command_name]);
        } else {
            $this->logger->info($event, ['category' => 'mongodb.command.' . $command_name]);
        }
    }
}