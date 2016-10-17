<?php
namespace ManaPHP\Mvc;

use ManaPHP\Component;

/**
 * Class Widget
 *
 * @package ManaPHP\Mvc
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