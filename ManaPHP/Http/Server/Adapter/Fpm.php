<?php
namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Component;
use ManaPHP\Http\ServerInterface;

/**
 * Class Fpm
 * @package ManaPHP\Http\Server\Adapter
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
class Fpm extends Component implements ServerInterface
{
    /**
     * @var bool
     */
    protected $_compatible_globals = false;

    /**
     * Fpm constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        if (isset($options['compatible_globals'])) {
            $this->_compatible_globals = (bool)$options['compatible_globals'];
            unset($options['compatible_globals']);
        }
    }

    protected function _prepareGlobals()
    {
        if (!isset($_GET['_url']) && ($pos = strripos($_SERVER['SCRIPT_NAME'], '/public/')) !== false) {
            $prefix = substr($_SERVER['SCRIPT_NAME'], 0, $pos);
            if ($prefix === '' || strpos($_SERVER['REQUEST_URI'], $prefix) === 0) {
                $url = substr($_SERVER['REQUEST_URI'], $pos);
                $_GET['_url'] = $_REQUEST['_url'] = ($pos = strpos($url, '?')) === false ? $url : substr($url, 0, $pos);
            }
        }

        if (!$_POST && isset($_SERVER['REQUEST_METHOD']) && !in_array($_SERVER['REQUEST_METHOD'], ['GET', 'OPTIONS'], true)) {
            $data = file_get_contents('php://input');

            if (isset($_SERVER['CONTENT_TYPE'])
                && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
                $_POST = json_decode($data, true, 16);
            } else {
                parse_str($data, $_POST);
            }

            if (is_array($_POST)) {
                /** @noinspection AdditionOperationOnArraysInspection */
                $_REQUEST = $_POST + $_GET;
            } else {
                $_POST = [];
            }
        }

        $globals = $this->request->getGlobals();

        $globals->_GET = $_GET;
        $globals->_POST = $_POST;
        $globals->_REQUEST = $_REQUEST;
        $globals->_FILES = $_FILES;
        $globals->_COOKIE = $_COOKIE;
        $globals->_SERVER = $_SERVER;

        if (!$this->_compatible_globals) {
            unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);
            foreach ($_SERVER as $k => $v) {
                if (strpos('DOCUMENT_ROOT,SERVER_SOFTWARE,SCRIPT_NAME,SCRIPT_FILENAME', $k) === false) {
                    unset($_SERVER[$k]);
                }
            }
        }

        $GLOBALS['globals'] = $globals;
    }

    /**
     * @param \ManaPHP\Http\Server\HandlerInterface $handler
     *
     * @return static
     */
    public function start($handler)
    {
        $this->_prepareGlobals();

        $handler->handle();

        return $this;
    }

    /**
     * @param \ManaPHP\Http\ResponseInterface $response
     */
    public function send($response)
    {
        $response->send();
    }
}