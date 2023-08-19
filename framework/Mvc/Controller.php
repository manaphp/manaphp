<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\CookiesInterface;
use ManaPHP\Http\SessionInterface;
use ManaPHP\Mvc\View\FlashInterface;

class Controller extends \ManaPHP\Http\Controller
{
    #[Inject] protected ViewInterface $view;
    #[Inject] protected FlashInterface $flash;
    #[Inject] protected CookiesInterface $cookies;
    #[Inject] protected SessionInterface $session;
    #[Inject] protected AuthorizationInterface $authorization;
}