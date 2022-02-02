<?php
declare(strict_types=1);

namespace ManaPHP\Di;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends Exception implements NotFoundExceptionInterface
{

}