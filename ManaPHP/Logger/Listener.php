<?php
namespace ManaPHP\Logger;

class Listener extends \ManaPHP\Event\Listener
{
    /**
     * @param \ManaPHP\LoggerInterface $logger
     * @param \ManaPHP\Logger\Log      $log
     *
     * @return void|false
     */
    public function onLog($logger, $log)
    {

    }
}