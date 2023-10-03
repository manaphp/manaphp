<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Identifying\IdentityInterface;

class Controller
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected CookiesInterface $cookies;
    #[Autowired] protected IdentityInterface $identity;
}
