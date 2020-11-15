<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Imaging\Image\Adapter\Imagick;
use PHPUnit\Framework\TestCase;

class ImagingImageAdapterImagickTest extends TestCase
{
    protected $_originalImage;
    protected $_resultDirectory;

    public function setUp()
    {
        parent::setUp();

        new FactoryDefault();

        $this->_originalImage = __DIR__ . '/Image/original.jpg';
        $this->_resultDirectory = __DIR__ . '/Image/Engine/Imagick/Result';
        if (!@mkdir($this->_resultDirectory, 0755, true) && !is_dir($this->_resultDirectory)) {
            $this->fail('Create directory failed: ' . $this->_resultDirectory);
        }
    }

    public function test_getWidth()
    {
        $image = new Imagick($this->_originalImage);
        $this->assertEquals(600, $image->do_getWidth());
    }

    public function test_getHeight()
    {
        $image = new Imagick($this->_originalImage);
        $this->assertEquals(300, $image->do_getHeight());
    }

    public function test_resize()
    {
        $image = new Imagick($this->_originalImage);
        $image->do_resize(600, 150);
        $resultImageFile = $this->_resultDirectory . '/resize_600x150.jpg';
        $image->do_save($resultImageFile);

        $image = new Imagick($this->_originalImage);
        $image->do_resize(600, 300);
        $resultImageFile = $this->_resultDirectory . '/resize_600x300.jpg';
        $image->do_save($resultImageFile);

        $image = new Imagick($this->_originalImage);
        $image->do_resize(600, 600);
        $resultImageFile = $this->_resultDirectory . '/resize_600x600.jpg';
        $image->do_save($resultImageFile);
    }

    public function test_crop()
    {
        $image = new Imagick($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/crop_100x100_0x0.jpg';
        $image->do_crop(100, 100, 0, 0)->do_save($resultImageFile);

        $image = new Imagick($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/crop_100x100_200x200.jpg';
        $image->do_crop(100, 100, 200, 200)->do_save($resultImageFile);
    }

    public function test_rotate()
    {
        $image = new Imagick($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/rotate_0.jpg';
        $image->do_rotate(0)->do_save($resultImageFile);

        $image = new Imagick($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/rotate_45.jpg';
        $image->do_rotate(45)->do_save($resultImageFile);

        $image = new Imagick($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/rotate_90.jpg';
        $image->do_rotate(90)->do_save($resultImageFile);

        $image = new Imagick($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/rotate__45.jpg';
        $image->do_rotate(-45)->do_save($resultImageFile);
    }

    public function test_watermark()
    {
        $image = new Imagick($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/watermark_with_alpha.jpg';
        $image->do_watermark(__DIR__ . '/Image/watermark.png', 0, 0, 0.5)->do_save($resultImageFile);

        $image = new Imagick($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/' . 'watermark_without_alpha.jpg';
        $image->do_watermark(__DIR__ . '/Image/watermark.jpg', 0, 0, 0.5)->do_save($resultImageFile);
    }

    public function test_text()
    {
        $image = new Imagick($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/text.jpg';
        $image->do_text('http://www.google.com', 0, 0, 0.8, 0xffccdd, 16)->do_save($resultImageFile);
    }
}