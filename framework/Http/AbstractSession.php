<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ArrayAccess;
use JsonSerializable;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Eventing\EventSubscriberInterface;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Str;
use ManaPHP\Http\Server\Event\RequestResponsing;
use ManaPHP\Http\Session\Event\SessionCreate;
use ManaPHP\Http\Session\Event\SessionDestory;
use ManaPHP\Http\Session\Event\SessionEnd;
use ManaPHP\Http\Session\Event\SessionStart;
use ManaPHP\Http\Session\Event\SessionUpdate;
use ManaPHP\Logging\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractSession implements SessionInterface, ArrayAccess, JsonSerializable
{
    use ContextTrait;

    #[Inject] protected EventDispatcherInterface $eventDispatcher;
    #[Inject] protected EventSubscriberInterface $eventSubscriber;

    #[Inject] protected LoggerInterface $logger;
    #[Inject] protected CookiesInterface $cookies;
    #[Inject] protected RequestInterface $request;
    #[Inject] protected RouterInterface $router;

    #[Value] protected int $ttl = 3600;
    #[Value] protected int $lazy = 60;
    #[Value] protected string $name = 'PHPSESSID';
    #[Value] protected string $serializer = 'json';

    protected array $params = ['expire' => 0, 'path' => '', 'domain' => '', 'secure' => false, 'httponly' => true];

    public function __construct(array $params = [])
    {
        $this->params = $params + $this->params;

        $this->eventSubscriber->addListener($this);
    }

    public function all(): array
    {
        /** @var AbstractSessionContext $context */
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        return $context->_SESSION;
    }

    protected function start(): void
    {
        /** @var AbstractSessionContext $context */
        $context = $this->getContext();

        if ($context->started) {
            return;
        }

        if (($session_id = $this->cookies->get($this->name)) && ($str = $this->do_read($session_id))) {
            $context->is_new = false;

            if (is_array($data = $this->unserialize($str))) {
                $context->_SESSION = $data;
            } else {
                $context->_SESSION = [];
                $this->logger->error('unserialize failed', 'session.unserialize');
            }
        } else {
            $session_id = $this->generateSessionId();
            $context->is_new = true;
            $context->_SESSION = [];
        }

        $context->session_id = $session_id;
        $context->started = true;

        $this->eventDispatcher->dispatch(new SessionStart($this, $context, $session_id));
    }

    public function onRequestResponding(#[Event] RequestResponsing $event): void
    {
        /** @var AbstractSessionContext $context */
        $context = $this->getContext();

        if (!$context->started) {
            return;
        }

        $session_id = $context->session_id;

        $this->eventDispatcher->dispatch(new SessionEnd($this, $context, $session_id));

        if ($context->is_new) {
            if (!$context->_SESSION) {
                return;
            }

            $params = $this->params;
            $expire = $params['expire'] ? time() + $params['expire'] : 0;

            $this->cookies->set(
                $this->name,
                $context->session_id,
                $expire,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );

            $this->eventDispatcher->dispatch(new SessionCreate($this, $context, $session_id));
        } elseif ($context->is_dirty) {
            null;
        } elseif ($this->lazy) {
            if (isset($context->_SESSION['__T']) && time() - $context->_SESSION['__T'] < $this->lazy) {
                return;
            }
        } else {
            if ($this->do_touch($context->session_id, $context->ttl ?? $this->ttl)) {
                return;
            }
        }

        $this->eventDispatcher->dispatch(new SessionUpdate($this, $context, $session_id));

        if ($this->lazy) {
            $context->_SESSION['__T'] = time();
        }

        $data = $this->serialize($context->_SESSION);
        $this->do_write($context->session_id, $data, $context->ttl ?? $this->ttl);
    }

    public function destroy(?string $session_id = null): static
    {
        if ($session_id) {
            $this->eventDispatcher->dispatch(new SessionDestory($this, null, $session_id));
            $this->do_destroy($session_id);
        } else {
            /** @var AbstractSessionContext $context */
            $context = $this->getContext();

            if (!$context->started) {
                $this->start();
            }

            $session_id = $context->session_id;
            $this->eventDispatcher->dispatch(new SessionDestory($this, $context, $session_id));

            $context->started = false;
            $context->is_dirty = false;
            $context->session_id = null;
            $context->_SESSION = null;
            $this->do_destroy($session_id);

            $name = $this->name;
            $params = $this->params;
            $this->cookies->delete($name, $params['path'], $params['domain']);
        }

        return $this;
    }

    abstract public function do_touch(string $session_id, int $ttl): bool;

    public function serialize(array $data): string
    {
        $serializer = $this->serializer;

        //https://github.com/wikimedia/php-session-serializer/blob/master/src/Wikimedia/PhpSessionSerializer.php
        if ($serializer === 'php') {
            $r = '';
            foreach ($data as $key => $value) {
                $v = serialize($value);
                $r .= "$key|$v";
            }
            return $r;
        } elseif ($serializer === 'php_binary') {
            $r = '';
            foreach ($data as $key => $value) {
                $r .= chr(strlen($key)) . $key . serialize($value);
            }
            return $r;
        } elseif ($serializer === 'php_serialize') {
            return $this->serialize($data);
        } elseif ($serializer === 'json') {
            return json_stringify($data);
        } elseif ($serializer === 'igbinary') {
            return igbinary_serialize($data);
        } else {
            throw new NotSupportedException(['`:serializer` serializer is not support', 'serializer' => $serializer]);
        }
    }

    public function unserialize(string $data): ?array
    {
        $serializer = $this->serializer;

        if ($serializer === 'php') {
            $r = [];
            $offset = 0;
            while ($offset < strlen($data)) {
                if (!str_contains(substr($data, $offset), '|')) {
                    return null;
                }
                $pos = strpos($data, '|', $offset);
                $num = $pos - $offset;
                $key = substr($data, $offset, $num);
                $offset += $num + 1;
                $value = unserialize(substr($data, $offset), ['allowed_classes' => true]);
                $r[$key] = $value;
                $offset += strlen(serialize($value));
            }
            return $r;
        } elseif ($serializer === 'php_binary') {
            $r = [];
            $offset = 0;
            while ($offset < strlen($data)) {
                $num = ord($data[$offset]);
                $offset++;
                $key = substr($data, $offset, $num);
                $offset += $num;
                $value = unserialize(substr($data, $offset), ['allowed_classes' => true]);
                $r[$key] = $value;
                $offset += strlen(serialize($value));
            }
            return $r;
        } elseif ($serializer === 'php_serialize') {
            return unserialize($data, ['allowed_classes' => true]);
        } elseif ($serializer === 'json') {
            return json_parse($data);
        } elseif ($serializer === 'igbinary') {
            return igbinary_unserialize($data);
        } else {
            throw new NotSupportedException(['`:serializer` serializer is not support', 'serializer' => $serializer]);
        }
    }

    abstract public function do_read(string $session_id): string;

    abstract public function do_write(string $session_id, string $data, int $ttl): bool;

    abstract public function do_gc(int $ttl): void;

    protected function generateSessionId(): string
    {
        return Str::random(32, 36);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        /** @var AbstractSessionContext $context */
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        return $context->_SESSION[$name] ?? $default;
    }

    public function set(string $name, mixed $value): static
    {
        /** @var AbstractSessionContext $context */
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        $context->is_dirty = true;
        $context->_SESSION[$name] = $value;

        return $this;
    }

    public function has(string $name): bool
    {
        /** @var AbstractSessionContext $context */
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        return isset($context->_SESSION[$name]);
    }

    public function remove(string $name): static
    {
        /** @var AbstractSessionContext $context */
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        $context->is_dirty = true;
        unset($context->_SESSION[$name]);

        return $this;
    }

    public function getId(): string
    {
        /** @var AbstractSessionContext $context */
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        return $context->session_id;
    }

    public function setId(string $id): static
    {
        /** @var AbstractSessionContext $context */
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        $context->session_id = $id;

        return $this;
    }

    public function getTtl(): int
    {
        /** @var AbstractSessionContext $context */
        $context = $this->getContext();

        return $context->ttl ?? $this->ttl;
    }

    public function setTtl(int $ttl): static
    {
        /** @var AbstractSessionContext $context */
        $context = $this->getContext();

        $context->ttl = $ttl;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract public function do_destroy(string $session_id): void;

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    public function read(string $session_id): array
    {
        $session = $this->do_read($session_id);
        if (!$session) {
            return [];
        }

        return $this->unserialize($session);
    }

    public function write(string $session_id, array $data): static
    {
        /** @var AbstractSessionContext $context */
        $context = $this->getContext();

        $session = $this->serialize($data);

        $this->do_write($session_id, $session, $context->ttl ?? $this->ttl);

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return $this->all();
    }
}