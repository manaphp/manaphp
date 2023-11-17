<?php
declare(strict_types=1);

namespace ManaPHP\Swoole;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Http\Server\Event\ServerPipeMessage;
use ManaPHP\Http\Server\Event\ServerTask;
use ManaPHP\Http\Server\Event\ServerWorkerStart;
use ManaPHP\Swoole\Workers\PipeCallMessage;
use ManaPHP\Swoole\Workers\TaskCallMessage;
use ManaPHP\Swoole\Workers\TaskWaitCallMessage;
use Psr\Container\ContainerInterface;
use Swoole\Server;

class Workers implements WorkersInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected ListenerProviderInterface $listenerProvider;

    protected Server $server;

    public function bootstrap(): void
    {
        $this->listenerProvider->add($this);
    }

    public function onServerWorkerStart(#[Event] ServerWorkerStart $event): void
    {
        $this->server = $event->server;
    }

    public function onTask(#[Event] ServerTask $event): void
    {
        $message = $event->data;
        if ($message instanceof TaskCallMessage) {
            $target = $this->container->get($message->id);
            $method = $message->method;

            $target->$method(...$message->arguments);
        } elseif ($message instanceof TaskWaitCallMessage) {
            $target = $this->container->get($message->id);
            $method = $message->method;

            $return = $target->$method(...$message->arguments);
            $this->server->finish($return);
        }
    }

    public function onServerPipeMessage(#[Event] ServerPipeMessage $event): void
    {
        $message = $event->message;
        if ($message instanceof PipeCallMessage) {
            $target = $this->container->get($message->id);
            $method = $message->method;

            $target->$method(...$message->arguments);
        }
    }

    public function task(array|callable $task, array $arguments, int $task_worker_id, float $timeout = null): mixed
    {
        $id = \is_string($task[0]) ? $task[0] : \get_class($task[0]);

        if ($timeout === null) {
            $data = new TaskCallMessage($id, $task[1], $arguments);
            return $this->server->task($data, $task_worker_id);
        } else {
            $data = new TaskWaitCallMessage($id, $task[1], $arguments);
            return $this->server->taskwait($data, $timeout, $task_worker_id);
        }
    }

    public function sendMessage(array|callable $task, array $arguments, int $dst_worker_id): bool
    {
        $id = \is_string($task[0]) ? $task[0] : \get_class($task[0]);
        $message = new PipeCallMessage($id, $task[1], $arguments);

        return $this->server->sendMessage($message, $dst_worker_id);
    }

    public function getWorkerId(): int
    {
        return $this->server->worker_id;
    }

    public function getWorkerNum(): int
    {
        return $this->server->setting['worker_num'];
    }

    public function getTaskWorkerNum(): int
    {
        return $this->server->setting['task_worker_num'];
    }

    public function getServer(): Server
    {
        return $this->server;
    }
}