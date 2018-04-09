<?php
namespace ManaPHP\View;

use ManaPHP\Component;

/**
 * Class ManaPHP\View\Widget
 *
 * @package widget
 *
 * @property \ManaPHP\View\UrlInterface $url
 * @property \ManaPHP\CacheInterface    $cache
 * @property \ManaPHP\RendererInterface $renderer
 * @property \ManaPHP\DbInterface       $db
 */
abstract class Widget extends Component implements WidgetInterface
{

}