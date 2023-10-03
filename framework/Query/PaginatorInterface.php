<?php
declare(strict_types=1);

namespace ManaPHP\Query;

interface PaginatorInterface
{
    public function setLinks(int $number): static;

    public function paginate(int $count, int $page, int $size): static;

    public function renderAsArray(): array;

    public function renderAsHtml(?string $urlTemplate = null): string;

    public function toArray(): array;
}