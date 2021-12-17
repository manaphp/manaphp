<?php
declare(strict_types=1);

namespace ManaPHP\Imaging;

interface ImageInterface
{
    public function __construct(string $file);

    public function getWidth(): int;

    public function getHeight(): int;

    public function resize(int $width, int $height): static;

    public function resizeCropCenter(int $width, int $height): static;

    public function scale(float $ratio): static;

    public function scaleFixedWidth(int $width): static;

    public function scaleFixedHeight(int $height): static;

    public function crop(int $width, int $height, int $offsetX = 0, int $offsetY = 0): static;

    public function rotate(int $degrees, int $background = 0xffffff, float $alpha = 1.0): static;

    public function watermark(string $file, int $offsetX = 0, int $offsetY = 0, float $opacity = 1.0): static;

    public function text(
        string $text,
        int $offsetX = 0,
        int $offsetY = 0,
        float $opacity = 1.0,
        int $color = 0x000000,
        int $size = 12,
        ?string $font_file = null
    ): static;

    public function save(string $file, int $quality = 80): void;
}