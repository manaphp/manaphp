<?php
declare(strict_types=1);

namespace ManaPHP\I18n;

interface TranslatorInterface
{
    public function translate(string $template, array $placeholders = []): string;
}