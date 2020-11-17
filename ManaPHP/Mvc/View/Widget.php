<?php

namespace ManaPHP\Mvc\View;

use ManaPHP\Component;
use ManaPHP\Logging\Logger\LogCategorizable;

/**
 * @property-read \ManaPHP\Http\UrlInterface      $url
 * @property-read \ManaPHP\Caching\CacheInterface $cache
 * @property-read \ManaPHP\Html\RendererInterface $renderer
 * @property-read \ManaPHP\Data\DbInterface       $db
 */
abstract class Widget extends Component implements WidgetInterface, LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Widget');
    }
}