<?php

namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Http\Session;
use ManaPHP\Http\Session\Adapter\Cookie\Exception as CookieException;

class Cookie extends Session
{
    /**
     * @var string
     */
    protected $_key;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (isset($options['crypt'])) {
            $this->_injections['crypt'] = $options['crypt'];
        }

        $this->_key = $options['key'] ?? $this->crypt->getDerivedKey('cookieSession');
    }

    /**
     * @param string $session_id
     *
     * @return string
     * @throws \ManaPHP\Http\Session\Adapter\Cookie\Exception
     */
    public function do_read($session_id)
    {
        $data = $this->cookies->get($session_id) ?: '';
        if ($data === '') {
            return '';
        }

        $parts = explode('.', $data, 2);

        if (count($parts) !== 2) {
            throw new CookieException(['format invalid: `:cookie`', 'cookie' => $data]);
        }

        if (md5($parts[0] . $this->_key) !== $parts[1]) {
            throw new CookieException(['hash invalid: `:cookie`', 'cookie' => $data]);
        }

        $payload = json_parse($parts[0]);
        if (!is_array($payload)) {
            throw new CookieException(['payload invalid: `:cookie`', 'cookie' => $data]);
        }

        if (time() > $payload['exp']) {
            return '';
        }

        return $payload['data'];
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
        $params = session_get_cookie_params();

        $payload = base64_encode(json_stringify(['exp' => time() + $ttl, 'data' => $data]));
        $this->cookies->set(
            $session_id,
            $payload . '.' . md5($payload . $this->_key),
            $params['lifetime'],
            $params['path'],
            $params['domain'],
            $params['secure']
        );

        return true;
    }

    /**
     * @param string $session_id
     * @param int    $ttl
     *
     * @return bool
     */
    public function do_touch($session_id, $ttl)
    {
        return false;
    }

    /**
     * @param string $session_id
     *
     * @return bool
     */
    public function do_destroy($session_id)
    {
        $this->cookies->delete($session_id);

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