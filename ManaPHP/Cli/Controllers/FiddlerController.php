<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

/**
 * Class FiddlerController
 *
 * @package ManaPHP\Cli\Controllers
 * @property-read \ManaPHP\Debugging\FiddlerPlugin $fiddlerPlugin
 */
class FiddlerController extends Controller
{
    /**
     * @param \ManaPHP\DiInterface $di
     */
    public function setDi($di)
    {
        if (!$di->has('fiddlerPlugin')) {
            $di->setShared('fiddlerPlugin', 'ManaPHP\Plugins\FiddlerPlugin');
        }
        parent::setDi($di);
    }

    /**
     * fiddler app
     *
     * @param string $id app id
     * @param string $ip client ip
     */
    public function defaultAction($id = '', $ip = '')
    {
        $options = [];

        if ($id) {
            $options['id'] = $id;
        }

        if ($ip) {
            $options['ip'] = $ip;
        }

        $this->fiddlerPlugin->subscribe($options);
    }
}
