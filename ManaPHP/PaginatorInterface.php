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
     * @param array $displayText
     *
     * @return static
     */
    public function setDisplayText($displayText);

    /**
     * @param int $number
     *
     * @return static
     */
    public function setNumberOfLinks($number);

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