<?php

namespace ManaPHP;

use ManaPHP\Exception\MisuseException;

class RedisContext
{
    /**
     * @var \ManaPHP\Redis\Connection
     */
    public $connection;
}

/**
 * Class Redis
 *
 * @package ManaPHP
 * @property-read \ManaPHP\RedisContext $_context
 */
class Redis extends Component
{
    /**
     * @var string
     */
    protected $_url;

    /**
     * @var float
     */
    protected $_timeout = 1.0;

    /**
     * Redis constructor.
     *
     * @param string $url
     */
    public function __construct($url = 'redis://127.0.0.1/1?timeout=3&retry_interval=0&auth=&persistent=0')
    {
        $this->_url = $url;

        if (preg_match('#timeout=([\d.]+)#', $url, $matches) === 1) {
            $this->_timeout = (float)$matches[1];
        }

        $pool_size = preg_match('#pool_size=(\d+)#', $url, $matches) ? $matches[1] : 4;
        $this->poolManager->add($this, ['class' => 'ManaPHP\Redis\Connection', $url], $pool_size);
    }

    public function __destruct()
    {
        $this->poolManager->remove($this);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return bool|mixed
     */
    public function call($name, ...$arguments)
    {
        $context = $this->_context;

        if ($name === 'multi') {
            if ($context->connection !== null) {
                $this->poolManager->push($this, $context->connection);
                $context->connection = null;
                throw new MisuseException('redis is in multi already.');
            }

            $context->connection = $this->poolManager->pop($this, $this->_timeout);

            try {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $success = false;
                $context->connection->call($name, $arguments);
                $success = true;
            } finally {
                if (!$success) {
                    $this->poolManager->push($this, $context->connection);
                    $context->connection = null;
                }
            }

            return $this;
        } elseif ($name === 'exec' || $name === 'discard') {
            if ($context->connection === null) {
                throw new MisuseException('redis is not in multi.');
            }

            try {
                return $context->connection->call($name, $arguments);
            } finally {
                $this->poolManager->push($this, $context->connection);
                $context->connection = null;
            }
        } elseif ($context->connection) {
            try {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $success = false;
                $context->connection->call($name, $arguments);
                $success = true;
            } finally {
                if (!$success) {
                    $this->poolManager->push($this, $context->connection);
                    $context->connection = null;
                }
            }

            return $this;
        } else {
            $connection = $this->poolManager->pop($this, $this->_timeout);

            try {
                return $connection->call($name, $arguments);
            } finally {
                $this->poolManager->push($this, $connection);
            }
        }
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return bool|mixed
     */
    public function __call($name, $arguments)
    {
        $this->fireEvent('redis:calling', ['name' => $name, 'arguments' => $arguments]);

        $r = $this->call($name, ...$arguments);

        $this->fireEvent('redis:called', ['name' => $name, 'arguments' => $arguments, 'return' => $r]);

        return $r;
    }
}
