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
 */
abstract class Controller extends Component implements ControllerInterface
{

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

}