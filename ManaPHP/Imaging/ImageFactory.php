<?php

namespace ManaPHP\Imaging;

use ManaPHP\Di\FactoryInterface;
use ManaPHP\Imaging\Image\Adapter\Gd;
use ManaPHP\Imaging\Image\Adapter\Imagick;

class ImageFactory implements FactoryInterface
{
    public function make($container, $name, $parameters = [])
    {
        return $container->make(extension_loaded('imagick') ? Imagick::class : Gd::class, $parameters);
    }
}