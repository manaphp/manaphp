<?php
namespace ManaPHP\Security;

use ManaPHP\Component;
use ManaPHP\Security\CsrfToken\Exception;

class CsrfToken extends Component implements CsrfTokenInterface
{
    /**
     * @var string
     */
    protected $_header = 'X-CSRF-Token';

    /**
     * @var int
     */
    protected $_length = 8;

    /**
     * @var bool
     */
    protected $_useCookie = true;
    /**
     * @var bool
     */
    protected $_enabled = true;

    /**
     * @var string
     */
    protected $_name = 'csrf_token';

    /**
     * CsrfToken constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        }

        if (isset($options['length'])) {
            $this->_length = $options['length'];
        }

        if (isset($options['useCookie'])) {
            $this->_useCookie = $options['useCookie'];
        }

        if (isset($options['name'])) {
            $this->_name = $options['name'];
        }
    }

    /**
     * @return string
     */
    protected function _generateToken()
    {
        $str = strtr(base64_encode(md5(microtime(true) . mt_rand(), true)), '+/=', '357');
        return substr($str, 0, $this->_length);
    }

    /**
     * @return string
     * @throws \ManaPHP\Security\Crypt\Exception
     */
    public function get()
    {
        if ($this->_useCookie) {
            if (!$this->cookies->has($this->_name)) {
                $this->cookies->set($this->_name, $this->_generateToken(), 0, '/', null);
            }

            return (string)$this->cookies->get($this->_name);
        } else {
            if (!$this->session->has($this->_name)) {
                $this->session->set($this->_name, $this->_generateToken());
            }

            return (string)$this->session->get($this->_name);
        }
    }

    /**
     * @return void
     * @throws \ManaPHP\Security\CsrfToken\Exception|\ManaPHP\Security\Crypt\Exception|\ManaPHP\Http\Request\Exception
     */
    public function verify()
    {
        if (!$this->_enabled) {
            return;
        }

        if ($this->_useCookie) {
            $token_server = $this->cookies->get($this->_name);
        } else {
            $token_server = $this->session->get($this->_name);
        }
        if ($token_server === null) {
            throw new Exception('The CSRF token could not be verified: missing in server.');
        } else {
            if ($this->request->get($this->_name)) {
                $token_client = $this->request->get($this->_name);
            } else {
                if ($this->request->hasServer($this->_header)) {
                    $token_client = $this->request->getServer($this->_header);
                }
            }

            if (!isset($token_client)) {
                throw new Exception('The CSRF token could not be verified: missing in client.');
            }

            if ($token_client !== $token_server) {
                throw new Exception('The CSRF token could not be verified: not match.');
            }
        }
    }

    /**
     * @return static
     */
    public function disable()
    {
        $this->_enabled = false;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        /** @noinspection MagicMethodsValidityInspection */
        return $this->get();
    }
}