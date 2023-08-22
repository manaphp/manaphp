<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Logging\Logger\LogCategorizable;

class Controller implements LogCategorizable
{
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ResponseInterface $response;
    #[Inject] protected CookiesInterface $cookies;
    #[Inject] protected RouterInterface $router;
    #[Inject] protected DispatcherInterface $dispatcher;
    #[Inject] protected IdentityInterface $identity;

    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Controller');
    }
}
