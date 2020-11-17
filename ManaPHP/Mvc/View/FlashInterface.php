<?php

namespace ManaPHP\Mvc\View;

interface FlashInterface
{
    /**
     * Shows a HTML error message
     *
     * @param string $message
     *
     * @return void
     */
    public function error($message);

    /**
     * Shows a HTML notice/information message
     *
     * @param string $message
     *
     * @return void
     */
    public function notice($message);

    /**
     * Shows a HTML success message
     *
     * @param string $message
     *
     * @return void
     */
    public function success($message);

    /**
     * Shows a HTML warning message
     *
     * @param string $message
     *
     * @return void
     */
    public function warning($message);

    /**
     * Prints the messages in the session flasher
     *
     * @param bool $remove
     *
     * @return void
     */
    public function output($remove = true);
}