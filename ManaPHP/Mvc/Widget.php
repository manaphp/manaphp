<?php
namespace ManaPHP\Mvc;

use ManaPHP\Component;

/**
 * Class ManaPHP\Mvc\Widget
 *
 * @package widget
 *
 * @property \ManaPHP\Mvc\UrlInterface           $url
 * @property \ManaPHP\CacheInterface             $cache
 * @property \ManaPHP\RendererInterface          $renderer
 * @property \ManaPHP\Mvc\Model\ManagerInterface $modelsManager
 * @property \ManaPHP\DbInterface                $db
 */
abstract class Widget extends Component implements WidgetInterface
{

}