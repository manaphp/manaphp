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
        if (is_object($options)) {
            $options = (array)$options;
        }

        if (is_string($options)) {
            $options = ['key' => $options];
        }

        if (isset($options['key'])) {
            $this->_key = $options['key'];
        }
    }

    /**
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);

        if ($this->_key === null) {
            $this->_key = $this->_dependencyInjector->crypt->getDerivedKey('cookieSession');
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return static
     */
    public function setKey($key)
    {
        $this->_key = $key;

        return $this;
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
     * @throws \ManaPHP\Http\Session\Engine\Cookie\Exception
     */
    public function read($sessionId)
    {
        $data = $this->_dependencyInjector->cookies->get($sessionId) ?: '';
        if ($data === '') {
            return '';
        }

        $parts = explode('.', $data, 2);

        if (count($parts) !== 2) {
            throw new CookieException('format invalid: `:cookie`', ['cookie' => $data]);
        }

        if (md5($parts[0] . $this->_key) !== $parts[1]) {
            throw new CookieException('hash invalid: `:cookie`', ['cookie' => $data]);
        }

        $payload = json_decode(base64_decode($parts[0]), true);
        if (time() > $payload['exp']) {
            return '';
        }

        return $payload['data'];
    }

    /**
     * @param string $sessionId
     * @param string $data
     * @param int    $ttl
     *
     * @return bool
     */
    public function write($sessionId, $data, $ttl)
    {
        $params = session_get_cookie_params();

        $payload = base64_encode(json_encode(['exp' => time() + $ttl, 'data' => $data]));
        $this->_dependencyInjector->cookies->set($sessionId, $payload . '.' . md5($payload . $this->_key), $params['lifetime'], $params['path'], $params['domain'],
            $params['secure']);

        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId)
    {
        $this->_dependencyInjector->cookies->delete($sessionId);

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function gc($ttl)
    {
        $this->clean();

        return true;
    }

    /**
     * @return void
     */
    public function clean()
    {

    }
}