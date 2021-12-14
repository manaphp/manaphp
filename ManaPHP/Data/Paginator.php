<?php

namespace ManaPHP\Data;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\PreconditionException;

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Paginator extends Component implements PaginatorInterface
{
    /**
     * @var int
     */
    public $count;

    /**
     * @var int
     */
    public $size;

    /**
     * @var int
     */
    public $page;

    /**
     * @var int
     */
    public $pages;

    /**
     * @var int
     */
    public $prev;

    /**
     * @var int
     */
    public $next;

    /**
     * @var array
     */
    public $items;

    /**
     * @var int
     */
    protected $links = 11;

    /**
     * @param int $number
     *
     * @return static
     */
    public function setLinks($number)
    {
        $this->links = $number;

        return $this;
    }

    /**
     * @param int $count
     * @param int $size
     * @param int $page
     *
     * @return static
     */
    public function paginate($count, $size = null, $page = null)
    {
        $this->count = (int)$count;
        $this->size = (int)($size ?: $this->request->get('size', 10));
        $this->page = (int)($page ?: $this->request->get('page', 1));
        $this->pages = (int)ceil($this->count / $this->size);
        $this->prev = ($this->page <= $this->pages && $this->page > 1) ? $this->page - 1 : -1;
        $this->next = $this->page < $this->pages ? $this->page + 1 : -1;

        return $this;
    }

    /**
     * @return array
     */
    public function renderAsArray()
    {
        return [
            'page'  => $this->page,
            'size'  => $this->size,
            'count' => $this->count,
            'pages' => $this->pages,
            'items' => $this->items
        ];
    }

    /**
     * @param string $urlTemplate
     *
     * @return string
     */
    public function renderAsHtml($urlTemplate = null)
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
        $first_url = str_replace('{page}', 1, $urlTemplate);
        $str .= '  <li class="first"><a href="' . $first_url . '">&lt;&lt;</a></li>' . PHP_EOL;

        if ($this->prev < 0) {
            $str .= '  <li class="prev disabled"><span>&lt;</span></li>' . PHP_EOL;
        } else {
            $prev_url = str_replace('{page}', $this->prev, $urlTemplate);
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
            $next_url = str_replace('{page}', $this->next, $urlTemplate);
            $str .= '  <li class="next"><a href="' . $next_url . '">&gt;</a></li>' . PHP_EOL;
        }

        $last_url = str_replace('{page}', $this->pages, $urlTemplate);
        $str .= '  <li class="last"><a href="' . $last_url . '">&gt;&gt;</a></li>' . PHP_EOL;
        $str .= '</ul>' . PHP_EOL;

        return $str;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->renderAsHtml();
        } catch (\Exception $e) {
            return '';
        }
    }

    public function jsonSerialize(): array
    {
        return $this->renderAsArray();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->renderAsArray();
    }
}