<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\CookiesInterface;
use ManaPHP\Http\SessionInterface;
use ManaPHP\Mvc\View\FlashInterface;

class Controller extends \ManaPHP\Http\Controller
{
    #[Autowired] protected ViewInterface $view;
    #[Autowired] protected FlashInterface $flash;
    #[Autowired] protected CookiesInterface $cookies;
    #[Autowired] protected SessionInterface $session;
    #[Autowired] protected AuthorizationInterface $authorization;
}