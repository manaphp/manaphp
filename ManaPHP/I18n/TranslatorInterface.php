<?php

namespace ManaPHP\I18n;

interface TranslatorInterface
{
    /**
     * @param string $template
     * @param array  $placeholders
     *
     * @return string
     */
    public function translate($template, $placeholders = null);
}