<?php
declare(strict_types=1);

namespace ManaPHP\Html;

interface PurifierInterface
{
    public function purify(string $html, ?array $allowedTags = null, ?array $allowedAttributes = null): string;
}