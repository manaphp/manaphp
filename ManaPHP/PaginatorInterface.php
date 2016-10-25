<?php
namespace ManaPHP;

/**
 * Interface ManaPHP\PaginatorInterface
 *
 * @package paginator
 */
interface PaginatorInterface
{
    /**
     * @param int $count
     * @param int $page
     * @param int $size
     *
     * @return static
     */
    public function paginate($count, $page, $size);

    /**
     * @param  false|string $itemsName
     *
     * @return array
     */
    public function renderAsArray($itemsName = 'items');

    /**
     * @param string $urlTemplate
     *
     * @return string
     */
    public function renderAsHtml($urlTemplate = null);
}