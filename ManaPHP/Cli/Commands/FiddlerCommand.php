<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;

/**
 * @property-read \ManaPHP\Debugging\FiddlerPlugin $fiddlerPlugin
 */
class FiddlerCommand extends Command
{
    /**
     * fiddler app
     *
     * @param string $id app id
     * @param string $ip client ip
     *
     * @return void
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
