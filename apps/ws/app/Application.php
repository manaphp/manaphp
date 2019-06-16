<?php
namespace App;

use App\Processes\PusherProcess;

class Application extends \ManaPHP\WebSocket\Application
{
    public function getProcesses()
    {
        return ['pusherProcess' => ['class' => PusherProcess::class, 'endpoint' => 'admin']];
    }
}