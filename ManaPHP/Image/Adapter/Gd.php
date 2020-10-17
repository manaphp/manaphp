<?php

namespace ManaPHP\Image\Adapter;

use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\ExtensionNotInstalledException;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\PreconditionException;
use ManaPHP\Image;

/**
 * Class ManaPHP\Image\Adapter\Gd
 *
 * @package image\adapter
 */
class Gd extends Image
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
     */
    public function __construct($file)
    {
        if (!extension_loaded('gd')) {
            throw new ExtensionNotInstalledException('gd');
        }

        $this->_file = realpath($this->alias->resolve($file));
        if (!$this->_file) {
            throw new FileNotFoundException(['`:file` file is not exists', 'file' => $file]);
        }

        list($this->_width, $this->_height, $type) = getimagesize($this->_file);

        if ($type === IMAGETYPE_GIF) {
            $this->_image = imagecreatefromgif($this->_file);
        } elseif ($type === IMAGETYPE_JPEG) {
            $this->_image = imagecreatefromjpeg($this->_file);
        } elseif ($type === IMAGETYPE_PNG) {
            $this->_image = imagecreatefrompng($this->_file);
        } else {
            throw new PreconditionException('Installed GD does not support such images');
        }
        imagesavealpha($this->_image, true);
    }

    /**
     * Image width
     *
     * @return int
     */
    public function do_getWidth()
    {
        return $this->_width;
    }

    /**
     * Image height
     *
     * @return int
     */
    public function do_getHeight()
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
    public function do_resize($width, $height)
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
    public function do_rotate($degrees, $background = 0xffffff, $alpha = 1.0)
    {
        $red = ($background >> 16) & 0xFF;
        $green = ($background >> 8) & 0xFF;
        $blue = $background & 0xFF;
        $transparent = imagecolorallocatealpha($this->_image, $red, $green, $blue, $alpha * 127);
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
    public function do_crop($width, $height, $offsetX = 0, $offsetY = 0)
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
    public function do_text(
        $text,
        $offsetX = 0,
        $offsetY = 0,
        $opacity = 1.0,
        $color = 0x000000,
        $size = 12,
        $font_file = null
    ) {
        $red = ($color >> 16) & 0xFF;
        $green = ($color >> 8) & 0xFF;
        $blue = $color & 0xFF;
        $textColor = imagecolorallocatealpha($this->_image, $red, $green, $blue, abs(1 - $opacity) * 127);
        if ($font_file) {
            $font_file = $this->alias->resolve($font_file);
            imagettftext($this->_image, $size, 0, $offsetX, $offsetY, $textColor, $font_file, $text);
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
     */
    public function do_watermark($file, $offsetX = 0, $offsetY = 0, $opacity = 1.0)
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
            throw new PreconditionException('Installed GD does not support such images');
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
     * @return void
     */
    public function do_save($file, $quality = 80)
    {
        $file = $this->alias->resolve($file);

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'jpg';
        }

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException(['create `:1` image directory failed: :2', $dir, error_get_last()['message']]);
        }
        if ($ext === 'gif') {
            imagegif($this->_image, $file);
        } elseif ($ext === 'jpg' || $ext === 'jpeg') {
            imagejpeg($this->_image, $file, $quality);
        } elseif ($ext === 'png') {
            imagepng($this->_image, $file);
        } else {
            throw new PreconditionException(['`:extension` is not supported by Installed GD', 'extension' => $ext]);
        }
    }

    public function __destruct()
    {
        imagedestroy($this->_image);
    }
}