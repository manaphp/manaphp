<?php

namespace App;

class Application extends \ManaPHP\Ws\Application
{
    public function authenticate()
    {
//        $this->wspClient->pushToId(2, 'abczc', 'admin');
//        $this->wspClient->pushToName('mark', 'mark', 'admin');
//        $this->wspClient->pushToRole('admin', 'abc', 'admin');
//        $this->wspClient->pushToAll('abc', 'admin');
//        $this->wspClient->broadcast(date('Y-m-d H:i:s'),'admin');
//
        $this->identity->setClaims(['user_id' => 1, 'user_name' => 'mark', 'role' => 'admin']);
    }
}
