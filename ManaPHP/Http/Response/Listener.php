<?php
namespace ManaPHP\Http\Response;

class Listener extends \ManaPHP\Event\Listener
{
    /**
     * @param \ManaPHP\Http\ResponseInterface $response
     *
     * @return void
     */
    public function onBeforeSend($response)
    {

    }

    /**
     * @param \ManaPHP\Http\ResponseInterface $response
     *
     * @return void
     */
    public function onAfterSend($response)
    {

    }
}