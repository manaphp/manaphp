<?php
declare(strict_types=1);

namespace ManaPHP\I18n;

interface LocaleInterface
{
    public function get(): string;

    public function set(string $locale): static;
}