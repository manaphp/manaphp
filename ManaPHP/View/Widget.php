<?php
namespace ManaPHP\View;

use ManaPHP\Component;

/**
 * Class ManaPHP\View\Widget
 *
 * @package widget
 *
 * @property-read \ManaPHP\UrlInterface      $url
 * @property-read \ManaPHP\CacheInterface    $cache
 * @property-read \ManaPHP\RendererInterface $renderer
 * @property-read \ManaPHP\DbInterface       $db
 */
abstract class Widget extends Component implements WidgetInterface
{

}