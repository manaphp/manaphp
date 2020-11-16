<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;

/**
 * Class FiddlerCommand
 *
 * @package ManaPHP\Cli\Commands
 * @property-read \ManaPHP\Debugging\FiddlerPlugin $fiddlerPlugin
 */
class FiddlerCommand extends Command
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
