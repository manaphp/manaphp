<?php
namespace ManaPHP\Swoole;

use ManaPHP\Mvc\Router\NotFoundRouteException;

/**
 * Class ManaPHP\Mvc\Application
 *
 * @package application
 *
 * @property \ManaPHP\Mvc\ViewInterface           $view
 * @property \ManaPHP\Mvc\Dispatcher              $dispatcher
 * @property \ManaPHP\Mvc\RouterInterface         $router
 * @property \ManaPHP\Http\RequestInterface       $request
 * @property \ManaPHP\Http\ResponseInterface      $response
 * @property \ManaPHP\Http\SessionInterface       $session
 * @property \ManaPHP\Security\CsrfTokenInterface $csrfToken
 * @property \ManaPHP\Http\CookiesInterface       $cookies
 */
class HttpServer extends \ManaPHP\Application
{
    /**
     * @var \swoole_http_server
     */
    protected $_swoole;

    /**
     * @var string
     */
    protected $_listen = 'http://0.0.0.0:9501';

    /**
     * @var \ManaPHP\Mvc\ModuleInterface[]
     */
    protected $_moduleInstances = [];

    /**
     * @var string
     */
    protected $_lastModule = null;

    /**
     * @return bool
     * @throws \ManaPHP\Security\CsrfToken\Exception
     * @throws \ManaPHP\Http\Request\Exception
     * @throws \ManaPHP\Security\Crypt\Exception
     */
    public function onDispatcherBeforeExecuteRoute()
    {
        $r = $this->_moduleInstances[$this->_lastModule]->authorize($this->dispatcher->getControllerName(), $this->dispatcher->getActionName());
        if ($r === false || is_object($r)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Handles a MVC request
     *
     * @param string $uri
     * @param string $method
     *
     * @return \ManaPHP\Http\ResponseInterface
     * @throws \ManaPHP\Mvc\Action\NotFoundException
     * @throws \ManaPHP\Mvc\Action\Exception
     * @throws \ManaPHP\Mvc\Application\Exception
     * @throws \ManaPHP\Event\Exception
     * @throws \ManaPHP\Mvc\Application\NotFoundModuleException
     * @throws \ManaPHP\Mvc\Dispatcher\Exception
     * @throws \ManaPHP\Mvc\Dispatcher\NotFoundControllerException
     * @throws \ManaPHP\Mvc\Dispatcher\NotFoundActionException
     * @throws \ManaPHP\Mvc\View\Exception
     * @throws \ManaPHP\Renderer\Exception
     * @throws \ManaPHP\Alias\Exception
     * @throws \ManaPHP\Mvc\Router\Exception
     * @throws \ManaPHP\Mvc\Router\NotFoundRouteException
     */
    public function handle($uri = null, $method = null)
    {
        for ($i = ob_get_level(); $i > 0; $i--) {
            ob_end_clean();
        }

        ob_start();
        ob_implicit_flush(false);

        $this->debugger->start();

        if (!$this->router->handle($uri, $method)) {
            throw new NotFoundRouteException('router does not have matched route for `:uri`'/**m0980aaf224562f1a4*/, ['uri' => $this->router->getRewriteUri($uri)]);
        }

        $moduleName = $this->router->getModuleName();
        $controllerName = $this->router->getControllerName();
        $actionName = $this->router->getActionName();
        $params = $this->router->getParams();

        if ($this->_lastModule !== $moduleName) {
            $this->alias->set('@module', "@app/$moduleName");
            $this->alias->set('@ns.module', '@ns.app\\' . $moduleName);
            $this->alias->set('@views', '@module/Views');

            if (!isset($this->_moduleInstances[$moduleName])) {
                $moduleClassName = $this->alias->resolveNS('@ns.module\\Module');

                $this->attachEvent('dispatcher:beforeExecuteRoute');

                $this->_moduleInstances[$moduleName] = $this->_dependencyInjector->getShared(class_exists($moduleClassName) ? $moduleClassName : 'ManaPHP\Mvc\Module');
                $this->_moduleInstances[$moduleName]->registerServices($this->_dependencyInjector);
            }

            $this->_lastModule = $moduleName;
        }
        $ret = $this->dispatcher->dispatch($moduleName, $controllerName, $actionName, $params);
        $malformed_message = ob_get_clean();
        if ($ret !== false) {
            $actionReturnValue = $this->dispatcher->getReturnedValue();
            if ($actionReturnValue === null) {
                $this->view->render($this->dispatcher->getControllerName(), $this->dispatcher->getActionName());
                $this->response->setContent($malformed_message . $this->view->getContent());
            }
        }

        return $this->response;
    }

    /**
     * @param \swoole_http_request $request
     */
    protected function _prepareGlobals($request)
    {
        static $server = null;

        if ($server === null) {
            $server = [];
            $script_filename = get_included_files()[0];
            $server['DOCUMENT_ROOT'] = dirname($script_filename);
            $server['SCRIPT_FILENAME'] = $script_filename;
            $server['PHP_SELF'] = $server['SCRIPT_NAME'] = '/' . basename($script_filename);
            $server['QUERY_STRING'] = '';
            $server['REQUEST_SCHEME'] = 'http';
            $parts = explode('-', phpversion());
            $server['SERVER_SOFTWARE'] = 'Swoole/' . SWOOLE_VERSION . ' ' . php_uname('s') . '/' . $parts[1] . ' PHP/' . $parts[0];
        }
        $_SERVER = array_change_key_case($request->server, CASE_UPPER);
        unset($_SERVER['SERVER_SOFTWARE']);

        $_SERVER += $server;

        foreach ($request->header ?: [] as $k => $v) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $k))] = $v;
        }

        $_SERVER['WORKER_ID'] = $this->_swoole->worker_pid;

        $_GET = $request->get ?: [];
        $_POST = $request->post ?: [];

        $_REQUEST = $_POST + $_GET;

        $_COOKIE = $request->cookie ?: [];
        $_FILES = $request->files ?: [];
    }

    /**
     * @param \swoole_http_request  $request
     * @param \swoole_http_response $response
     */
    public function onRequest($request, $response)
    {
        $this->_prepareGlobals($request);
        xdebug_start_trace('/home/mark/manaphp/data/traces/' . date('Ymd_His_') . mt_rand(1000, 9999) . '.trace');
        if (1) {
            $this->handle();
            $content = $this->response->getContent();
            //      $this->debugger->save();
        } else {
            $content = json_encode(['time' => date('Y-m-d H:i:s')]);
        }
        $this->response->setHeader('worker-id', $_SERVER['WORKER_ID']);
        foreach ($this->response->getHeaders() as $k => $v) {
            $response->header($k, $v);
        }

        $this->fireEvent('cookies:beforeSend');
        foreach ($this->cookies->getSent() as $cookie) {
            $response->cookie($cookie['name'], $cookie['value'], $cookie['expire'],
                $cookie['path'], $cookie['domain'], $cookie['secure'],
                $cookie['httpOnly']);
        }
        $this->fireEvent('cookies:afterSend');

        $this->_dependencyInjector->reConstruct();
        xdebug_stop_trace();
        $response->end($content);
    }

    /**
     * @param \swoole_http_server $swoole
     */
    protected function _prepareSwoole($swoole)
    {

    }

    public function main()
    {
        $this->registerServices();

        $parts = parse_url($this->_listen);
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        if (isset($parts['port'])) {
            $port = $parts['port'];
        } else {
            $port = ($scheme === 'http' ? 80 : 443);
        }

        if ($scheme === 'http') {
            $this->_swoole = new \swoole_http_server($host, $port);
        } else {
            $this->_swoole = new \swoole_http_server($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_TCP);
        }

        $this->_prepareSwoole($this->_swoole);

        $this->_swoole->on('request', [$this, 'onRequest']);

        $this->_swoole->start();
    }
}