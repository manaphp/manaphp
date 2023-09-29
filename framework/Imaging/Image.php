<?php
declare(strict_types=1);

namespace ManaPHP\Imaging;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Imaging\Image\Adapter\Gd;
use ManaPHP\Imaging\Image\Adapter\Imagick;

class Image
{
    #[Autowired] protected MakerInterface $maker;

    public function __invoke(array $parameters, ?string $id): mixed
    {
        if (extension_loaded('imagick')) {
            return $this->maker->make(Imagick::class, $parameters, $id);
        } else {
            return $this->maker->make(Gd::class, $parameters, $id);
        }
    }
}