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
     * @param int $number
     *
     * @return static
     */
    public function setLinks($number);

    /**
     * @param int $count
     * @param int $size
     * @param int $page
     *
     * @return static
     */
    public function paginate($count, $size = null, $page = null);

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

    /**
     * @return array
     */
    public function toArray();
}