<?php

namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Http\Session;

class Redis extends Session
{
    /**
     * @var string
     */
    protected $_prefix;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->_prefix = $options['prefix'] ?? "cache:{$this->configure->id}:session:";
    }

    /**
     * @param string $session_id
     *
     * @return string
     */
    public function do_read($session_id)
    {
        $data = $this->redisCache->get($this->_prefix . $session_id);
        return is_string($data) ? $data : '';
    }

    /**
     * @param string $session_id
     * @param string $data
     * @param int    $ttl
     *
     * @return bool
     */
    public function do_write($session_id, $data, $ttl)
    {
        return $this->redisCache->set($this->_prefix . $session_id, $data, $ttl);
    }

    /**
     * @param string $session_id
     * @param int    $ttl
     *
     * @return bool
     */
    public function do_touch($session_id, $ttl)
    {
        $this->redisCache->expire($this->_prefix . $session_id, $ttl);

        return true;
    }

    /**
     * @param string $session_id
     *
     * @return bool
     */
    public function do_destroy($session_id)
    {
        $this->redisCache->del($this->_prefix . $session_id);

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function do_gc($ttl)
    {
        return true;
    }
}
