<?php

namespace ManaPHP;

use ManaPHP\Image\Adapter\Gd;
use ManaPHP\Image\Adapter\Imagick;
use ManaPHP\Image\Exception as ImageException;

class Image implements ImageInterface
{
    /**
     * @var \ManaPHP\Image\AdapterInterface
     */
    public $adapter;

    /**
     * ImageInterface constructor.
     *
     * @param string $file
     *
     * @throws \ManaPHP\Image\Exception
     */
    public function __construct($file)
    {
        if (extension_loaded('imagick')) {
            $this->adapter = new Imagick($file);
        } elseif (extension_loaded('gd')) {
            $this->adapter = new Gd($file);
        } else {
            throw new ImageException('No valid Image Adapter exists.'/**m0e2528a66b81cf976*/);
        }
    }

    /**
     * Image width
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->adapter->getWidth();
    }

    /**
     * Image height
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->adapter->getHeight();
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
        $this->adapter->crop($width, $height, $offsetX, $offsetY);

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return static
     */
    public function resize($width, $height)
    {
        $this->adapter->resize($width, $height);

        return $this;
    }

    /**
     * Resize the image by a given width and height
     *
     * @param int $width
     * @param int $height
     *
     * @return static
     */
    public function resizeCropCenter($width, $height)
    {
        $_width = $this->adapter->getWidth();
        $_height = $this->adapter->getHeight();

        if ($_width / $_height > $width / $height) {
            $crop_height = $_height;
            $crop_width = $width * $crop_height / $height;
            $offsetX = ($_width - $crop_width) / 2;
            $offsetY = 0;
        } else {
            $crop_width = $_width;
            $crop_height = $height * $crop_width / $width;
            $offsetY = ($_height - $crop_height) / 2;
            $offsetX = 0;
        }

        $this->crop($crop_width, $crop_height, $offsetX, $offsetY);
        $this->scale($width / $crop_width);

        return $this;
    }

    /**
     * Scale the image by a given ratio
     *
     * @param float $ratio
     *
     * @return static
     */
    public function scale($ratio)
    {
        $_width = (int)$this->adapter->getWidth();
        $_height = (int)$this->adapter->getHeight();

        if ($ratio === 1) {
            return $this;
        }

        $width = (int)($_width * $ratio);
        $height = (int)($_height * $ratio);

        $this->adapter->resize($width, $height);

        return $this;
    }

    /**
     * Scale the image by a given width
     *
     * @param int $width
     *
     * @return static
     */
    public function scaleFixedWidth($width)
    {
        $_width = $this->adapter->getWidth();
        $_height = $this->adapter->getHeight();

        $height = (int)($_height * $width / $_width);
        $this->adapter->resize($width, $height);

        return $this;
    }

    /**
     * Scale the image by a given height
     *
     * @param int $height
     *
     * @return static
     */
    public function scaleFixedHeight($height)
    {
        $_width = $this->adapter->getWidth();
        $_height = $this->adapter->getHeight();

        $width = (int)($_width * $height / $_height);
        $this->adapter->resize($width, $height);

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
        $this->adapter->rotate($degrees, $background, $alpha);

        return $this;
    }

    /**
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
        $this->adapter->text($text, $offsetX, $offsetY, $opacity, $color, $size, $font_file);

        return $this;
    }

    /**
     * @param string $file
     * @param int    $offsetX
     * @param int    $offsetY
     * @param float  $opacity
     *
     * @return static
     * @throws \ManaPHP\Image\Exception
     */
    public function watermark($file, $offsetX = 0, $offsetY = 0, $opacity = 1.0)
    {
        $this->adapter->watermark($file, $offsetX, $offsetY, $opacity);

        return $this;
    }

    public function save($file, $quality = 80)
    {
        $this->adapter->save($file, $quality);

        return $this;
    }
}