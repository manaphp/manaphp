<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Http\Globals\Proxy;

/**
 * @property-read \ManaPHP\Http\GlobalsContext $context
 */
class Globals extends Component implements GlobalsInterface
{
    /**
     * @var bool
     */
    protected $proxy = false;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['proxy'])) {
            $this->proxy = $options['proxy'];
        }

        if ($this->proxy) {
            $_GET = new Proxy($this, '_GET');
            $_POST = new Proxy($this, '_POST');
            $_REQUEST = new Proxy($this, '_REQUEST');
            $_FILES = new Proxy($this, '_FILES');
            $_COOKIE = new Proxy($this, '_COOKIE');
            $_SERVER = new Proxy($this, '_SERVER');
        }
    }

    /**
     * @param array  $GET
     * @param array  $POST
     * @param array  $SERVER
     * @param string $RAW_BODY
     * @param array  $COOKIE
     * @param array  $FILES
     *
     * @return void
     */
    public function prepare($GET, $POST, $SERVER, $RAW_BODY = null, $COOKIE = [], $FILES = [])
    {
        $context = $this->context;

        if (!$POST
            && (isset($SERVER['REQUEST_METHOD']) && !in_array($SERVER['REQUEST_METHOD'], ['GET', 'OPTIONS'], true))
        ) {
            if (isset($SERVER['CONTENT_TYPE'])
                && str_contains($SERVER['CONTENT_TYPE'], 'application/json')
            ) {
                $POST = json_parse($RAW_BODY);
            } else {
                parse_str($RAW_BODY, $POST);
            }

            if (!is_array($POST)) {
                $POST = [];
            }
        }

        $context->_GET = $GET;
        $context->_POST = $POST;
        $context->_REQUEST = $POST === [] ? $GET : array_merge($GET, $POST);
        $context->_SERVER = $SERVER;
        $context->rawBody = $RAW_BODY;
        $context->_COOKIE = $COOKIE;
        $context->_FILES = $FILES;
    }

    /**
     * @return \ManaPHP\Http\GlobalsContext
     */
    public function get()
    {
        return $this->context;
    }

    /**
     * @return array
     */
    public function getServer()
    {
        return $this->context->_SERVER;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function setServer($name, $value)
    {
        $this->context->_SERVER[$name] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->context->_FILES;
    }

    /**
     * @return array
     */
    public function getRequest()
    {
        return $this->context->_REQUEST;
    }

    /**
     * @return string
     */
    public function getRawBody()
    {
        return $this->context->rawBody;
    }

    /**
     * @return array
     */
    public function getCookie()
    {
        return $this->context->_COOKIE;
    }

    public function dump()
    {
        $data = parent::dump();

        if (DIRECTORY_SEPARATOR === '\\') {
            foreach (['PATH', 'SystemRoot', 'COMSPEC', 'PATHEXT', 'WINDIR'] as $name) {
                unset($data['context']['_SERVER'][$name]);
            }
        }

        return $data;
    }
}