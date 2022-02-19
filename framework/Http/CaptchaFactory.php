<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\FactoryInterface;
use ManaPHP\Exception\ExtensionNotInstalledException;
use ManaPHP\Http\Captcha\Adapter\Gd;
use ManaPHP\Http\Captcha\Adapter\Imagick;

class CaptchaFactory implements FactoryInterface
{
    public function make(ContainerInterface $container, string $name, array $parameters = []): object
    {
        if (class_exists('Imagick')) {
            return $container->get(Imagick::class);
        } elseif (function_exists('gd_info')) {
            return $container->get(Gd::class);
        } else {
            throw new ExtensionNotInstalledException('please install `gd` or `imagic` extension first');
        }
    }
}