<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Pushing;

use ManaPHP\Coroutine;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Messaging\PubSubInterface;
use ManaPHP\Ws\Pushing\Server\Event\ServerPushing;
use ManaPHP\Ws\ServerInterface as WsServerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class Server implements ServerInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected LoggerInterface $logger;
    #[Autowired] protected IdentityInterface $identity;
    #[Autowired] protected WsServerInterface $wsServer;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected PubSubInterface $pubSub;

    #[Autowired] protected string $endpoint;
    #[Autowired] protected string $prefix = 'ws_pushing:';
    #[Autowired] protected bool $dedicated = false;

    protected array $ids;
    protected array $names;
    protected array $rooms;
    protected array $roles;
    protected array $fds = [];

    public function open(int $fd): void
    {
        if (!$this->dedicated) {
            $this->fds[$fd] = true;
        }

        if (($id = $this->identity->getId(0)) !== 0) {
            $this->ids[$id][$fd] = true;
        }

        if (($name = $this->identity->getName('')) !== '') {
            $this->names[$name][$fd] = true;
        }

        if (($room = $this->request->get('room_id', '')) !== '') {
            $this->rooms[$room][$fd] = true;
        }

        foreach ($this->identity->getRoles() as $role) {
            $this->roles[$role][$fd] = true;
        }
    }

    public function close(int $fd): void
    {
        if (!$this->dedicated) {
            unset($this->fds[$fd]);
        }

        if (($id = $this->identity->getId(0)) !== 0) {
            unset($this->ids[$id][$fd]);
            if (count($this->ids[$id]) === 0) {
                unset($this->ids[$id]);
            }
        }

        if (($name = $this->identity->getName('')) !== '') {
            unset($this->names[$name][$fd]);
            if (count($this->names[$name]) === 0) {
                unset($this->names[$name]);
            }
        }

        if (($room = $this->request->get('room_id', '')) !== '') {
            unset($this->rooms[$room][$fd]);
            if (count($this->rooms[$room]) === 0) {
                unset($this->rooms[$room]);
            }
        }

        foreach ($this->identity->getRoles() as $role) {
            unset($this->roles[$role][$fd]);
            if (count($this->roles[$role]) === 0) {
                unset($this->roles[$role]);
            }
        }
    }

    public function push(int $fd, string $message): void
    {
        $this->wsServer->push($fd, $message);
    }

    public function pushToId(array $receivers, string $message): void
    {
        foreach ($receivers as $id) {
            foreach ($this->ids[$id] ?? [] as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    public function pushToName(array $receivers, string $message): void
    {
        foreach ($receivers as $name) {
            foreach ($this->names[$name] ?? [] as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    public function pushToRoom(array $receivers, string $message): void
    {
        foreach ($receivers as $room) {
            foreach ($this->rooms[$room] ?? [] as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    public function pushToRole(array $receivers, string $message): void
    {
        $sent = [];
        foreach ($receivers as $role) {
            foreach ($this->roles[$role] ?? [] as $fd => $_) {
                if (!isset($sent[$fd])) {
                    $this->push($fd, $message);
                    $sent[$fd] = true;
                }
            }
        }
    }

    public function pushToAll(string $message): void
    {
        foreach ($this->ids as $fds) {
            foreach ($fds as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    public function broadcast(string $message): void
    {
        if ($this->dedicated) {
            $this->wsServer->broadcast($message);
        } else {
            foreach ($this->fds as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    public function dispatch(string $type, array $receivers, string $message): void
    {
        if ($type === 'broadcast') {
            $this->broadcast($message);
        } elseif ($type === 'all') {
            $this->pushToAll($message);
        } elseif ($type === 'id') {
            $this->pushToId($receivers, $message);
        } elseif ($type === 'name') {
            $this->pushToName($receivers, $message);
        } elseif ($type === 'role') {
            $this->pushToRole($receivers, $message);
        } elseif ($type === 'room') {
            $this->pushToRoom($receivers, $message);
        } else {
            $this->logger->warning(
                'unknown `{0}` type message: {1}', [$type, $message, 'category' => 'wspServer.bad_type']
            );
        }
    }

    public function start(): void
    {
        Coroutine::create(
            function () {
                $prefix = $this->prefix . $this->endpoint;
                $this->pubSub->psubscribe(
                    ["$prefix:*"], function ($channel, $message) use ($prefix) {
                    list($type, $receivers) = explode(':', substr($channel, strlen($prefix) + 1), 2);

                    if ($type !== null && $receivers !== null) {
                        $receivers = explode(',', $receivers);

                        $this->eventDispatcher->dispatch(new ServerPushing($this, $type, $receivers, $message));
                        $this->dispatch($type, $receivers, $message);
                        $this->eventDispatcher->dispatch(new ServerPushing($this, $type, $receivers, $message));
                    } else {
                        $this->logger->warning($channel, ['category' => 'wspServer.bad_channel']);
                    }
                }
                );
            }
        );
    }
}
