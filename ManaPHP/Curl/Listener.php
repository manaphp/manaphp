<?php
namespace ManaPHP\Curl;

class Listener extends \ManaPHP\Event\Listener
{
    /**
     * @param \ManaPHP\Curl\EasyInterface $curl
     * @param array                       $data
     *
     * @return void
     */
    public function beforeRequest($curl, $data)
    {

    }

    /**
     * @param \ManaPHP\Curl\EasyInterface $curl
     * @param array                       $data
     *
     * @return void
     */
    public function afterRequest($curl, $data)
    {

    }
}