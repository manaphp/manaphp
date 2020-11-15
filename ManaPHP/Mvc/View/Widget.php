<?php

namespace ManaPHP\Mvc\View;

use ManaPHP\Component;
use ManaPHP\Logger\LogCategorizable;

/**
 * Class ManaPHP\Mvc\View\Widget
 *
 * @package widget
 *
 * @property-read \ManaPHP\Http\UrlInterface      $url
 * @property-read \ManaPHP\Caching\CacheInterface $cache
 * @property-read \ManaPHP\RendererInterface      $renderer
 * @property-read \ManaPHP\DbInterface            $db
 */
abstract class Widget extends Component implements WidgetInterface, LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Widget');
    }
}