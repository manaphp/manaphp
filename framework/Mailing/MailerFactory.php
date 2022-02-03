<?php
declare(strict_types=1);

namespace ManaPHP\Mailing;

use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\FactoryInterface;
use ManaPHP\Mailing\Mailer\Adapter\Smtp;

class MailerFactory implements FactoryInterface
{
    public function make(ContainerInterface $container, string $name, array $parameters = []): object
    {
        return $container->get(Smtp::class);
    }
}