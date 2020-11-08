<?php

namespace ManaPHP\Dom;

class CssToXPath
{
    public function transform($path)
    {
        $path = $this->_transform($path);
        if (str_contains($path, ':')) {

            $path = preg_replace_callback(
                '#:(eq|gt|lt)\((-?\d+)\)#', static function ($match) {
                $word = $match[1];
                if ($word === 'eq') {
                    if ($match[2] >= 0) {
                        return '[' . ($match[2] + 1) . ']';
                    } else {
                        return "[last()$match[2]]";
                    }
                } elseif ($word === 'gt' || $word === 'lt') {
                    if ($match[2] >= 0) {
                        return '[position()' . ($word === 'gt' ? '>' : '<') . ($match[2] + 1) . ']';
                    } else {
                        return '[position()' . ($word === 'gt' ? '>' : '<') . "last()$match[2]]";
                    }
                } else {
                    return '';
                }
            }, $path
            );

            $path = preg_replace(['#:contains\((["\'])([^\'"]+)\\1\)#'], ["[contains(.,'\\2')]"], $path);
            $path = strtr(
                $path, [
                    ':header'      => '*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]',
                    ':first'       => '[first()]',
                    ':last'        => '[last()]',
                    ':even'        => '[position() mod 2 = 0]',
                    ':odd'         => '[position() mod 2 = 1]',
                    ':contains('   => 'contains(.,',
                    ':not('        => 'not(',
                    ':empty'       => '[not(* or text())]',
                    ':only-child'  => '[last()=1]',
                    ':first-child' => '[position()=1]',
                    ':last-child'  => '[position()=last()]'
                ]
            );
        }

        return $path;
    }

    /**
     * Transform CSS expression to XPath
     *
     * @param string $path_src
     *
     * @return string
     */
    protected function _transform($path_src)
    {
        $path = (string)$path_src;

        if (str_contains($path, ',')) {
            $paths = explode(',', $path);
            $expressions = [];
            foreach ($paths as $path) {
                $xpath = $this->transform(trim($path));
                if (is_string($xpath)) {
                    $expressions[] = $xpath;
                } elseif (is_array($xpath)) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $expressions = array_merge($expressions, $xpath);
                }
            }
            return implode('|', $expressions);
        }

        $paths = ['//'];
        $path = preg_replace('|\s+>\s+|', '>', $path);
        $segments = preg_split('/\s+/', $path);
        foreach ($segments as $key => $segment) {
            $pathSegment = static::_tokenize($segment);
            if (0 === $key) {
                if (str_starts_with($pathSegment, '[contains(')) {
                    $paths[0] .= '*' . ltrim($pathSegment, '*');
                } else {
                    $paths[0] .= $pathSegment;
                }
                continue;
            }
            if (str_starts_with($pathSegment, '[contains(')) {
                foreach ($paths as $pathKey => $xpath) {
                    $paths[$pathKey] .= '//*' . ltrim($pathSegment, '*');
                    $paths[] = $xpath . $pathSegment;
                }
            } else {
                foreach ($paths as $pathKey => $xpath) {
                    $paths[$pathKey] .= '//' . $pathSegment;
                }
            }
        }

        if (1 === count($paths)) {
            return $paths[0];
        }
        return implode('|', $paths);
    }

    /**
     * Tokenize CSS expressions to XPath
     *
     * @param string $expression_src
     *
     * @return string
     */
    protected static function _tokenize($expression_src)
    {
        // Child selectors
        $expression = str_replace('>', '/', $expression_src);

        // IDs
        $expression = preg_replace('|#([a-z][\w\-]*)|i', "[@id='$1']", $expression);
        $expression = preg_replace('|(?<![\w\-])(\[@id=)|i', '*$1', $expression);

        // arbitrary attribute strict equality
        $expression = preg_replace_callback(
        /**@lang text */ '|\[@?([\w\-]*)([~*^$|!])?=([^\[]+)\]|',
            static function ($matches) {
                $attr = strtolower($matches[1]);
                $type = $matches[2];
                $val = trim($matches[3], "'\" \t");

                $field = ($attr === '' ? 'text()' : '@' . $attr);

                $items = [];
                foreach (explode(str_contains($val, '|') ? '|' : '&', $val) as $word) {
                    if ($type === '') {
                        $items[] = "$field='$word'";
                    } elseif ($type === '~') {
                        $items[] = "contains(concat(' ', normalize-space($field), ' '), ' $word ')";
                    } elseif ($type === '|') {
                        $item = "$field='$word' or starts-with($field,'$word-')";
                        $items[] = $word === $val ? $item : "($item)";
                    } elseif ($type === '!') {
                        $item = "not($field) or $field!='$word'";
                        $items[] = $word === $val ? $item : "($item)";
                    } else {
                        $p2t = ['*' => 'contains', '^' => 'starts-with', '$' => 'ends-with'];
                        $items[] = $p2t[$type] . "($field, '$word')";
                    }
                }
                return '[' . implode(str_contains($val, '|') ? ' or ' : ' and ', $items) . ']';
            },
            $expression
        );

        //attribute contains specified content
        $expression = preg_replace_callback(
        /**@lang text */ '|\[(!?)([a-z][\w\-|&]*)\]|i',
            static function ($matches) {
                $val = $matches[2];
                $op = str_contains($val, '|') ? '|' : '&';

                $items = [];
                foreach (explode($op, $val) as $word) {
                    $items[] = '@' . $word;
                }

                $r = '[' . implode($op === '|' ? ' or ' : ' and ', $items) . ']';
                return $matches[1] === '!' ? "not($r)" : $r;
            },
            $expression
        );
        // Classes
        if (!str_contains($expression, '[@')) {
            $expression = preg_replace(
                '|\.([a-z][\w\-]*)|i',
                "[contains(concat(' ', normalize-space(@class), ' '), ' \$1 ')]",
                $expression
            );
        }

        /** ZF-9764 -- remove double asterisk */
        $expression = str_replace('**', '*', $expression);

        return $expression;
    }
}