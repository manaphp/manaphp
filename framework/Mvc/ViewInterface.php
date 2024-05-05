<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

interface ViewInterface
{
    public function setLayout(string $layout = 'Default'): static;

    public function disableLayout(): static;

    public function setVar(string $name, mixed $value): static;

    public function setVars(array $vars): static;

    public function getVar(?string $name = null): mixed;

    public function hasVar(string $name): bool;

    public function render(string $template, array $vars = []): string;

    public function widget(string $widget, array $options = []): void;

    public function block(string $path, array $vars = []): void;

    public function setContent(string $content): static;

    public function getContent(): string;
}