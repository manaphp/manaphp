<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Mvc\Controller\Exception as ControllerException;

/**
 * Class ManaPHP\Mvc\Controller
 *
 * @package controller
 *
 * @method void initialize();
 * @method bool beforeExecuteRoute();
 * @method bool afterExecuteRoute();
 * @method void onConstruct();
 *
 *
 * @property \ManaPHP\Mvc\ViewInterface                    $view
 * @property \ManaPHP\Mvc\View\FlashInterface              $flash
 * @property \ManaPHP\Mvc\View\FlashInterface              $flashSession
 * @property \ManaPHP\Security\CaptchaInterface            $captcha
 * @property \ManaPHP\Http\ClientInterface                 $httpClient
 * @property \ManaPHP\Authentication\PasswordInterface     $password
 * @property \ManaPHP\Http\CookiesInterface                $cookies
 * @property \ManaPHP\CounterInterface                     $counter
 * @property \ManaPHP\CacheInterface                       $cache
 * @property \ManaPHP\DbInterface                          $db
 * @property \ManaPHP\Authentication\UserIdentityInterface $userIdentity
 * @property \ManaPHP\Http\RequestInterface                $request
 * @property \ManaPHP\Http\ResponseInterface               $response
 * @property \ManaPHP\Security\CryptInterface              $crypt
 * @property \ManaPHP\Http\Session\BagInterface            $persistent
 * @property \ManaPHP\Mvc\DispatcherInterface              $dispatcher
 * @property \ManaPHP\LoggerInterface                      $logger
 * @property \Application\Configure                        $configure
 * @property \ManaPHP\Http\SessionInterface                $session
 * @property \ManaPHP\Security\CsrfTokenInterface          $csrfToken
 * @property \ManaPHP\Paginator                            $paginator
 * @property \ManaPHP\Cache\AdapterInterface               $viewsCache
 * @property \ManaPHP\FilesystemInterface                  $filesystem
 * @property \ManaPHP\Security\RandomInterface             $random
 * @property \ManaPHP\Message\QueueInterface               $messageQueue
 * @property \ManaPHP\Security\RateLimiterInterface        $rateLimiter
 * @property \ManaPHP\Meter\LinearInterface                $linearMeter
 * @property \ManaPHP\Meter\RoundInterface                 $roundMeter
 * @property \ManaPHP\Security\SecintInterface             $secint
 * @property \ManaPHP\Http\FilterInterface                 $filter
 * @property \ManaPHP\Db\Model\MetadataInterface           $modelsMetadata
 * @property \ManaPHP\Mvc\UrlInterface                     $url
 * @property \ManaPHP\StopwatchInterface                   $stopwatch
 * @property \ManaPHP\Security\HtmlPurifierInterface       $htmlPurifier
 * @property \ManaPHP\Cli\EnvironmentInterface             $environment
 * @property \ManaPHP\Net\ConnectivityInterface            $netConnectivity
 * @property \ManaPHP\Redis                                $redis
 * @property \ManaPHP\Mongodb                              $mongodb
 * @property \Elasticsearch\Client                         $elasticsearch
 * @property \ManaPHP\ZookeeperInterface                   $zookeeper
 * @property \ManaPHP\Db\QueryInterface                    $dbQuery
 * @property \ManaPHP\Mvc\RouterInterface                  $router
 */
abstract class Controller extends Component implements ControllerInterface
{
    /**
     * @var array
     */
    protected $_actions;

    /**
     * \ManaPHP\Mvc\Controller constructor
     *
     */
    final public function __construct()
    {
        if (method_exists($this, 'onConstruct')) {
            $this->{'onConstruct'}();
        }
    }

    /**
     * @return array
     */
    public function actionList()
    {
        if ($this->_actions === null) {
            $this->_actions = [];
            foreach (get_class_methods($this) as $method) {
                if ($method[0] !== '_' && ($pos = strrpos($method, 'Action')) !== false && $pos + 6 === strlen($method)) {
                    $action = substr($method, 0, -6);

                    $this->_actions[] = $action;
                }
            }
        }

        return $this->_actions;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function actionExists($name)
    {
        return in_array($name, $this->_actions !== null ? $this->_actions : $this->actionList(), true);
    }

    /**
     * @param string $action
     * @param array  $params
     *
     * @return mixed
     * @throws \ManaPHP\Mvc\Controller\Exception
     */
    public function actionInvoke($action, $params = [])
    {
        $actionMethod = $action . 'Action';

        $args = [];
        $missing = [];

        $parameters = (new \ReflectionMethod($this, $actionMethod))->getParameters();
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $value = null;
            $type = $parameter->getClass();

            if ($type !== null && is_subclass_of($type->getName(), Component::class)) {
                $value = $this->_dependencyInjector->get($type->getName());
            } elseif (isset($params[$name])) {
                $value = $params[$name];
            } elseif ($this->request->has($name)) {
                $value = $this->request->get($name);
            } elseif ($this->request->hasJson($name)) {
                $value = $this->request->getJson($name);
            } elseif (count($params) === 1 && count($parameters) === 1) {
                $value = $params[0];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            }

            if ($value === null) {
                $missing[] = $name;
                continue;
            }

            if ($parameter->isArray()) {
                $args[] = (array)$value;
            } else {
                $args[] = $value;
            }
        }

        if (count($missing) !== 0) {
            throw new ControllerException('Missing required parameters: `:parameters`', ['parameters' => implode(',', $missing)]);
        }

        return call_user_func_array([$this, $actionMethod], $args);
    }
}