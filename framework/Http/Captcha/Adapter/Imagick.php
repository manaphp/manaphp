<?php
declare(strict_types=1);

namespace ManaPHP\Http\Captcha\Adapter;

use ImagickDraw;
use ImagickPixel;
use ManaPHP\Http\AbstractCaptcha;

class Imagick extends AbstractCaptcha
{
    public function draw(string $code, int $width, int $height): string
    {
        $image = new \Imagick();
        $draw = new ImagickDraw();
        $image->newImage($width, $height, new ImagickPixel('rgb(' . $this->bg_rgb . ')'));
        $draw->setFont($this->alias->resolve($this->fonts[random_int(0, \count($this->fonts) - 1)]));
        $draw->setGravity(\Imagick::GRAVITY_NORTHWEST);

        $referenceFontSize = min($height, $width / $this->length);

        $x = random_int((int)($referenceFontSize * 0.1), (int)($referenceFontSize * 0.3));
        $length = \strlen($code);
        $fgPixel = new ImagickPixel();
        $font_size = $referenceFontSize;

        for ($i = 0; $i < $length; $i++) {
            $font_size = $referenceFontSize * random_int(800, 1000) / 1000;
            $draw->setFontSize($font_size);
            $fgPixel->setColor('rgb(' . random_int(0, 240) . ',' . random_int(0, 240) . ',' . random_int(0, 240) . ')');
            $draw->setFillColor($fgPixel);
            $angle = random_int(-$this->angle_noise, $this->angle_noise);
            $y = ($height - $referenceFontSize) * random_int(-1000, 1000) / 1000;
            $image->annotateImage($draw, $x, $y, $angle, $code[$i]);
            $x += $font_size * random_int(600, 800) / 1000;
        }

        for ($k = 0; $k < $this->char_noise; $k++) {
            $letter = $this->charset[random_int(0, \strlen($this->charset) - 1)];
            $red = random_int(0, 240);
            $green = random_int(0, 240);
            $blue = random_int(0, 240);
            $fgPixel->setColor("rgb($red,$green,$blue)");
            $draw->setFillColor($fgPixel);
            $draw->setFontSize($font_size * 0.4 * $this->rand_amplitude(0.1));
            $angle = random_int(-$this->angle_noise, $this->angle_noise);
            $noise_x = $x + random_int(-700, 700) / 1000 * $font_size;
            $noise_y = $font_size / 2 + random_int((int)(-$font_size * 0.5), (int)($font_size * 0.5));
            $image->annotateImage($draw, $noise_x, $noise_y, $angle, $letter);
        }

        $image->setImageFormat('jpeg');
        $content = (string)$image;
        $image->destroy();
        $fgPixel->destroy();
        $draw->destroy();

        return $content;
    }
}