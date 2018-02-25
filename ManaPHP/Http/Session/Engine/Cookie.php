<?php
namespace ManaPHP\Http\Session\Engine;

use ManaPHP\Component;
use ManaPHP\Http\Session\Engine\Cookie\Exception as CookieException;
use ManaPHP\Http\Session\EngineInterface;

class Cookie extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_key;

    /**
     * Cookie constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $this->_key = $options;
        } else {
            if (isset($options['key'])) {
                $this->_key = $options['key'];
            }
        }
    }

    /**
     * @return string
     */
    protected function _getKey()
    {
        return $this->_key = $this->crypt->getDerivedKey('cookieSession');
    }

    /**
     * @param string $session_id
     *
     * @return string
     * @throws \ManaPHP\Http\Session\Engine\Cookie\Exception
     */
    public function read($session_id)
    {
        $data = $this->_dependencyInjector->cookies->get($session_id) ?: '';
        if ($data === '') {
            return '';
        }

        $parts = explode('.', $data, 2);

        if (count($parts) !== 2) {
            throw new CookieException(['format invalid: `:cookie`', 'cookie' => $data]);
        }

        $key = $this->_key ?: $this->_getKey();
        if (md5($parts[0] . $key) !== $parts[1]) {
            throw new CookieException(['hash invalid: `:cookie`', 'cookie' => $data]);
        }

        $payload = json_decode(base64_decode($parts[0]), true);
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
     * @param array  $context
     *
     * @return bool
     */
    public function write($session_id, $data, $context)
    {
        $params = session_get_cookie_params();

        $key = $this->_key ?: $this->_getKey();
        $payload = base64_encode(json_encode(['exp' => time() + $context['ttl'], 'data' => $data]));
        $this->_dependencyInjector->cookies->set($session_id, $payload . '.' . md5($payload . $key), $params['lifetime'], $params['path'], $params['domain'],
            $params['secure']);

        return true;
    }

    /**
     * @param string $session_id
     *
     * @return bool
     */
    public function destroy($session_id)
    {
        $this->_dependencyInjector->cookies->delete($session_id);

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