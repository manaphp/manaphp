<?php
namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Http\Server;

/**
 * Class Php
 * @package ManaPHP\Http\Server\Adapter
 */
class Php extends Server
{
    /**
     * Fpm constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        $public_dir = $this->alias->resolve('@public');
        if (PHP_SAPI === 'cli') {
            $index = @get_included_files()[0];
            $cmd = "php -S $this->_host:$this->_port  -d opcache.enable_cli=on -t $public_dir  $index";
            echo $cmd, PHP_EOL;
            shell_exec($cmd);
            exit(0);
        } else {
            $_SERVER['SERVER_ADDR'] = $this->_host;
            $_SERVER['SERVER_PORT'] = $this->_port;
            $_SERVER['REQUEST_SCHEME'] = 'http';
            $_GET['_url'] = $_REQUEST['_url'] = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        }
    }

    protected function _prepareGlobals()
    {
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