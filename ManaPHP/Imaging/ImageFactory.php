<?php
declare(strict_types=1);

namespace ManaPHP\Imaging;

use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\FactoryInterface;
use ManaPHP\Imaging\Image\Adapter\Gd;
use ManaPHP\Imaging\Image\Adapter\Imagick;

class ImageFactory implements FactoryInterface
{
    public function make(ContainerInterface $container, string $name, array $parameters = []): object
    {
        return $container->make(extension_loaded('imagick') ? Imagick::class : Gd::class, $parameters);
    }
}