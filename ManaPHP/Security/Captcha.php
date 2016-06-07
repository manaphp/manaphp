<?php
namespace ManaPHP\Security;

use ManaPHP\Component;
use ManaPHP\Security\Captcha\Exception;

class Captcha extends Component implements CaptchaInterface
{
    protected /** @noinspection SpellCheckingInspection */
        $_charSet = '23456789abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY';
    /**
     * @var array
     */
    protected $_fontFiles = [
        __DIR__ . '/Captcha/Fonts/AirbusSpecial.ttf',
        __DIR__ . '/Captcha/Fonts/StencilFour.ttf',
        __DIR__ . '/Captcha/Fonts/SpicyRice.ttf'
    ];
    protected $_sessionVar = 'captcha';

    protected $_angleAmplitude = 30;

    protected $_noiseCharCount = 1;

    protected $_bgRGB = '255,255,255';

    protected $_codeLength = 4;

    /**
     * @var int
     */
    protected $_minInterval = 1;

    /**
     * @param string $rgb
     *
     * @return static
     */
    public function setBackground($rgb)
    {
        $this->_bgRGB = $rgb;

        return $this;
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

    protected function _rand_amplitude($a)
    {
        return mt_rand((1 - $a) * 10000, (1 + $a) * 10000) / 10000;
    }

    /**
     * @param array $fontFiles
     *
     * @return static
     */
    public function setFontFiles($fontFiles)
    {
        $this->_fontFiles = $fontFiles;

        return $this;
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

        list($r, $g, $b) = explode(',', $this->_bgRGB);
        $bgColor = imagecolorallocate($image, $r, $g, $b);

        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        $fontFile = $this->_fontFiles[mt_rand() % count($this->_fontFiles)];

        $referenceFontSize = min($height, $width / $this->_codeLength);

        $x = 0;
        $points[2] = mt_rand($referenceFontSize * 0.1, $referenceFontSize * 0.3);
        $length = strlen($code);
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $length; $i++) {
            $fontSize = $referenceFontSize * mt_rand(800, 1000) / 1000;
            $angle = mt_rand(-$this->_angleAmplitude, $this->_angleAmplitude);
            $x += ($points[2] - $x) - round(mt_rand($fontSize * 0.1, $fontSize * 0.2));
            $y = $height - (($height - $referenceFontSize) * mt_rand(0, 1000) / 1000);
            $fgColor = imagecolorallocate($image, mt_rand(0, 240), mt_rand(0, 240), mt_rand(0, 240));

            $points = imagettftext($image, $fontSize, $angle, $x, $y, $fgColor, $fontFile, $code[$i]);

            for ($k = 0; $k < $this->_noiseCharCount; $k++) {
                $letter = $this->_charSet[mt_rand() % strlen($this->_charSet)];
                $fgColor = imagecolorallocate($image, mt_rand(0, 240), mt_rand(0, 240), mt_rand(0, 240));
                imagettftext($image,
                    $fontSize * 0.4 * $this->_rand_amplitude(0.1),
                    mt_rand(-40, 40),
                    round($x + mt_rand(-$fontSize * 1.5, $fontSize)),
                    $height / 2 + mt_rand(-$fontSize * 0.5, $fontSize * 0.5),
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
        $image = new \Imagick();
        $draw = new \ImagickDraw();
        $image->newImage($width, $height, new \ImagickPixel('rgb(' . $this->_bgRGB . ')'));
        $draw->setFont($this->_fontFiles[mt_rand() % count($this->_fontFiles)]);
        $draw->setGravity(\Imagick::GRAVITY_NORTHWEST);

        $referenceFontSize = min($height, $width / $this->_codeLength);

        $x = mt_rand($referenceFontSize * 0.1, $referenceFontSize * 0.3);
        $length = strlen($code);
        $fgPixel = new \ImagickPixel();
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $length; $i++) {
            $fontSize = $referenceFontSize * mt_rand(800, 1000) / 1000;
            $draw->setFontSize($fontSize);
            $fgPixel->setColor('rgb(' . mt_rand(0, 240) . ',' . mt_rand(0, 240) . ',' . mt_rand(0, 240) . ')');
            $draw->setFillColor($fgPixel);
            $angle = mt_rand(-$this->_angleAmplitude, $this->_angleAmplitude);
            $y = ($height - $referenceFontSize) * mt_rand(-1000, 1000) / 1000;
            $image->annotateImage($draw, $x, $y, $angle, $code[$i]);
            $x += $fontSize * mt_rand(600, 800) / 1000;

            for ($k = 0; $k < $this->_noiseCharCount; $k++) {
                $letter = $this->_charSet[mt_rand() % strlen($this->_charSet)];
                $fgPixel->setColor('rgb(' . mt_rand(0, 240) . ',' . mt_rand(0, 240) . ',' . mt_rand(0, 240) . ')');
                $draw->setFillColor($fgPixel);
                $draw->setFontSize($fontSize * 0.4 * $this->_rand_amplitude(0.1));
                $angle = mt_rand(-40, 40);
                $image->annotateImage($draw, $x + mt_rand(-700, 700) / 1000 * $fontSize, $fontSize / 2 + mt_rand(-$fontSize * 0.5, $fontSize * 0.5), $angle, $letter);
            }
        }

        $this->response->setContentType('image/jpeg');
        $image->setImageFormat('jpeg');
        $this->response->setContent($image->__tostring());
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
     * @throws \ManaPHP\Security\Captcha\Exception
     */
    public function generate($width = 100, $height = 30, $ttl = 300)
    {
        $code = '';
        $charsetCount = strlen($this->_charSet);
        for ($i = 0; $i < $this->_codeLength; $i++) {
            $code .= $this->_charSet[mt_rand() % $charsetCount];
        }

        if (class_exists('Imagick')) {
            $response = $this->_generateByImagic($code, $width, $height);
        } elseif (function_exists('gd_info')) {
            $response = $this->_generateByGd($code, $width, $height);
        } else {
            throw new Exception('captcha is not support, please install gd module first.');
        }

        $this->session->set($this->_sessionVar, ['code' => $code, 'created_time' => time(), 'ttl' => $ttl]);

        return $response;
    }

    protected function _verify($code, $isTry)
    {
        if (!$this->session->has($this->_sessionVar)) {
            throw new Exception('captcha is not exist in server.');
        }

        $sessionVar = $this->session->get($this->_sessionVar);

        if ($isTry) {
            if (isset($sessionVar['try_verified_time'])) {
                $this->session->remove($this->_sessionVar);
                throw new Exception('captcha has been tried.');
            } else {
                $sessionVar['try_verified_time'] = time();
                $this->session->set($this->_sessionVar, $sessionVar);
            }
        } else {
            $this->session->remove($this->_sessionVar);
        }

        if (time() - $sessionVar['created_time'] < $this->_minInterval) {
            throw new Exception('captcha verification is too frequency.');
        }

        if (time() - $sessionVar['created_time'] > $sessionVar['ttl']) {
            throw new Exception('captcha is expired.');
        }

        if (strtolower($sessionVar['code']) !== strtolower($code)) {
            throw new Exception('captcha is not match.');
        }

        return true;
    }

    public function verify($code)
    {
        return $this->_verify($code, false);
    }

    public function tryVerify($code)
    {
        return $this->_verify($code, true);
    }
}