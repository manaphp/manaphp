<?php
namespace ManaPHP\I18n;

interface TranslationInterface
{
    /**
     * @param string $messageId
     * @param array  $bind
     *
     * @return string
     */
    public function translate($messageId, $bind = []);
}