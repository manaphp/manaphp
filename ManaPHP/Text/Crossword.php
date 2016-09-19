<?php
namespace ManaPHP\Text;

class Crossword
{
    /**
     * @param string|array $words
     * @param string       $word
     *
     * @return string|false
     */
    public function guess($words, $word)
    {
        if (is_string($words)) {
            if (strpos($words, ',') !== false) {
                $words = explode(',', $words);
            } else {
                $words = [$words];
            }
        }

        foreach ($words as $k => $v) {
            $words[$k] = strtolower($v);
        }

        $word = strtolower($word);

        foreach (str_split($word, 1) as $c) {
            foreach ($words as $k => $v) {
                if (strpos($v, $c) === false) {
                    unset($words[$k]);
                }
            }

            if (count($words) === 0) {
                return false;
            } elseif (count($words) === 1) {
                return array_values($words)[0];
            }
        }

        return false;
    }
}