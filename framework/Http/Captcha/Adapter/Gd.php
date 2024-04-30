<?php
declare(strict_types=1);

namespace ManaPHP\Http\Captcha\Adapter;

use ManaPHP\Http\AbstractCaptcha;
use function count;
use function strlen;

class Gd extends AbstractCaptcha
{
    public function draw(string $code, int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);

        $parts = explode(',', $this->bg_rgb);
        $bgColor = imagecolorallocate($image, (int)$parts[0], (int)$parts[1], (int)$parts[2]);

        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        $font_file = $this->alias->resolve($this->fonts[random_int(0, count($this->fonts) - 1)]);

        $x = 0;
        $length = strlen($code);
        for ($i = 0; $i < $length; $i++) {
            $font_size = $this->size + random_int(-$this->size_noise, $this->size_noise);
            $angle = random_int(-$this->angle_noise, $this->angle_noise);
            $fg_color = imagecolorallocate($image, random_int(0, 240), random_int(0, 240), random_int(0, 240));
            $y = $height + random_int(-$this->y_noise, $this->y_noise) - 3;
            $points = imagettftext($image, $font_size, $angle, (int)$x, $y, $fg_color, $font_file, $code[$i]);
            $x += $points[3] + random_int(-$this->x_noise, $this->x_noise) - 3;
        }

        for ($k = 0; $k < $this->char_noise; $k++) {
            $font_file = $this->alias->resolve($this->fonts[random_int(0, count($this->fonts) - 1)]);

            $y = random_int((int)($height * 0.3), (int)($height * 0.7));
            $x = random_int(0, $width) - $this->size;
            $letter = $this->charset[random_int(0, strlen($this->charset) - 1)];
            $fg_color = imagecolorallocate($image, random_int(0, 240), random_int(0, 240), random_int(0, 240));
            $font_size = (int)($this->size / 2 + random_int(-$this->size_noise, $this->size_noise));
            $angle = random_int(-$this->angle_noise, $this->angle_noise);
            imagettftext($image, $font_size, $angle, $x, $y, $fg_color, $font_file, $letter);
        }

        ob_start();
        imagejpeg($image, null, 90);
        $content = ob_get_clean();
        imagedestroy($image);

        return $content;
    }
}