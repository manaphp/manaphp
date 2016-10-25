<?php
namespace ManaPHP\Text;

/**
 * Interface ManaPHP\Text\CrosswordInterface
 *
 * @package crossword
 */
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