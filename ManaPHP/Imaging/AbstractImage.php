<?php
declare(strict_types=1);

namespace ManaPHP\Imaging;

use ManaPHP\Component;

abstract class AbstractImage extends Component implements ImageInterface
{
    abstract public function do_getWidth(): int;

    public function getWidth(): int
    {
        return $this->do_getWidth();
    }

    abstract public function do_getHeight(): int;

    public function getHeight(): int
    {
        return $this->do_getHeight();
    }

    abstract public function do_crop(int $width, int $height, int $offsetX = 0, int $offsetY = 0): static;

    public function crop(int $width, int $height, int $offsetX = 0, int $offsetY = 0): static
    {
        $this->do_crop($width, $height, $offsetX, $offsetY);

        return $this;
    }

    abstract public function do_resize(int $width, int $height): static;

    public function resize(int $width, int $height): static
    {
        $this->do_resize($width, $height);

        return $this;
    }

    public function resizeCropCenter(int $width, int $height): static
    {
        $_width = $this->do_getWidth();
        $_height = $this->do_getHeight();

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

    abstract public function do_rotate(int $degrees, int $background = 0xffffff, float $alpha = 1.0): static;

    /**
     * Scale the image by a given ratio
     *
     * @param float $ratio
     *
     * @return static
     */
    public function scale(float $ratio): static
    {
        $_width = $this->do_getWidth();
        $_height = $this->do_getHeight();

        $width = (int)($_width * $ratio);
        $height = (int)($_height * $ratio);

        $this->do_resize($width, $height);

        return $this;
    }

    public function scaleFixedWidth(int $width): static
    {
        $_width = $this->do_getWidth();
        $_height = $this->do_getHeight();

        $height = (int)($_height * $width / $_width);
        $this->do_resize($width, $height);

        return $this;
    }

    public function scaleFixedHeight(int $height): static
    {
        $_width = $this->do_getWidth();
        $_height = $this->do_getHeight();

        $width = (int)($_width * $height / $_height);
        $this->do_resize($width, $height);

        return $this;
    }

    public function rotate(int $degrees, int $background = 0xffffff, float $alpha = 1.0): static
    {
        $this->do_rotate($degrees, $background, $alpha);

        return $this;
    }

    abstract public function do_text(
        string $text,
        int $offsetX = 0,
        int $offsetY = 0,
        float $opacity = 1.0,
        int $color = 0x000000,
        int $size = 12,
        ?string $font_file = null
    ): static;

    public function text(
        string $text,
        int $offsetX = 0,
        int $offsetY = 0,
        float $opacity = 1.0,
        int $color = 0x000000,
        int $size = 12,
        ?string $font_file = null
    ): static {
        $this->do_text($text, $offsetX, $offsetY, $opacity, $color, $size, $font_file);

        return $this;
    }

    abstract public function do_watermark(string $file, int $offsetX = 0, int $offsetY = 0, float $opacity = 1.0
    ): static;

    public function watermark(string $file, int $offsetX = 0, int $offsetY = 0, float $opacity = 1.0): static
    {
        $this->do_watermark($file, $offsetX, $offsetY, $opacity);

        return $this;
    }

    abstract public function do_save(string $file, int $quality = 80): void;

    public function save(string $file, int $quality = 80): void
    {
        $this->do_save($file, $quality);
    }
}