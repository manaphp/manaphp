<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ArrayAccess;
use ManaPHP\Component;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Str;

/**
 * @property-read \ManaPHP\AliasInterface              $alias
 * @property-read \ManaPHP\Logging\LoggerInterface     $logger
 * @property-read \ManaPHP\Http\CookiesInterface       $cookies
 * @property-read \ManaPHP\Http\RequestInterface       $request
 * @property-read \ManaPHP\Http\AbstractSessionContext $context
 */
abstract class AbstractSession extends Component implements SessionInterface, ArrayAccess
{
    protected int $ttl = 3600;
    protected int $lazy;
    protected string $name = 'PHPSESSID';
    protected string $serializer = 'json';
    protected array $params = ['expire' => 0, 'path' => null, 'domain' => null, 'secure' => false, 'httponly' => true];

    public function __construct(array $options = [])
    {
        if (isset($options['ttl'])) {
            $this->ttl = (int)$options['ttl'];
        }

        if (isset($options['lazy'])) {
            $this->lazy = (int)$options['lazy'];
        } else {
            $this->lazy = (int)min($this->ttl / 10, 600);
        }

        if (isset($options['name'])) {
            $this->name = $options['name'];
        }

        if (isset($options['serializer'])) {
            $this->serializer = $options['serializer'];
        }

        if (isset($options['params'])) {
            $this->params = $options['params'] + $this->params;
        }

        if (!isset($this->params['path'])) {
            $this->params['path'] = $this->alias->get('@web') ?: '/';
        }

        $this->attachEvent('request:responding', [$this, 'onRequestResponding']);
    }

    protected function start(): void
    {
        $context = $this->context;

        if ($context->started) {
            return;
        }

        $context->started = true;

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

        $this->fireEvent('session:start', compact('context', 'session_id'));
    }

    public function onRequestResponding(): void
    {
        $context = $this->context;

        if (!$context->started) {
            return;
        }

        $session_id = $context->session_id;

        $this->fireEvent('session:end', compact('context', 'session_id'));

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

            $this->fireEvent('session:create', compact('context', 'session_id'));
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

        $this->fireEvent('session:update', compact('context', 'session_id'));

        if ($this->lazy) {
            $context->_SESSION['__T'] = time();
        }

        $data = $this->serialize($context->_SESSION);
        if (!is_string($data)) {
            $this->logger->error('serialize data failed', 'session.serialize');
        }
        $this->do_write($context->session_id, $data, $context->ttl ?? $this->ttl);
    }

    public function destroy(?string $session_id = null): static
    {
        if ($session_id) {
            $this->fireEvent('session:destroy', compact('session_id'));
            $this->do_destroy($session_id);
        } else {
            $context = $this->context;

            if (!$context->started) {
                $this->start();
            }

            $session_id = $context->session_id;
            $this->fireEvent('session:destroy', compact('context', 'session_id'));

            $context->started = false;
            $context->is_dirty = false;
            $context->session_id = null;
            $context->_SESSION = null;
            $this->do_destroy($context->session_id);

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
        } elseif ($serializer === 'wddx') {
            return wddx_serialize_value($data);
        } else {
            throw new NotSupportedException(['`:serializer` serializer is not support', 'serializer' => $serializer]);
        }
    }

    public function unserialize(string $data): false|array
    {
        $serializer = $this->serializer;

        if ($serializer === 'php') {
            $r = [];
            $offset = 0;
            while ($offset < strlen($data)) {
                if (!str_contains(substr($data, $offset), '|')) {
                    return false;
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
        } elseif ($serializer === 'wddx') {
            return wddx_deserialize($data);
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

    public function get(?string $name = null, mixed $default = null): mixed
    {
        $context = $this->context;

        if (!$context->started) {
            $this->start();
        }

        if ($name === null) {
            return $context->_SESSION;
        } elseif (isset($context->_SESSION[$name])) {
            return $context->_SESSION[$name];
        } else {
            return $default;
        }
    }

    public function set(string $name, mixed $value): static
    {
        $context = $this->context;

        if (!$context->started) {
            $this->start();
        }

        $context->is_dirty = true;
        $context->_SESSION[$name] = $value;

        return $this;
    }

    public function has(string $name): bool
    {
        $context = $this->context;

        if (!$context->started) {
            $this->start();
        }

        return isset($context->_SESSION[$name]);
    }

    public function remove(string $name): static
    {
        $context = $this->context;

        if (!$context->started) {
            $this->start();
        }

        $context->is_dirty = true;
        unset($context->_SESSION[$name]);

        return $this;
    }

    public function getId(): string
    {
        $context = $this->context;

        if (!$context->started) {
            $this->start();
        }

        return $context->session_id;
    }

    public function setId(string $id): static
    {
        $context = $this->context;

        if (!$context->started) {
            $this->start();
        }

        $context->session_id = $id;

        return $this;
    }

    public function getTtl(): int
    {
        return $this->context->ttl ?? $this->ttl;
    }

    public function setTtl(int $ttl): static
    {
        $this->context->ttl = $ttl;

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
        $session = $this->serialize($data);

        $this->do_write($session_id, $session, $this->context->ttl ?? $this->ttl);

        return $this;
    }
}