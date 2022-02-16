<?php

namespace App\Middlewares;

use ManaPHP\Http\Middleware;

/**
 * @property-read \ManaPHP\Http\AuthorizationInterface $authorization
 */
class AuthMiddleware extends Middleware
{
    public function onAuthorizing()
    {
        $this->authorization->authorize();
    }
}