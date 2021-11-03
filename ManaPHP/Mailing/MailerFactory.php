<?php

namespace ManaPHP\Mailing;

use ManaPHP\Di\FactoryInterface;
use ManaPHP\Mailing\Mailer\Adapter\Smtp;

class MailerFactory implements FactoryInterface
{
    public function make($container, $name, $parameters = [])
    {
        return $container->get(Smtp::class);
    }
}