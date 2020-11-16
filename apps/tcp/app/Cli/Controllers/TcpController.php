<?php

namespace App\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Exception;

class TcpController extends Controller
{
    public function echoAction($str = 'Hello World')
    {
        $server = env('SOCKET_SERVER');
        $parts = parse_url($server);
        $host = $parts['scheme'] . '://' . $parts['host'];

        if (($socket = fsockopen($host, $parts['port'])) === false) {
            throw new Exception("open $server failed");
        }

        $this->console->writeLn('sending: ' . $str);
        fwrite($socket, pack('L', strlen($str)) . $str);

        $header = fread($socket, 4);
        $this->console->writeLn('receiving header: ' . bin2hex($header));
        $len = unpack('L', $header)[1];
        $this->console->writeLn('receiving length: ' . $len);

        $data = fread($socket, $len);
        $this->console->writeLn('receiving: ' . $data);
    }
}