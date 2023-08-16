<?php
declare(strict_types=1);

namespace ManaPHP\Imaging;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Imaging\Image\Adapter\Gd;
use ManaPHP\Imaging\Image\Adapter\Imagick;

class Image
{
    #[Inject] protected MakerInterface $maker;

    public function __invoke(array $parameters, ?string $id)
    {
        if (extension_loaded('imagick')) {
            return $this->maker->make(Imagick::class, $parameters, $id);
        } else {
            return $this->maker->make(Gd::class, $parameters, $id);
        }
    }
}