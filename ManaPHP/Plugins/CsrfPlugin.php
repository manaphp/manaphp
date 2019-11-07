<?php
namespace ManaPHP\Plugins;

use ManaPHP\Exception\CsrfTokenException;
use ManaPHP\Plugin;

class CsrfPlugin extends Plugin
{
    /**
     * @var string
     */
    protected $_header = 'HTTP_X_CSRF_TOKEN';

    /**
     * @var int
     */
    protected $_length = 8;

    /**
     * @var bool
     */
    protected $_useCookie = true;

    /**
     * @var string
     */
    protected $_name = 'csrf_token';

    /**
     * CsrfPlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['length'])) {
            $this->_length = $options['length'];
        }

        if (isset($options['useCookie'])) {
            $this->_useCookie = $options['useCookie'];
        }

        if (isset($options['name'])) {
            $this->_name = $options['name'];
        }

        $this->attachEvent('request:validate', [$this, 'onRequestValidate']);
    }

    /**
     * @return string
     */
    protected function _generateToken()
    {
        $str = strtr(base64_encode(random_bytes(16)), '+/=', '357');
        return substr($str, 0, $this->_length);
    }

    /**
     * @return string
     */
    public function get()
    {
        if ($this->_useCookie) {
            if (!$this->cookies->has($this->_name)) {
                $this->cookies->set($this->_name, $this->_generateToken(), 0, '/');
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
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return bool
     */
    protected function _isSafe()
    {
        $request = $this->request;
        return $request->isGet() || $request->isOptions() || $request->isHead();
    }

    /**
     * @return void
     * @throws \ManaPHP\Exception\CsrfTokenException
     */
    public function onRequestValidate()
    {
        if ($this->_isSafe()) {
            return;
        }

        if ($this->_useCookie) {
            $token_server = $this->cookies->get($this->_name);
        } else {
            $token_server = $this->session->get($this->_name);
        }
        if ($token_server === null) {
            throw new CsrfTokenException('The CSRF token could not be verified: missing in server');
        } else {
            if ($this->request->has($this->_name)) {
                $token_client = $this->request->get($this->_name);
            } elseif ($this->request->hasServer($this->_header)) {
                $token_client = $this->request->getServer($this->_header);
            } else {
                throw new CsrfTokenException('The CSRF token could not be verified: missing in client');
            }

            if ($token_client !== $token_server) {
                throw new CsrfTokenException('The CSRF token could not be verified: not match');
            }
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }
}