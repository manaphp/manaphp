<?php
namespace ManaPHP\Image\Engine;

use ManaPHP\Component;
use ManaPHP\Image\Engine\Gd\Exception as GdException;
use ManaPHP\Image\EngineInterface;

/**
 * Class ManaPHP\Image\Engine\Gd
 *
 * @package image\adapter
 */
class Gd extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_file;

    /**
     * @var resource
     */
    protected $_image;

    /**
     * @var int
     */
    protected $_width;

    /**
     * @var int
     */
    protected $_height;

    /**
     * @param string $file
     *
     * @throws \ManaPHP\Image\Engine\Exception
     */
    public function __construct($file)
    {
        if (!extension_loaded('gd')) {
            throw new GdException('gd is not installed, or the extension is not loaded'/**m02d21d9765a90c68b*/);
        }

        $this->_file = realpath($this->alias->resolve($file));
        if (!$this->_file) {
            throw new GdException('`:file` file is not exists'/**m028d68547edc10000*/, ['file' => $file]);
        }

        list($this->_width, $this->_height, $type) = getimagesize($this->_file);

        if ($type === IMAGETYPE_GIF) {
            $this->_image = imagecreatefromgif($this->_file);
        } elseif ($type === IMAGETYPE_JPEG) {
            $this->_image = imagecreatefromjpeg($this->_file);
        } elseif ($type === IMAGETYPE_PNG) {
            $this->_image = imagecreatefrompng($this->_file);
        } else {
            throw new GdException('Installed GD does not support such images'/**m0fc930b8083eb2b4f*/);
        }
        imagesavealpha($this->_image, true);
    }

    /**
     * Image width
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->_width;
    }

    /**
     * Image height
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->_height;
    }

    public function getInternalHandle()
    {
        return $this->_image;
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return static
     */
    public function resize($width, $height)
    {
        if (version_compare(PHP_VERSION, '5.5.0') < 0) {
            $image = imagecreatetruecolor($width, $height);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            imagecopyresampled($image, $this->_image, 0, 0, 0, 0, $width, $height, $this->_width, $this->_height);
        } else {
            $image = imagescale($this->_image, $width, $height);
        }

        imagedestroy($this->_image);
        $this->_image = $image;
        $this->_width = imagesx($image);
        $this->_height = imagesy($image);

        return $this;
    }

    /**
     * Rotate the image by a given degrees
     *
     * @param int   $degrees
     * @param int   $background
     * @param float $alpha
     *
     * @return static
     */
    public function rotate($degrees, $background = 0xffffff, $alpha = 1.0)
    {
        $transparent = imagecolorallocatealpha($this->_image, ($background >> 16) & 0xFF, ($background >> 8) & 0xFF,
            $background & 0xFF, $alpha * 127);
        $image = imagerotate($this->_image, 360 - $degrees, $transparent, true);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        imagedestroy($this->_image);
        $this->_image = $image;
        $this->_width = imagesx($image);
        $this->_height = imagesy($image);

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param int $offsetX
     * @param int $offsetY
     *
     * @return static
     */
    public function crop($width, $height, $offsetX = 0, $offsetY = 0)
    {
        if (version_compare(PHP_VERSION, '5.5.0') < 0) {
            $image = imagecreatetruecolor($width, $height);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagecopy($image, $this->_image, 0, 0, $offsetX, $offsetY, $width, $height);
        } else {
            $rect = ['x' => $offsetX, 'y' => $offsetY, 'width' => $width, 'height' => $height];
            $image = imagecrop($this->_image, $rect);
        }

        imagedestroy($this->_image);
        $this->_image = $image;
        $this->_width = imagesx($image);
        $this->_height = imagesy($image);

        return $this;
    }

    /**
     * Execute a text
     *
     * @param string $text
     * @param int    $offsetX
     * @param int    $offsetY
     * @param float  $opacity
     * @param int    $color
     * @param int    $size
     * @param string $font_file
     *
     * @return static
     */
    public function text(
        $text,
        $offsetX = 0,
        $offsetY = 0,
        $opacity = 1.0,
        $color = 0x000000,
        $size = 12,
        $font_file = null
    ) {
        $textColor = imagecolorallocatealpha($this->_image, ($color >> 16) & 0xFF, ($color >> 8) & 0xFF,
            $color & 0xFF, abs(1 - $opacity) * 127);
        if ($font_file) {
            imagettftext($this->_image, $size, 0, $offsetX, $offsetY, $textColor, $this->alias->resolve($font_file), $text);
        } else {
            imagestring($this->_image, $size, $offsetX, $offsetY, $text, $textColor);
        }

        return $this;
    }

    /**
     * @param string $file
     * @param int    $offsetX
     * @param int    $offsetY
     * @param float  $opacity
     *
     * @return static
     * @throws \ManaPHP\Image\Engine\Exception
     */
    public function watermark($file, $offsetX = 0, $offsetY = 0, $opacity = 1.0)
    {
        $file = $this->alias->resolve($file);

        list($maskWidth, $maskHeight, $maskType) = getimagesize($file);

        if ($maskType === IMAGETYPE_GIF) {
            $maskImage = imagecreatefromgif($file);
        } elseif ($maskType === IMAGETYPE_JPEG) {
            $maskImage = imagecreatefromjpeg($file);
        } elseif ($maskType === IMAGETYPE_PNG) {
            $maskImage = imagecreatefrompng($file);
        } else {
            throw new GdException('Installed GD does not support such images'/**m0d78d3cd78b039e72*/);
        }

        imagesavealpha($maskImage, true);

        $image = imagecreatetruecolor($this->_width, $this->_height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        if ($maskType !== IMAGETYPE_PNG) {
            $filedColor = imagecolorallocatealpha($image, 127, 127, 127, (1 - $opacity) * 127);
        } else {
            $filedColor = imagecolorallocate($image, 127, 127, 127);
        }

        imagelayereffect($maskImage, IMG_EFFECT_OVERLAY);
        imagefilledrectangle($maskImage, 0, 0, $maskWidth, $maskHeight, $filedColor);
        imagealphablending($this->_image, true);
        imagecopy($this->_image, $maskImage, $offsetX, $offsetY, 0, 0, $maskWidth, $maskHeight);

        return $this;
    }

    /**
     * @param string $file
     * @param int    $quality
     *
     * @throws \ManaPHP\Image\Engine\Exception
     */
    public function save($file, $quality = 80)
    {
        $file = $this->alias->resolve($file);

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'jpg';
        }

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new GdException('create `:dir` image directory failed: :message'/**m0798bf2f57ec615b2*/, ['dir' => $dir, 'message' => error_get_last()['message']]);
        }
        if ($ext === 'gif') {
            imagegif($this->_image, $file);
        } elseif ($ext === 'jpg' || $ext === 'jpeg') {
            imagejpeg($this->_image, $file, $quality);
        } elseif ($ext === 'png') {
            imagepng($this->_image, $file);
        } else {
            throw new GdException('`:extension` is not supported by Installed GD'/**m0e69270218b72270a*/, ['extension' => $ext]);
        }
    }

    public function __destruct()
    {
        imagedestroy($this->_image);
    }
}