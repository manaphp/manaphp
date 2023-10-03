<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Http\SessionInterface;

class Controller extends \ManaPHP\Http\Controller
{
    #[Autowired] protected ViewInterface $view;
    #[Autowired] protected SessionInterface $session;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected AuthorizationInterface $authorization;
}