<?php
namespace ManaPHP;

use ManaPHP\Paginator\Exception as PaginatorException;

/**
 * Class ManaPHP\Paginator
 *
 * @package paginator
 *
 * @property \ManaPHP\Http\RequestInterface $request
 */
class Paginator extends Component implements PaginatorInterface
{
    public $numberOfPages = 9;

    /**
     * @var array
     */
    public $items;

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
     * @param int $count
     * @param int $size
     * @param int $page
     *
     * @return static
     * @throws \ManaPHP\Paginator\Exception
     */
    public function paginate($count, $size, $page)
    {
        $this->count = (int)$count;
        $this->size = (int)$size;
        $this->page = (int)$page;
        $this->pages = (int)ceil($this->count / $size);
        $this->prev = max(1, $this->page - 1);
        $this->next = min($this->page + 1, $this->pages);

        return $this;
    }

    /**
     * @param  false|string $itemsName
     *
     * @return array
     */
    public function renderAsArray($itemsName = 'items')
    {
        $data = [
            'page' => $this->page,
            'size' => $this->size,
            'count' => $this->count,
            'pages' => $this->pages,
        ];

        if ($itemsName !== false) {
            /** @noinspection OffsetOperationsInspection */
            $data[$itemsName] = $this->items;
        }

        return $data;
    }

    /**
     * @param string $urlTemplate
     *
     * @return string
     * @throws \ManaPHP\Paginator\Exception
     */
    public function renderAsHtml($urlTemplate = null)
    {
        if ($urlTemplate === null) {
            if (!$this->request->hasServer('REQUEST_URI')) {
                throw new PaginatorException('REQUEST_URI is not exist in $_SERVER'/**m043f318485f00921e*/);
            } else {
                $urlTemplate = $this->request->getServer('REQUEST_URI', 'ignore');
            }

            if (strpos($urlTemplate, '?page=') === false && strpos($urlTemplate, '&page=') === false) {
                $urlTemplate .= (strpos($urlTemplate, '?') === false ? '?' : '&') . 'page={page}';
            } else {
                $urlTemplate = preg_replace('#([\?&]page)=\d+#', '\1={page}', $urlTemplate);
            }
        }

        if (strpos($urlTemplate, '{page}') === false) {
            throw new PaginatorException('`:template` url template is invalid: it must contain {page} pattern'/**m0b85431254175cf7a*/, ['template' => $urlTemplate]);
        }

        $str = '';
        $str .= '<ul class="pagination">' . PHP_EOL;
        $str .= '<li class="first"><a href="' . str_replace('{page}', 1, $urlTemplate) . '">&lt;&lt;</a></li>' . PHP_EOL;
        $str .= '<li class="prev"><a href="' . str_replace('{page}', $this->prev, $urlTemplate) . '">&lt;</a></li>' . PHP_EOL;

        $startPage = (int)min($this->page - ceil($this->numberOfPages / 2), $this->pages - $this->numberOfPages);
        $startPage = max(0, $startPage) + 1;

        $endPage = min($startPage + $this->numberOfPages - 1, $this->pages);

        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i === $this->page) {
                $str .= '<li class="active"><a href="' . str_replace('{page}', $i, $urlTemplate) . '">' . $i . '</a></li>' . PHP_EOL;
            } else {
                $str .= '<li><a href="' . str_replace('{page}', $i, $urlTemplate) . '">' . $i . '</a></li>' . PHP_EOL;
            }
        }

        $str .= '<li class="next"><a href="' . str_replace('{page}', $this->next, $urlTemplate) . '">&gt;</a></li>' . PHP_EOL;
        $str .= '<li class="last"><a href="' . str_replace('{page}', $this->pages, $urlTemplate) . '">&gt;&gt;</a></li>' . PHP_EOL;
        $str .= '</ul>' . PHP_EOL;
        return $str;
    }

    public function __toString()
    {
        /** @noinspection MagicMethodsValidityInspection */
        return $this->renderAsHtml();
    }
}