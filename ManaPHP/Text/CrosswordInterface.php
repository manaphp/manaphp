<?php
namespace ManaPHP\Text;

interface CrosswordInterface
{
    /**
     * @param string|array $words
     * @param string       $word
     *
     * @return string|false
     */
    public function guess($words, $word);
}