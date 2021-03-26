<?php

namespace App;

class Application extends \ManaPHP\Socket\Application
{
    public function onReceive($fd, $data)
    {
        parent::onReceive($fd, substr($data, 4));
    }

    public function send($fd, $data)
    {
        return parent::send($fd, pack('L', strlen($data)) . $data);
    }
}