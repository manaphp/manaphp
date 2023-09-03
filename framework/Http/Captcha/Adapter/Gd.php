<?php
declare(strict_types=1);

namespace ManaPHP\Http\Captcha\Adapter;

use ManaPHP\Http\AbstractCaptcha;

class Gd extends AbstractCaptcha
{
    public function draw(string $code, int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);

        $parts = explode(',', $this->bgRGB);
        $bgColor = imagecolorallocate($image, (int)$parts[0], (int)$parts[1], (int)$parts[2]);

        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        $fontFile = $this->alias->resolve($this->fonts[random_int(0, count($this->fonts) - 1)]);

        $referenceFontSize = min($height, $width / $this->length);

        $x = 0;
        $points[2] = random_int((int)($referenceFontSize * 0.1), (int)($referenceFontSize * 0.3));
        $length = strlen($code);
        for ($i = 0; $i < $length; $i++) {
            $fontSize = $referenceFontSize * random_int(800, 1000) / 1000;
            $angle = random_int(-$this->angleAmplitude, $this->angleAmplitude);
            $x += ($points[2] - $x) - round(random_int((int)($fontSize * 0.1), (int)($fontSize * 0.2)));
            $y = $height - (($height - $referenceFontSize) * random_int(0, 1000) / 1000);
            $fgColor = imagecolorallocate($image, random_int(0, 240), random_int(0, 240), random_int(0, 240));

            $points = imagettftext($image, $fontSize, $angle, (int)$x, (int)$y, $fgColor, $fontFile, $code[$i]);
        }

        for ($k = 0; $k < $this->noiseCharCount; $k++) {
            $letter = $this->charset[random_int(0, strlen($this->charset) - 1)];
            $fgColor = imagecolorallocate($image, random_int(0, 240), random_int(0, 240), random_int(0, 240));
            imagettftext(
                $image,
                $fontSize * 0.4 * $this->rand_amplitude(0.1),
                random_int(-40, 40),
                (int)round($x + random_int((int)(-$fontSize * 1.5), (int)$fontSize)),
                $height / 2 + random_int((int)(-$fontSize * 0.5), (int)($fontSize * 0.5)),
                $fgColor, $fontFile, $letter
            );
        }

        ob_start();
        imagejpeg($image, null, 90);
        $content = ob_get_clean();
        imagedestroy($image);

        return $content;
    }
}