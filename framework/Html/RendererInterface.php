<?php
declare(strict_types=1);

namespace ManaPHP\Html;

interface RendererInterface
{
    public function lock(): void;

    public function unlock(): void;

    public function render(string $template, array $vars = [], bool $directOutput = false): ?string;

    public function renderFile(string $file, array $vars = []): string;

    public function partial(string $path, array $vars = []): void;

    public function exists(string $template): bool;

    public function getSection(string $section, string $default = ''): string;

    public function startSection(string $section, ?string $default = null): void;

    public function stopSection(bool $overwrite = false): void;

    public function appendSection(): void;
}