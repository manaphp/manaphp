<?php

namespace ManaPHP\Imaging\Image\Adapter;

use ImagickDraw;
use ImagickPixel;
use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\ExtensionNotInstalledException;
use ManaPHP\Exception\InvalidFormatException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\PreconditionException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Imaging\AbstractImage;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class Imagick extends AbstractImage
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var \Imagick
     */
    protected $image;

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @param string $file
     */
    public function __construct($file)
    {
        if (!extension_loaded('imagick')) {
            throw new ExtensionNotInstalledException('Imagick');
        }

        $this->file = realpath($this->alias->resolve($file));
        if (!$this->file) {
            throw new InvalidValueException(['`:file` file is not exists', 'file' => $file]);
        }

        $this->image = new \Imagick();
        if (!$this->image->readImage($this->file)) {
            throw new InvalidFormatException(['Imagick::readImage `:file` failed', 'file' => $file]);
        }

        if ($this->image->getNumberImages() !== 1) {
            throw new PreconditionException(['not support multiple iterations: `:file`', 'file' => $file]);
        }

        if (!$this->image->getImageAlphaChannel()) {
            $this->image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_SET);
        }

        $this->width = $this->image->getImageWidth();
        $this->height = $this->image->getImageHeight();
    }

    /**
     * Image width
     *
     * @return int
     */
    public function do_getWidth()
    {
        return $this->width;
    }

    /**
     * Image height
     *
     * @return int
     */
    public function do_getHeight()
    {
        return $this->height;
    }

    /**
     * @return \Imagick
     */
    public function getInternalHandle()
    {
        return $this->image;
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return static
     */
    public function do_resize($width, $height)
    {
        $this->image->scaleImage($width, $height);

        $this->width = $this->image->getImageWidth();
        $this->height = $this->image->getImageHeight();

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
        $backgroundColor = sprintf('rgba(%u,%u,%u,%f)', $red, $green, $blue, $alpha);
        $this->image->rotateImage(new ImagickPixel($backgroundColor), $degrees);
        $this->image->setImagePage($this->width, $this->height, 0, 0);

        $this->width = $this->image->getImageWidth();
        $this->height = $this->image->getImageHeight();

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
        $this->image->cropImage($width, $height, $offsetX, $offsetY);
        $this->image->setImagePage($width, $height, 0, 0);

        $this->width = $this->image->getImageWidth();
        $this->height = $this->image->getImageHeight();

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
        $draw = new ImagickDraw();
        $textColor = sprintf('rgb(%u,%u,%u)', ($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF);
        $draw->setFillColor(new ImagickPixel($textColor));
        if ($font_file) {
            $draw->setFont($this->alias->resolve($font_file));
        }
        $draw->setFontSize($size);
        $draw->setFillOpacity($opacity);
        $draw->setGravity(\Imagick::GRAVITY_NORTHWEST);
        $this->image->annotateImage($draw, $offsetX, $offsetY, 0, $text);
        $draw->destroy();

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
        $watermark = new \Imagick($this->alias->resolve($file));

        if ($watermark->getImageAlphaChannel() === \Imagick::ALPHACHANNEL_UNDEFINED) {
            $watermark->setImageOpacity($opacity);
        }

        if ($watermark->getNumberImages() !== 1) {
            throw new PreconditionException(['not support multiple iterations: `:file`', 'file' => $file]);
        }

        if (!$this->image->compositeImage($watermark, \Imagick::COMPOSITE_OVER, $offsetX, $offsetY)) {
            throw new RuntimeException('Imagick::compositeImage Failed');
        }

        $watermark->clear();
        $watermark->destroy();

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

        $this->image->setFormat($ext);

        if ($ext === 'gif') {
            $this->image->optimizeImageLayers();
        } elseif ($ext === 'jpg' || $ext === 'jpeg') {
            $this->image->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $this->image->setImageCompressionQuality($quality);
        }

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException($dir);
        }

        if (!$this->image->writeImage($file)) {
            throw new RuntimeException(['save `:file` image file failed', 'file' => $file]);
        }
    }

    public function __destruct()
    {
        $this->image->clear();
        $this->image->destroy();
    }
}