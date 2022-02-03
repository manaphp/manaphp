<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use ManaPHP\Component;
use ManaPHP\Exception\ExtensionNotInstalledException;
use ManaPHP\Http\Captcha\InvalidCaptchaException;

/**
 * @property-read \ManaPHP\AliasInterface         $alias
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\SessionInterface  $session
 */
class Captcha extends Component implements CaptchaInterface
{
    protected string $charset = '23456789abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY';
    protected array $fonts = [];
    protected string $sessionVar = 'captcha';
    protected int $angleAmplitude = 30;
    protected int $noiseCharCount = 1;
    protected string $bgRGB = '255,255,255';
    protected int $length = 4;
    protected int $minInterval = 1;

    public function __construct(array $options = [])
    {
        if (isset($options['charset'])) {
            $this->charset = $options['charset'];
        }

        if (!isset($options['fonts'])) {
            $options['fonts'] = [
                '@manaphp/Http/Captcha/Fonts/AirbusSpecial.ttf',
                '@manaphp/Http/Captcha/Fonts/StencilFour.ttf',
                '@manaphp/Http/Captcha/Fonts/SpicyRice.ttf'
            ];
        }
        $this->fonts = $options['fonts'];

        if (isset($options['length'])) {
            $this->length = $options['length'];
        }

        if (isset($options['bgRGB'])) {
            $this->bgRGB = $options['bgRGB'];
        }
    }

    public function setNoiseCharCount(int $count): static
    {
        $this->noiseCharCount = $count;

        return $this;
    }

    protected function rand_amplitude(float $a): float
    {
        return random_int((1 - $a) * 10000, (1 + $a) * 10000) / 10000;
    }

    protected function generateByGd(string $code, int $width, int $height): ResponseInterface
    {
        $image = imagecreatetruecolor($width, $height);

        $parts = explode(',', $this->bgRGB);
        $bgColor = imagecolorallocate($image, $parts[0], $parts[1], $parts[2]);

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

            $points = imagettftext($image, $fontSize, $angle, $x, $y, $fgColor, $fontFile, $code[$i]);

            for ($k = 0; $k < $this->noiseCharCount; $k++) {
                $letter = $this->charset[random_int(0, strlen($this->charset) - 1)];
                $fgColor = imagecolorallocate($image, random_int(0, 240), random_int(0, 240), random_int(0, 240));
                imagettftext(
                    $image,
                    $fontSize * 0.4 * $this->rand_amplitude(0.1),
                    random_int(-40, 40),
                    round($x + random_int((int)(-$fontSize * 1.5), $fontSize)),
                    $height / 2 + random_int((int)(-$fontSize * 0.5), (int)($fontSize * 0.5)),
                    $fgColor, $fontFile, $letter
                );
            }
        }

        $this->response->setContentType('image/jpeg');

        ob_start();
        imagejpeg($image, null, 90);
        $this->response->setContent(ob_get_clean());
        imagedestroy($image);

        return $this->response;
    }

    protected function generateByImagic(string $code, int $width, int $height): ResponseInterface
    {
        $image = new Imagick();
        $draw = new ImagickDraw();
        $image->newImage($width, $height, new ImagickPixel('rgb(' . $this->bgRGB . ')'));
        $draw->setFont($this->alias->resolve($this->fonts[random_int(0, count($this->fonts) - 1)]));
        $draw->setGravity(Imagick::GRAVITY_NORTHWEST);

        $referenceFontSize = min($height, $width / $this->length);

        $x = random_int((int)($referenceFontSize * 0.1), (int)($referenceFontSize * 0.3));
        $length = strlen($code);
        $fgPixel = new ImagickPixel();
        for ($i = 0; $i < $length; $i++) {
            $fontSize = $referenceFontSize * random_int(800, 1000) / 1000;
            $draw->setFontSize($fontSize);
            $fgPixel->setColor('rgb(' . random_int(0, 240) . ',' . random_int(0, 240) . ',' . random_int(0, 240) . ')');
            $draw->setFillColor($fgPixel);
            $angle = random_int(-$this->angleAmplitude, $this->angleAmplitude);
            $y = ($height - $referenceFontSize) * random_int(-1000, 1000) / 1000;
            $image->annotateImage($draw, $x, $y, $angle, $code[$i]);
            $x += $fontSize * random_int(600, 800) / 1000;

            for ($k = 0; $k < $this->noiseCharCount; $k++) {
                $letter = $this->charset[random_int(0, strlen($this->charset) - 1)];
                $red = random_int(0, 240);
                $green = random_int(0, 240);
                $blue = random_int(0, 240);
                $fgPixel->setColor("rgb($red,$green,$blue)");
                $draw->setFillColor($fgPixel);
                $draw->setFontSize($fontSize * 0.4 * $this->rand_amplitude(0.1));
                $angle = random_int(-40, 40);
                $noise_x = $x + random_int(-700, 700) / 1000 * $fontSize;
                $noise_y = $fontSize / 2 + random_int((int)(-$fontSize * 0.5), (int)($fontSize * 0.5));
                $image->annotateImage($draw, $noise_x, $noise_y, $angle, $letter);
            }
        }

        $this->response->setContentType('image/jpeg');
        $image->setImageFormat('jpeg');
        $this->response->setContent((string)$image);
        $image->destroy();
        $fgPixel->destroy();
        $draw->destroy();

        return $this->response;
    }

    public function generate(int $width = 100, int $height = 30, int $ttl = 300): ResponseInterface
    {
        $code = '';
        $charsetCount = strlen($this->charset);
        for ($i = 0; $i < $this->length; $i++) {
            $code .= $this->charset[random_int(0, $charsetCount - 1)];
        }

        if (class_exists('Imagick')) {
            $response = $this->generateByImagic($code, $width, $height);
        } elseif (function_exists('gd_info')) {
            $response = $this->generateByGd($code, $width, $height);
        } else {
            throw new ExtensionNotInstalledException('please install `gd` or `imagic` extension first');
        }

        $captchaData = ['code' => $code, 'created_time' => time(), 'ttl' => $ttl];
        $this->session->set($this->sessionVar, $captchaData);
        return $response;
    }

    public function verify(?string $code = null, bool $isTry = false): void
    {
        if ($code === null) {
            $code = $this->request->get('code');
        }

        if (!$this->session->has($this->sessionVar)) {
            throw new InvalidCaptchaException('captcha is not exist in server');
        }

        $sessionVar = $this->session->get($this->sessionVar);

        if ($isTry) {
            if (isset($sessionVar['try_verified_time'])) {
                $this->session->remove($this->sessionVar);
                throw new InvalidCaptchaException('captcha has been tried');
            } else {
                $sessionVar['try_verified_time'] = time();
                $this->session->set($this->sessionVar, $sessionVar);
            }
        } else {
            $this->session->remove($this->sessionVar);
        }

        if (time() - $sessionVar['created_time'] < $this->minInterval) {
            throw new InvalidCaptchaException('captcha verification is too frequency');
        }

        if (time() - $sessionVar['created_time'] > $sessionVar['ttl']) {
            throw new InvalidCaptchaException('captcha is expired');
        }

        if (strtolower($sessionVar['code']) !== strtolower($code)) {
            throw new InvalidCaptchaException('captcha is not match');
        }
    }
}