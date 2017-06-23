<?php
namespace ManaPHP\Text;

/**
 * Class ManaPHP\Text\Crossword
 *
 * @package crossword
 */
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
            $words = explode(',', $words);
        }

        $word = strtolower($word);

        $likeWord = null;
        $likeCount = 0;
        /** @noinspection ForeachSourceInspection */
        foreach ($words as $v) {
            if ($v === $word || strtolower($v) === $word) {
                return $v;
            }

            if ($likeCount <= 1 && strpos($v, $word) === 0) {
                $likeWord = $v;
                $likeCount++;
            }
        }

        if ($likeCount === 1) {
            return $likeWord;
        }

        /** @noinspection ForeachSourceInspection */
        foreach ($words as $k => $v) {
            if (strspn($word, strtolower($v)) !== strlen($word)) {
                unset($words[$k]);
            }
        }

        if (count($words) === 0) {
            return false;
        } elseif (count($words) === 1) {
            return array_values($words)[0];
        } else {
            return false;
        }
    }
}