<?php

namespace ManaPHP\I18n;

interface TranslatorInterface
{
    /**
     * @param string $locale
     *
     * @return static
     */
    public function setLocale($locale);

    /**
     * @return string
     */
    public function getLocale();

    /**
     * @param string $template
     * @param array  $placeholders
     *
     * @return string
     */
    public function translate($template, $placeholders = null);
}