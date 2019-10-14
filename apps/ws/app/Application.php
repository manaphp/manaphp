<?php
namespace App;

use ManaPHP\WebSocket\Processes\PusherProcess;

class Application extends \ManaPHP\WebSocket\Application
{
    public function authenticate()
    {
//        $this->wsPusher->pushToId(2, 'abczc', 'admin');
//        $this->wsPusher->pushToName('mark', 'mark', 'admin');
//        $this->wsPusher->pushToRole('admin', 'abc', 'admin');
//        $this->wsPusher->pushToAll('abc', 'admin');
//        $this->wsPusher->broadcast(date('Y-m-d H:i:s'),'admin');
//
        $this->identity->setClaims(['user_id' => 1, 'user_name' => 'mark', 'role' => 'admin']);
    }

    public function getProcesses()
    {
        return ['pusherProcess' => ['class' => PusherProcess::class, 'endpoint' => 'admin']];
    }
}