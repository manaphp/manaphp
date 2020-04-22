<?php

namespace ManaPHP\Security;

interface HtmlPurifierInterface
{
    /**
     * @param string $html
     * @param array  $allowedTags
     * @param array  $allowedAttributes
     *
     * @return string
     */
    public function purify($html, $allowedTags = null, $allowedAttributes = null);
}