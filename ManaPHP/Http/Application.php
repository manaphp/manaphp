<?php
namespace ManaPHP\Http;

use ManaPHP\Http\Server\RequestHandlerInterface;

/**
 * Class Application
 * @property-read \ManaPHP\Http\ServerInterface   $httpServer
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\RouterInterface        $router
 * @property-read \ManaPHP\DispatcherInterface    $dispatcher
 * @property-read \ManaPHP\ViewInterface          $view
 * @property-read \ManaPHP\Http\SessionInterface  $session
 *
 * @package ManaPHP\Http
 * @method void authorize()
 */
abstract class Application extends \ManaPHP\Application implements RequestHandlerInterface
{
    public function __construct($loader = null)
    {
        parent::__construct($loader);

        $this->eventsManager->attachEvent('request:begin', [$this, 'generateRequestId']);
        $this->eventsManager->attachEvent('request:authenticate', [$this, 'authenticate']);

        if (method_exists($this, 'authorize')) {
            $this->eventsManager->attachEvent('request:authorize', [$this, 'authorize']);
        }

        $this->getDi()->setShared('httpServer', PHP_SAPI === 'cli' ? 'ManaPHP\Http\Server\Adapter\Swoole' : 'ManaPHP\Http\Server\Adapter\Fpm');
    }

    public function authenticate()
    {
        $this->identity->authenticate();
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

    abstract public function handle();

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();

        $this->httpServer->start($this);
    }
}