<?php
namespace ManaPHP\Mailer;

class Listener extends \ManaPHP\Event\Listener
{
    /**
     * @param \ManaPHP\MailerInterface $mailer
     * @param array                    $data
     *
     * @return void
     */
    public function onBeforeSend($mailer, $data)
    {

    }

    /**
     * @param \ManaPHP\MailerInterface $mailer
     * @param array                    $data
     *
     * @return void
     */
    public function onAfterSend($mailer, $data)
    {

    }
}