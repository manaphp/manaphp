<?php

namespace ManaPHP\I18n;

interface LocaleInterface
{
    /**
     * @return string
     */
    public function get();

    /**
     * @param string $locale
     *
     * @return static
     */
    public function set($locale);
}