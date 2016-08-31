<?php
namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Component;
use ManaPHP\Http\Session\AdapterInterface;

/**
 * Class Redis
 *
 * @package ManaPHP\Http\Session\Adapter
 * @property \Redis $redis
 */
class Redis extends Component implements AdapterInterface
{

    /**
     * @var int
     */
    protected $_ttl;

    /**
     * @var string
     */
    protected $_prefix = 'manaphp:session:';

    /**
     * Redis constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        }

        $this->_ttl = (int)(isset($options['ttl']) ? $options['ttl'] : ini_get('session.gc_maxlifetime'));

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param string $savePath
     * @param string $sessionName
     *
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return string
     */
    public function read($sessionId)
    {
        $data = $this->redis->get($this->_prefix . $sessionId);
        return is_string($data) ? $data : '';
    }

    /**
     * @param string $sessionId
     * @param string $data
     *
     * @return void
     */
    public function write($sessionId, $data)
    {
        $this->redis->set($this->_prefix . $sessionId, $data, $this->_ttl);
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId)
    {
        $this->redis->delete($this->_prefix . $sessionId);

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function gc($ttl)
    {
        return true;
    }
}