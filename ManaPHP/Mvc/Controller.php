<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;

/**
 * ManaPHP\Mvc\Controller
 *
 * Every application controller should extend this class that encapsulates all the controller functionality
 *
 * The controllers provide the “flow” between models and views. Controllers are responsible
 * for processing the incoming requests from the web browser, interrogating the models for data,
 * and passing that data on to the views for presentation.
 *
 *<code>
 *
 *class PeopleController extends \ManaPHP\Mvc\Controller
 *{
 *
 *  //This action will be executed by default
 *  public function indexAction()
 *  {
 *
 *  }
 *
 *  public function findAction()
 *  {
 *
 *  }
 *
 *  public function saveAction()
 *  {
 *   //Forwards flow to the index action
 *   return $this->dispatcher->forward(array('controller' => 'people', 'action' => 'index'));
 *  }
 *
 *}
 *
 *</code>
 *
 * @method void initialize();
 * @method bool beforeExecuteRoute(DispatcherInterface $dispatcher);
 * @method bool afterExecuteRoute(DispatcherInterface $dispatcher);
 * @method onConstruct();
 *
 *
 * @property \ManaPHP\Mvc\View                    $view
 * @property \ManaPHP\Mvc\View\Flash              $flash
 * @property \ManaPHP\Mvc\View\Flash              $flashSession
 * @property \ManaPHP\Security\Captcha            $captcha
 * @property \ManaPHP\Http\Client                 $httpClient
 * @property \ManaPHP\Authentication\Password     $password
 * @property \ManaPHP\Http\Cookies                $cookies
 * @property \ManaPHP\Mvc\Model\Manager           $modelsManager
 * @property \ManaPHP\Counter                     $counter
 * @property \ManaPHP\Cache                       $cache
 * @property \ManaPHP\Db                          $db
 * @property \ManaPHP\Authentication\UserIdentity $userIdentity
 * @property \ManaPHP\Http\Request                $request
 * @property \ManaPHP\Http\Response               $response
 * @property \ManaPHP\Security\Crypt              $crypt
 * @property \ManaPHP\Http\Session\Bag            $persistent
 * @property \ManaPHP\Di|\ManaPHP\DiInterface     $di
 * @property \ManaPHP\Mvc\Dispatcher              $dispatcher
 * @property \ManaPHP\Log\Logger                  $logger
 * @property \Application\Configure               $configure
 * @property \ManaPHP\Http\Session                $session
 * @property \ManaPHP\Security\CsrfToken          $csrfToken
 * @property \ManaPHP\Paginator                   $paginator
 * @property \ManaPHP\Cache                       $viewsCache
 */
abstract class Controller extends Component implements ControllerInterface
{
    /**
     * @var array
     */
    protected $_cacheOptions = [];

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
     * @param string $action
     *
     * @return string|false
     */
    public function getCachedResponse($action)
    {
        if (isset($this->_cacheOptions[$action])) {
            $cacheOptions = $this->_getCacheOptions($this->_cacheOptions[$action], $action);
            if (is_array($cacheOptions)) {
                return $this->viewsCache->get($cacheOptions['key']);
            }
        }

        return false;
    }

    /**
     * @param string $action
     * @param string $content
     *
     * @return void
     */
    public function setCachedResponse($action, $content)
    {
        if (isset($this->_cacheOptions[$action])) {
            $cacheOptions = $this->_getCacheOptions($this->_cacheOptions[$action], $action);
            if (is_array($cacheOptions)) {
                $this->viewsCache->set($cacheOptions['key'], $content, $cacheOptions['ttl']);
            }
        }
    }

    /**
     * @param $cacheOptions
     * @param $action
     *
     * @return array|false
     */
    protected function _getCacheOptions($cacheOptions, $action)
    {
        if (is_array($cacheOptions)) {
            $_cacheOptions = $cacheOptions;
        } else {
            $_cacheOptions = ['ttl' => $cacheOptions];
        }

        $parts = explode('\\', get_called_class());
        $prefix = '/' . $parts[1] . '/Views/' . basename($parts[3], 'Controller') . '/' . ucfirst($action);

        if (isset($_cacheOptions['key'])) {
            if (is_callable($_cacheOptions['key'])) {
                $key = $_cacheOptions['key']($this);
                if (!is_string($key)) {
                    return false;
                }
                $_cacheOptions['key'] = $prefix . '/' . $key;
            } else {
                $_cacheOptions['key'] = $prefix . '/' . $_cacheOptions['key'];
            }
        } else {
            $_cacheOptions['key'] = $prefix;
        }

        return $_cacheOptions;
    }
}