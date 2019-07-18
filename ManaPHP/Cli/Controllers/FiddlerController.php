<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

/**
 * Class FiddlerController
 * @package ManaPHP\Cli\Controllers
 * @property-read \ManaPHP\Plugins\FiddlerPlugin $fiddlerPlugin
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
     * fiddler web app
     *
     * @param string $id application id
     * @param string $ip client ip
     */
    public function webCommand($id = '', $ip = '')
    {
        $options = [];

        if ($id) {
            $options['id'] = $id;
        }

        if ($ip) {
            $options['ip'] = $ip;
        }

        $this->fiddlerPlugin->subscribeWeb($options);
    }

    /**
     * fiddler cli app
     *
     * @param string $id application id
     */
    public function cliCommand($id = '')
    {
        $options = [];

        if ($id) {
            $options['id'] = $id;
        }

        $this->fiddlerPlugin->subscribeCli($options);
    }
}