<?php
declare(strict_types=1);

namespace ManaPHP\Imaging\Image\Adapter;

use GdImage;
use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\ExtensionNotInstalledException;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\PreconditionException;
use ManaPHP\Imaging\AbstractImage;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class Gd extends AbstractImage
{
    protected string $file;
    protected GdImage $image;
    protected int $width;
    protected int $height;

    public function __construct(string $file)
    {
        if (!extension_loaded('gd')) {
            throw new ExtensionNotInstalledException('gd');
        }

        $this->file = realpath($this->alias->resolve($file));
        if (!$this->file) {
            throw new FileNotFoundException(['`:file` file is not exists', 'file' => $file]);
        }

        list($this->width, $this->height, $type) = getimagesize($this->file);

        if ($type === IMAGETYPE_GIF) {
            $this->image = imagecreatefromgif($this->file);
        } elseif ($type === IMAGETYPE_JPEG) {
            $this->image = imagecreatefromjpeg($this->file);
        } elseif ($type === IMAGETYPE_PNG) {
            $this->image = imagecreatefrompng($this->file);
        } else {
            throw new PreconditionException('Installed GD does not support such images');
        }
        imagesavealpha($this->image, true);
    }

    public function do_getWidth(): int
    {
        return $this->width;
    }

    public function do_getHeight(): int
    {
        return $this->height;
    }

    public function getInternalHandle(): mixed
    {
        return $this->image;
    }

    public function do_resize(int $width, int $height): static
    {
        if (version_compare(PHP_VERSION, '5.5.0') < 0) {
            $image = imagecreatetruecolor($width, $height);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            imagecopyresampled($image, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
        } else {
            $image = imagescale($this->image, $width, $height);
        }

        imagedestroy($this->image);
        $this->image = $image;
        $this->width = imagesx($image);
        $this->height = imagesy($image);

        return $this;
    }

    public function do_rotate(int $degrees, int $background = 0xffffff, float $alpha = 1.0): static
    {
        $red = ($background >> 16) & 0xFF;
        $green = ($background >> 8) & 0xFF;
        $blue = $background & 0xFF;
        $transparent = imagecolorallocatealpha($this->image, $red, $green, $blue, $alpha * 127);
        $image = imagerotate($this->image, 360 - $degrees, $transparent, true);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        imagedestroy($this->image);
        $this->image = $image;
        $this->width = imagesx($image);
        $this->height = imagesy($image);

        return $this;
    }

    public function do_crop(int $width, int $height, int $offsetX = 0, int $offsetY = 0): static
    {
        if (version_compare(PHP_VERSION, '5.5.0') < 0) {
            $image = imagecreatetruecolor($width, $height);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagecopy($image, $this->image, 0, 0, $offsetX, $offsetY, $width, $height);
        } else {
            $rect = ['x' => $offsetX, 'y' => $offsetY, 'width' => $width, 'height' => $height];
            $image = imagecrop($this->image, $rect);
        }

        imagedestroy($this->image);
        $this->image = $image;
        $this->width = imagesx($image);
        $this->height = imagesy($image);

        return $this;
    }

    public function do_text(
        string $text,
        int $offsetX = 0,
        int $offsetY = 0,
        float $opacity = 1.0,
        int $color = 0x000000,
        int $size = 12,
        ?string $font_file = null
    ): static {
        $red = ($color >> 16) & 0xFF;
        $green = ($color >> 8) & 0xFF;
        $blue = $color & 0xFF;
        $textColor = imagecolorallocatealpha($this->image, $red, $green, $blue, abs(1 - $opacity) * 127);
        if ($font_file) {
            $font_file = $this->alias->resolve($font_file);
            imagettftext($this->image, $size, 0, $offsetX, $offsetY, $textColor, $font_file, $text);
        } else {
            imagestring($this->image, $size, $offsetX, $offsetY, $text, $textColor);
        }

        return $this;
    }

    public function do_watermark(string $file, int $offsetX = 0, int $offsetY = 0, float $opacity = 1.0): static
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

        $image = imagecreatetruecolor($this->width, $this->height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        if ($maskType !== IMAGETYPE_PNG) {
            $filedColor = imagecolorallocatealpha($image, 127, 127, 127, (1 - $opacity) * 127);
        } else {
            $filedColor = imagecolorallocate($image, 127, 127, 127);
        }

        imagelayereffect($maskImage, IMG_EFFECT_OVERLAY);
        imagefilledrectangle($maskImage, 0, 0, $maskWidth, $maskHeight, $filedColor);
        imagealphablending($this->image, true);
        imagecopy($this->image, $maskImage, $offsetX, $offsetY, 0, 0, $maskWidth, $maskHeight);

        return $this;
    }

    public function do_save(string $file, int $quality = 80): void
    {
        $file = $this->alias->resolve($file);

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'jpg';
        }

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException($dir);
        }
        if ($ext === 'gif') {
            imagegif($this->image, $file);
        } elseif ($ext === 'jpg' || $ext === 'jpeg') {
            imagejpeg($this->image, $file, $quality);
        } elseif ($ext === 'png') {
            imagepng($this->image, $file);
        } else {
            throw new PreconditionException(['`:extension` is not supported by Installed GD', 'extension' => $ext]);
        }
    }

    public function __destruct()
    {
        imagedestroy($this->image);
    }
}