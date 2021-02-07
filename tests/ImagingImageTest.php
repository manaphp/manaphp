<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class ImagingImageTest extends TestCase
{
    protected $originalImage;
    protected $resultDirectory;

    public function setUp()
    {
        parent::setUp();

        new FactoryDefault();

        $this->originalImage = __DIR__ . '/Image/original.jpg';
        $this->resultDirectory = __DIR__ . '/Image/Result';
    }

    public function test_getWidth()
    {
        $image = image_create($this->originalImage);
        $this->assertEquals(600, $image->getWidth());
    }

    public function test_getHeight()
    {
        $image = image_create($this->originalImage);
        $this->assertEquals(300, $image->getHeight());
    }

    public function test_resize()
    {
        $image = image_create($this->originalImage);
        $image->resize(600, 150);
        $resultImageFile = $this->resultDirectory . '/resize_600x150.jpg';
        $image->save($resultImageFile);

        $image = image_create($this->originalImage);
        $image->resize(600, 300);
        $resultImageFile = $this->resultDirectory . '/resize_600x300.jpg';
        $image->save($resultImageFile);

        $image = image_create($this->originalImage);
        $image->resize(600, 600);
        $resultImageFile = $this->resultDirectory . '/resize_600x600.jpg';
        $image->save($resultImageFile);
    }

    public function test_resizeCropCenter()
    {
        $image = image_create($this->originalImage);
        $image->resizeCropCenter(600, 150);
        $resultImageFile = $this->resultDirectory . '/resizeCropCenter_600x150.jpg';
        $image->save($resultImageFile);

        $image = image_create($this->originalImage);
        $image->resizeCropCenter(600, 300);
        $resultImageFile = $this->resultDirectory . '/resizeCropCenter_600x300.jpg';
        $image->save($resultImageFile);

        $image = image_create($this->originalImage);
        $image->resizeCropCenter(600, 600);
        $resultImageFile = $this->resultDirectory . '/resizeCropCenter_600x600.jpg';
        $image->save($resultImageFile);
    }

    public function test_scale()
    {
        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/scale_0.5.jpg';
        $image->scale(0.5)->save($resultImageFile);

        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/scale_1.jpg';
        $image->scale(1)->save($resultImageFile);

        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/scale_1.5.jpg';
        $image->scale(1.5)->save($resultImageFile);
    }

    public function test_scaleFixedWidth()
    {
        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/scaleFixedWidth_600.jpg';
        $image->scaleFixedWidth(600)->save($resultImageFile);

        $image = new Proxy($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/scaleFixedWidth_300.jpg';
        $image->scaleFixedWidth(300)->save($resultImageFile);

        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/scaleFixedWidth_150.jpg';
        $image->scaleFixedWidth(150)->save($resultImageFile);
    }

    public function test_scaleFixedHeight()
    {
        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/scaleFixedHeight_600.jpg';
        $image->scaleFixedHeight(600)->save($resultImageFile);

        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/scaleFixedHeight_300.jpg';
        $image->scaleFixedHeight(300)->save($resultImageFile);

        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/scaleFixedHeight_150.jpg';
        $image->scaleFixedHeight(150)->save($resultImageFile);
    }

    public function test_crop()
    {
        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/crop_100x100_0x0.jpg';
        $image->crop(100, 100, 0, 0)->save($resultImageFile);

        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/crop_100x100_200x200.jpg';
        $image->crop(100, 100, 200, 200)->save($resultImageFile);
    }

    public function test_rotate()
    {
        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/rotate_0.jpg';
        $image->rotate(0)->save($resultImageFile);

        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/rotate_45.jpg';
        $image->rotate(45)->save($resultImageFile);

        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/rotate_90.jpg';
        $image->rotate(90)->save($resultImageFile);

        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/rotate__45.jpg';
        $image->rotate(-45)->save($resultImageFile);
    }

    public function test_watermark()
    {
        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/watermark_with_alpha.jpg';
        $image->watermark(__DIR__ . '/Image/watermark.png', 0, 0, 0.5)->save($resultImageFile);

        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/' . 'watermark_without_alpha.jpg';
        $image->watermark(__DIR__ . '/Image/watermark.jpg', 0, 0, 0.5)->save($resultImageFile);
    }

    public function test_text()
    {
        $image = image_create($this->originalImage);
        $resultImageFile = $this->resultDirectory . '/text.jpg';
        $image->text('http://www.google.com', 0, 0, 0.8, 0xffccdd, 16)->save($resultImageFile);
    }
}