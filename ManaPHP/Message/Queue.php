<?php
namespace ManaPHP\Message;

use ManaPHP\Component;

class Queue extends Component implements QueueInterface
{
    /**
     * @var string|\ManaPHP\Message\Queue\EngineInterface
     */
    protected $_engine;

    /**
     * Queue constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = 'ManaPHP\Message\Queue\Engine\Redis')
    {
        if (is_string($options)) {
            $this->_engine = $options;
        } else {
            $this->_engine = $options;
        }
    }

    /**
     * @return \ManaPHP\Cache\EngineInterface
     */
    protected function _getEngine()
    {
        if (is_string($this->_engine)) {
            return $this->_engine = $this->_dependencyInjector->getShared($this->_engine);
        } else {
            return $this->_engine = $this->_dependencyInjector->getInstance($this->_engine);
        }
    }

    /**
     * @param string $topic
     * @param string $body
     * @param int    $priority
     */
    public function push($topic, $body, $priority = self::PRIORITY_NORMAL)
    {
        $this->fireEvent('messageQueue:push', ['topic' => $topic]);

        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        $engine->push($topic, $body, $priority);
    }

    /**
     * @param string $topic
     * @param int    $timeout
     *
     * @return string|false
     */
    public function pop($topic, $timeout = PHP_INT_MAX)
    {
        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        if (($msg = $engine->pop($topic, $timeout)) !== false) {
            $this->fireEvent('messageQueue:pop', ['topic' => $topic, 'msg' => $msg]);
        }

        return $msg;
    }

    /**
     * @param string $topic
     *
     * @return void
     */
    public function delete($topic)
    {
        $this->fireEvent('messageQueue:delete', ['topic' => $topic]);
        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        $engine->delete($topic);
    }

    /**
     * @param string $topic
     * @param int    $priority
     *
     * @return         int
     */
    public function length($topic, $priority = null)
    {
        $engine = is_object($this->_engine) ? $this->_engine : $this->_getEngine();
        return $engine->length($topic, $priority);
    }
}