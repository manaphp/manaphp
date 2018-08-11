<?php
namespace ManaPHP\View;

interface HtmlInterface
{
    /**
     * @param string $name
     * @param array  $data
     *
     * @return string
     */
    public function render($name, $data);
}