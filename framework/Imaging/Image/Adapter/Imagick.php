<?php

declare(strict_types=1);

namespace ManaPHP\Imaging\Image\Adapter;

use Imagick as PhpImagick;
use ImagickDraw;
use ImagickPixel;
use ManaPHP\Alias\Path;
use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\ExtensionNotInstalledException;
use ManaPHP\Exception\InvalidFormatException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\PreconditionException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Imaging\AbstractImage;
use function dirname;
use function extension_loaded;
use function is_dir;
use function mkdir;
use function pathinfo;
use function realpath;
use function sprintf;
use function strtolower;

class Imagick extends AbstractImage
{
    protected string $file;
    protected PhpImagick $image;
    protected int $width;
    protected int $height;

    public function __construct(string $file)
    {
        if (!extension_loaded('imagick')) {
            throw new ExtensionNotInstalledException('Imagick extension is not installed.');
        }

        $this->file = realpath(Path::resolve($file));
        if (!$this->file) {
            throw new InvalidValueException('The image file "{file}" does not exist.', ['file' => $file]);
        }

        $this->image = new PhpImagick();
        if (!$this->image->readImage($this->file)) {
            throw new InvalidFormatException('Could not read image file "{file}" using Imagick::readImage.', ['file' => $file]);
        }

        if ($this->image->getNumberImages() !== 1) {
            throw new PreconditionException('The file "{file}" contains multiple image iterations. Only single-image files are supported.', ['file' => $file]);
        }

        if (!$this->image->getImageAlphaChannel()) {
            $this->image->setImageAlphaChannel(PhpImagick::ALPHACHANNEL_SET);
        }

        $this->width = $this->image->getImageWidth();
        $this->height = $this->image->getImageHeight();
    }

    public function do_getWidth(): int
    {
        return $this->width;
    }

    public function do_getHeight(): int
    {
        return $this->height;
    }

    public function getInternalHandle(): PhpImagick
    {
        return $this->image;
    }

    public function do_resize(int $width, int $height): static
    {
        $this->image->scaleImage($width, $height);

        $this->width = $this->image->getImageWidth();
        $this->height = $this->image->getImageHeight();

        return $this;
    }

    public function do_rotate(int $degrees, int $background = 0xffffff, float $alpha = 1.0): static
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

    public function do_crop(int $width, int $height, int $offsetX = 0, int $offsetY = 0): static
    {
        $this->image->cropImage($width, $height, $offsetX, $offsetY);
        $this->image->setImagePage($width, $height, 0, 0);

        $this->width = $this->image->getImageWidth();
        $this->height = $this->image->getImageHeight();

        return $this;
    }

    public function do_text(
        string  $text,
        int     $offsetX = 0,
        int     $offsetY = 0,
        float   $opacity = 1.0,
        int     $color = 0x000000,
        int     $size = 12,
        ?string $font_file = null
    ): static
    {
        $draw = new ImagickDraw();
        $textColor = sprintf('rgb(%u,%u,%u)', ($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF);
        $draw->setFillColor(new ImagickPixel($textColor));
        if ($font_file) {
            $draw->setFont(Path::resolve($font_file));
        }
        $draw->setFontSize($size);
        $draw->setFillOpacity($opacity);
        $draw->setGravity(PhpImagick::GRAVITY_NORTHWEST);
        $this->image->annotateImage($draw, $offsetX, $offsetY, 0, $text);
        $draw->destroy();

        return $this;
    }

    public function do_watermark(string $file, int $offsetX = 0, int $offsetY = 0, float $opacity = 1.0): static
    {
        $watermark = new PhpImagick(Path::resolve($file));

        /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
        if ($watermark->getImageAlphaChannel() === PhpImagick::ALPHACHANNEL_UNDEFINED) {
            $watermark->setImageOpacity($opacity);
        }

        if ($watermark->getNumberImages() !== 1) {
            throw new PreconditionException('The file "{file}" contains multiple image iterations. Only single-image files are supported.', ['file' => $file]);
        }

        if (!$this->image->compositeImage($watermark, PhpImagick::COMPOSITE_OVER, $offsetX, $offsetY)) {
            throw new RuntimeException('Imagick::compositeImage() failed. Please check image formats and dimensions.', [
                'main_image' => $this->file,
                'watermark_file' => $file,
                'main_image_format' => $this->image->getImageFormat(),
                'watermark_format' => $watermark->getImageFormat(),
                'main_image_size' => $this->image->getImageWidth() . 'x' . $this->image->getImageHeight(),
                'watermark_size' => $watermark->getImageWidth() . 'x' . $watermark->getImageHeight(),
                'offset_x' => $offsetX,
                'offset_y' => $offsetY
            ]);
        }

        $watermark->clear();

        return $this;
    }

    public function do_save(string $file, int $quality = 80): void
    {
        $file = Path::resolve($file);

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        $this->image->setFormat($ext);

        if ($ext === 'gif') {
            $this->image->optimizeImageLayers();
        } elseif ($ext === 'jpg' || $ext === 'jpeg') {
            $this->image->setImageCompression(PhpImagick::COMPRESSION_JPEG);
            $this->image->setImageCompressionQuality($quality);
        }

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException($dir);
        }

        if (!$this->image->writeImage($file)) {
            throw new RuntimeException('Could not save image file to "{file}".', ['file' => $file]);
        }
    }

    public function __destruct()
    {
        $this->image->clear();
    }
}
