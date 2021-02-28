<?php

namespace ManaPHP\Imaging;

use ManaPHP\Component;

abstract class Image extends Component implements ImageInterface
{
    abstract public function do_getWidth();

    /**
     * Image width
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->self->do_getWidth();
    }

    abstract public function do_getHeight();

    /**
     * Image height
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->self->do_getHeight();
    }

    /**
     * @param int $width
     * @param int $height
     * @param int $offsetX
     * @param int $offsetY
     *
     * @return static
     */
    abstract public function do_crop($width, $height, $offsetX = 0, $offsetY = 0);

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
        $this->self->do_crop($width, $height, $offsetX, $offsetY);

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return static
     */
    abstract public function do_resize($width, $height);

    /**
     * @param int $width
     * @param int $height
     *
     * @return static
     */
    public function resize($width, $height)
    {
        $this->self->do_resize($width, $height);

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
        $_width = $this->self->do_getWidth();
        $_height = $this->self->do_getHeight();

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

        $this->self->crop($crop_width, $crop_height, $offsetX, $offsetY);
        $this->self->scale($width / $crop_width);

        return $this;
    }

    /**
     * @param int   $degrees
     * @param int   $background
     * @param float $alpha
     *
     * @return static
     */
    abstract public function do_rotate($degrees, $background = 0xffffff, $alpha = 1.0);

    /**
     * Scale the image by a given ratio
     *
     * @param float $ratio
     *
     * @return static
     */
    public function scale($ratio)
    {
        $_width = (int)$this->self->do_getWidth();
        $_height = (int)$this->self->do_getHeight();

        if ($ratio === 1) {
            return $this;
        }

        $width = (int)($_width * $ratio);
        $height = (int)($_height * $ratio);

        $this->self->do_resize($width, $height);

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
        $_width = $this->self->do_getWidth();
        $_height = $this->self->do_getHeight();

        $height = (int)($_height * $width / $_width);
        $this->self->do_resize($width, $height);

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
        $_width = $this->self->do_getWidth();
        $_height = $this->self->do_getHeight();

        $width = (int)($_width * $height / $_height);
        $this->self->do_resize($width, $height);

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
        $this->self->do_rotate($degrees, $background, $alpha);

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
    abstract public function do_text(
        $text,
        $offsetX = 0,
        $offsetY = 0,
        $opacity = 1.0,
        $color = 0x000000,
        $size = 12,
        $font_file = null
    );

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
        $this->self->do_text($text, $offsetX, $offsetY, $opacity, $color, $size, $font_file);

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
    abstract public function do_watermark($file, $offsetX = 0, $offsetY = 0, $opacity = 1.0);

    /**
     * @param string $file
     * @param int    $offsetX
     * @param int    $offsetY
     * @param float  $opacity
     *
     * @return static
     */
    public function watermark($file, $offsetX = 0, $offsetY = 0, $opacity = 1.0)
    {
        $this->self->do_watermark($file, $offsetX, $offsetY, $opacity);

        return $this;
    }

    /**
     * @param string $file
     * @param int    $quality
     *
     * @return static
     */
    abstract public function do_save($file, $quality = 80);

    /**
     * @param string $file
     * @param int    $quality
     *
     * @return void
     */
    public function save($file, $quality = 80)
    {
        $this->self->do_save($file, $quality);
    }
}