<?php
namespace ManaPHP\Http;

use Swoole\Runtime;

/**
 * Class Application
 * @property-read \ManaPHP\Swoole\Http\ServerInterface $swooleHttpServer
 * @property-read \ManaPHP\Http\RequestInterface       $request
 * @property-read \ManaPHP\Http\ResponseInterface      $response
 * @property-read \ManaPHP\RouterInterface             $router
 * @property-read \ManaPHP\DispatcherInterface         $dispatcher
 * @property-read \ManaPHP\ViewInterface               $view
 * @property-read \ManaPHP\Http\SessionInterface       $session
 *
 * @package ManaPHP\Http
 * @method void authenticate()
 * @method void authorize()
 */
abstract class Application extends \ManaPHP\Application
{
    /**
     * @var bool
     */
    protected $_use_swoole = false;

    public function __construct($loader = null)
    {
        parent::__construct($loader);

        $this->eventsManager->attachEvent('request:begin', [$this, 'generateRequestId']);

        if (method_exists($this, 'authenticate')) {
            $this->eventsManager->attachEvent('request:authenticate', [$this, 'authenticate']);
        }

        if (method_exists($this, 'authorize')) {
            $this->eventsManager->attachEvent('request:authorize', [$this, 'authorize']);
        }

        $this->_use_swoole = PHP_SAPI === 'cli';
    }

    public function generateRequestId()
    {
        if (!$this->request->hasServer('HTTP_X_REQUEST_ID')) {
            if (function_exists('random_bytes')) {
                $request_id = random_bytes(15);
            } else {
                $request_id = substr(md5(microtime() . mt_rand(), true), 0, 15);
            }

            $globals = $this->request->getGlobals();

            $globals->_SERVER['HTTP_X_REQUEST_ID'] = 'aa' . bin2hex($request_id);
        }
    }

    protected function _prepareGlobals()
    {
        $globals = $this->request->getGlobals();

        $globals->_GET = $_GET;
        $globals->_POST = $_POST;
        $globals->_REQUEST = $_REQUEST;
        $globals->_FILES = $_FILES;
        $globals->_COOKIE = $_COOKIE;
        $globals->_SERVER = $_SERVER;

        if (!$this->configure->compatible_globals) {
            unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);
            foreach ($_SERVER as $k => $v) {
                if (strpos('DOCUMENT_ROOT,SERVER_SOFTWARE,SCRIPT_NAME,SCRIPT_FILENAME', $k) === false) {
                    unset($_SERVER[$k]);
                }
            }
        }
    }

    public function send()
    {
        if ($this->_use_swoole) {
            $this->swooleHttpServer->send($this->response);
        } else {
            $this->response->send();
        }
    }

    abstract public function handle();

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        if (MANAPHP_COROUTINE) {
            Runtime::enableCoroutine();
        }

        $this->registerServices();

        if ($this->_use_swoole) {
            $this->swooleHttpServer->start([$this, 'handle']);
        } else {
            $this->_prepareGlobals();
            $this->handle();
        }
    }
}