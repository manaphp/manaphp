<?php
namespace ManaPHP\Image\Adapter;

use ManaPHP\Di;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Image;

class Proxy extends Image
{
    /**
     * @var \ManaPHP\ImageInterface
     */
    protected $_handler;

    /**
     * Proxy constructor.
     *
     * @param string $file
     */
    public function __construct($file)
    {
        if (extension_loaded('imagick')) {
            $this->_handler = Di::getDefault()->get('ManaPHP\Image\Adapter\Imagick', [$file]);
        } elseif (extension_loaded('gd')) {
            $this->_handler = Di::getDefault()->get('ManaPHP\Image\Adapter\Gd', [$file]);
        } else {
            throw new NotSupportedException('neither `imagic` nor `gd` extension is loaded');
        }
    }

    /**
     * Image width
     *
     * @return int
     */
    public function do_getWidth()
    {
        return $this->_handler->do_getWidth();
    }

    public function do_getHeight()
    {
        return $this->_handler->do_getHeight();
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return static
     */
    public function do_resize($width, $height)
    {
        return $this->_handler->do_resize($width, $height);
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
        return $this->_handler->do_rotate($degrees, $background, $alpha);
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
        return $this->_handler->do_crop($width, $height, $offsetX, $offsetY);
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
        return $this->_handler->do_text($text, $offsetX, $offsetY, $opacity, $color, $size, $font_file);
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
        return $this->_handler->do_watermark($file, $offsetX, $offsetY, $opacity);
    }

    /**
     * @param string $file
     * @param int    $quality
     */
    public function do_save($file, $quality = 80)
    {
        $this->_handler->do_save($file, $quality);
    }
}