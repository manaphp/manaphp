<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Http\Captcha\Adapter\Gd;
use ManaPHP\Http\Captcha\Adapter\Imagick;

class Captcha
{
    #[Autowired] protected MakerInterface $maker;

    public function __invoke(array $parameters, ?string $id): mixed
    {
        if (\extension_loaded('imagick')) {
            return $this->maker->make(Imagick::class, $parameters, $id);
        } else {
            return $this->maker->make(Gd::class, $parameters, $id);
        }
    }
}