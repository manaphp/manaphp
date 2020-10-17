<?php

namespace ManaPHP\Security;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use ManaPHP\Component;
use ManaPHP\Exception\ExtensionNotInstalledException;
use ManaPHP\Security\Captcha\InvalidCaptchaException;

/**
 * Class ManaPHP\Security\Captcha
 *
 * @package captcha
 *
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\SessionInterface  $session
 */
class Captcha extends Component implements CaptchaInterface
{
    /**
     * @var string
     */
    protected /** @noinspection SpellCheckingInspection */
        $_charset = '23456789abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY';

    /**
     * @var array
     */
    protected $_fonts = [];

    /**
     * @var string
     */
    protected $_sessionVar = 'captcha';

    /**
     * @var int
     */
    protected $_angleAmplitude = 30;

    /**
     * @var int
     */
    protected $_noiseCharCount = 1;

    /**
     * @var string
     */
    protected $_bgRGB = '255,255,255';

    /**
     * @var int
     */
    protected $_length = 4;

    /**
     * @var int
     */
    protected $_minInterval = 1;

    /**
     * Captcha constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['charset'])) {
            $this->_charset = $options['charset'];
        }

        if (!isset($options['fonts'])) {
            $options['fonts'] = [
                '@manaphp/Security/Captcha/Fonts/AirbusSpecial.ttf',
                '@manaphp/Security/Captcha/Fonts/StencilFour.ttf',
                '@manaphp/Security/Captcha/Fonts/SpicyRice.ttf'
            ];
        }
        $this->_fonts = $options['fonts'];

        if (isset($options['length'])) {
            $this->_length = $options['length'];
        }

        if (isset($options['bgRGB'])) {
            $this->_bgRGB = $options['bgRGB'];
        }
    }

    /**
     * @param int $count
     *
     * @return static
     */
    public function setNoiseCharCount($count)
    {
        $this->_noiseCharCount = $count;

        return $this;
    }

    /**
     * @param float $a
     *
     * @return float
     */
    protected function _rand_amplitude($a)
    {
        return random_int((1 - $a) * 10000, (1 + $a) * 10000) / 10000;
    }

    /**
     * @param string $code
     * @param int    $width
     * @param int    $height
     *
     * @return string
     */
    protected function _generateByGd($code, $width, $height)
    {
        $image = imagecreatetruecolor($width, $height);

        $parts = explode(',', $this->_bgRGB);
        $bgColor = imagecolorallocate($image, $parts[0], $parts[1], $parts[2]);

        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        $fontFile = $this->alias->resolve($this->_fonts[random_int(0, count($this->_fonts) - 1)]);

        $referenceFontSize = min($height, $width / $this->_length);

        $x = 0;
        $points[2] = random_int($referenceFontSize * 0.1, $referenceFontSize * 0.3);
        $length = strlen($code);
        for ($i = 0; $i < $length; $i++) {
            $fontSize = $referenceFontSize * random_int(800, 1000) / 1000;
            $angle = random_int(-$this->_angleAmplitude, $this->_angleAmplitude);
            $x += ($points[2] - $x) - round(random_int($fontSize * 0.1, $fontSize * 0.2));
            $y = $height - (($height - $referenceFontSize) * random_int(0, 1000) / 1000);
            $fgColor = imagecolorallocate($image, random_int(0, 240), random_int(0, 240), random_int(0, 240));

            $points = imagettftext($image, $fontSize, $angle, $x, $y, $fgColor, $fontFile, $code[$i]);

            for ($k = 0; $k < $this->_noiseCharCount; $k++) {
                $letter = $this->_charset[random_int(0, strlen($this->_charset) - 1)];
                $fgColor = imagecolorallocate($image, random_int(0, 240), random_int(0, 240), random_int(0, 240));
                imagettftext($image,
                    $fontSize * 0.4 * $this->_rand_amplitude(0.1),
                    random_int(-40, 40),
                    round($x + random_int(-$fontSize * 1.5, $fontSize)),
                    $height / 2 + random_int(-$fontSize * 0.5, $fontSize * 0.5),
                    $fgColor, $fontFile, $letter);
            }
        }

        $this->response->setContentType('image/jpeg');

        ob_start();
        imagejpeg($image, null, 90);
        $this->response->setContent(ob_get_clean());
        imagedestroy($image);

        return $this->response;
    }

    /**
     * @param string $code
     * @param int    $width
     * @param int    $height
     *
     * @return \ManaPHP\Http\ResponseInterface
     */
    protected function _generateByImagic($code, $width, $height)
    {
        $image = new Imagick();
        $draw = new ImagickDraw();
        $image->newImage($width, $height, new ImagickPixel('rgb(' . $this->_bgRGB . ')'));
        $draw->setFont($this->alias->resolve($this->_fonts[random_int(0, count($this->_fonts) - 1)]));
        $draw->setGravity(Imagick::GRAVITY_NORTHWEST);

        $referenceFontSize = min($height, $width / $this->_length);

        $x = random_int($referenceFontSize * 0.1, $referenceFontSize * 0.3);
        $length = strlen($code);
        $fgPixel = new ImagickPixel();
        for ($i = 0; $i < $length; $i++) {
            $fontSize = $referenceFontSize * random_int(800, 1000) / 1000;
            $draw->setFontSize($fontSize);
            $fgPixel->setColor('rgb(' . random_int(0, 240) . ',' . random_int(0, 240) . ',' . random_int(0, 240) . ')');
            $draw->setFillColor($fgPixel);
            $angle = random_int(-$this->_angleAmplitude, $this->_angleAmplitude);
            $y = ($height - $referenceFontSize) * random_int(-1000, 1000) / 1000;
            $image->annotateImage($draw, $x, $y, $angle, $code[$i]);
            $x += $fontSize * random_int(600, 800) / 1000;

            for ($k = 0; $k < $this->_noiseCharCount; $k++) {
                $letter = $this->_charset[random_int(0, strlen($this->_charset) - 1)];
                $red = random_int(0, 240);
                $green = random_int(0, 240);
                $blue = random_int(0, 240);
                $fgPixel->setColor("rgb($red,$green,$blue)");
                $draw->setFillColor($fgPixel);
                $draw->setFontSize($fontSize * 0.4 * $this->_rand_amplitude(0.1));
                $angle = random_int(-40, 40);
                $noise_x = $x + random_int(-700, 700) / 1000 * $fontSize;
                $noise_y = $fontSize / 2 + random_int(-$fontSize * 0.5, $fontSize * 0.5);
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

    /**
     * @param int $width
     * @param int $height
     * @param int $ttl
     *
     * @return \ManaPHP\Http\ResponseInterface
     */
    public function generate($width = 100, $height = 30, $ttl = 300)
    {
        $code = '';
        $charsetCount = strlen($this->_charset);
        for ($i = 0; $i < $this->_length; $i++) {
            $code .= $this->_charset[random_int(0, $charsetCount - 1)];
        }

        if (class_exists('Imagick')) {
            $response = $this->_generateByImagic($code, $width, $height);
        } elseif (function_exists('gd_info')) {
            $response = $this->_generateByGd($code, $width, $height);
        } else {
            throw new ExtensionNotInstalledException('`captcha` service is not support, please install `gd` or `imagic` extension first');
        }

        $captchaData = ['code' => $code, 'created_time' => time(), 'ttl' => $ttl];
        $this->session->set($this->_sessionVar, $captchaData);
        return $response;
    }

    /**
     * @param string $code
     * @param bool   $isTry
     *
     * @return void
     * @throws \ManaPHP\Security\Captcha\InvalidCaptchaException
     */
    public function verify($code = null, $isTry = false)
    {
        if ($code === null) {
            $code = $this->request->get('code');
        }

        if (!$this->session->has($this->_sessionVar)) {
            throw new InvalidCaptchaException('captcha is not exist in server');
        }

        $sessionVar = $this->session->get($this->_sessionVar);

        if ($isTry) {
            if (isset($sessionVar['try_verified_time'])) {
                $this->session->remove($this->_sessionVar);
                throw new InvalidCaptchaException('captcha has been tried');
            } else {
                $sessionVar['try_verified_time'] = time();
                $this->session->set($this->_sessionVar, $sessionVar);
            }
        } else {
            $this->session->remove($this->_sessionVar);
        }

        if (time() - $sessionVar['created_time'] < $this->_minInterval) {
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