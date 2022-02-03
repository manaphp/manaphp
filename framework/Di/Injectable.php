<?php
declare(strict_types=1);

namespace ManaPHP\Di;

interface Injectable
{
    public function setContainer(ContainerInterface $container): void;
}