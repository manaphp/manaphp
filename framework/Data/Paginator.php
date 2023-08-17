<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use JetBrains\PhpStorm\ArrayShape;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\PreconditionException;
use ManaPHP\Http\RequestInterface;

class Paginator implements PaginatorInterface
{
    #[Inject] protected RequestInterface $request;

    public int $count;
    public int $size;
    public int $page;
    public int $pages;
    public int $prev;
    public int $next;
    public array $items;
    protected int $links = 11;

    public function setLinks(int $number): static
    {
        $this->links = $number;

        return $this;
    }

    public function paginate(int $count, ?int $size = null, ?int $page = null): static
    {
        $this->count = $count;
        $this->size = (int)($size ?: $this->request->get('size', 10));
        $this->page = (int)($page ?: $this->request->get('page', 1));
        $this->pages = (int)ceil($this->count / $this->size);
        $this->prev = ($this->page <= $this->pages && $this->page > 1) ? $this->page - 1 : -1;
        $this->next = $this->page < $this->pages ? $this->page + 1 : -1;

        return $this;
    }

    #[ArrayShape(['page' => "int", 'size' => "int", 'count' => "int", 'pages' => "int", 'items' => "array"])]
    public function renderAsArray(): array
    {
        return [
            'page'  => $this->page,
            'size'  => $this->size,
            'count' => $this->count,
            'pages' => $this->pages,
            'items' => $this->items
        ];
    }

    public function renderAsHtml(?string $urlTemplate = null): string
    {
        if ($urlTemplate === null) {
            if (!$this->request->hasServer('REQUEST_URI')) {
                throw new PreconditionException('REQUEST_URI is not exist');
            } else {
                $urlTemplate = $this->request->getServer('REQUEST_URI', 'ignore');
            }

            if (!str_contains($urlTemplate, '?page=') && !str_contains($urlTemplate, '&page=')) {
                $urlTemplate .= (str_contains($urlTemplate, '?') ? '&' : '?') . 'page={page}';
            } else {
                $urlTemplate = (string)preg_replace('#([?&]page)=\d+#', '\1={page}', $urlTemplate);
            }
        }

        if (!str_contains($urlTemplate, '{page}')) {
            throw new InvalidValueException(['`:template` url must contain {page}', 'template' => $urlTemplate]);
        }

        $str = PHP_EOL . '<ul class="pagination">' . PHP_EOL;
        $first_url = str_replace('{page}', '1', $urlTemplate);
        $str .= '  <li class="first"><a href="' . $first_url . '">&lt;&lt;</a></li>' . PHP_EOL;

        if ($this->prev < 0) {
            $str .= '  <li class="prev disabled"><span>&lt;</span></li>' . PHP_EOL;
        } else {
            $prev_url = str_replace('{page}', (string)$this->prev, $urlTemplate);
            $str .= '  <li class="prev"><a href="' . $prev_url . '">&lt;</a></li>' . PHP_EOL;
        }

        $startPage = (int)min($this->page - ceil($this->links / 2), $this->pages - $this->links);
        $startPage = max(0, $startPage) + 1;

        $endPage = min($startPage + $this->links - 1, $this->pages);

        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i === $this->page) {
                $str .= '  <li class="active"><span>' . $i . '</span></li>' . PHP_EOL;
            } else {
                $i_url = str_replace('{page}', $i, $urlTemplate);
                $str .= '  <li><a href="' . $i_url . '">' . $i . '</a></li>' . PHP_EOL;
            }
        }

        if ($this->next < 0) {
            $str .= '  <li class="next disabled"><span>&gt;</span></li>' . PHP_EOL;
        } else {
            $next_url = str_replace('{page}', (string)$this->next, $urlTemplate);
            $str .= '  <li class="next"><a href="' . $next_url . '">&gt;</a></li>' . PHP_EOL;
        }

        $last_url = str_replace('{page}', (string)$this->pages, $urlTemplate);
        $str .= '  <li class="last"><a href="' . $last_url . '">&gt;&gt;</a></li>' . PHP_EOL;
        $str .= '</ul>' . PHP_EOL;

        return $str;
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    public function __toString(): string
    {
        try {
            return $this->renderAsHtml();
        } catch (\Exception $e) {
            return '';
        }
    }

    #[ArrayShape(['page' => "int", 'size' => "int", 'count' => "int", 'pages' => "int", 'items' => "array"])]
    public function jsonSerialize(): array
    {
        return $this->renderAsArray();
    }

    #[ArrayShape(['page' => "int", 'size' => "int", 'count' => "int", 'pages' => "int", 'items' => "array"])]
    public function toArray(): array
    {
        return $this->renderAsArray();
    }
}