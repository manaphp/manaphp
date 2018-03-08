<?php
namespace ManaPHP\Dom;

class SelectorList implements \Iterator
{
    /**
     * @var array
     */
    protected $_full_xpath = [];

    /**
     * @var \ManaPHP\Dom\Selector[]
     */
    protected $_selectors;

    /**
     * @var int
     */
    protected $_position = 0;

    /**
     * SelectorList constructor.
     *
     * @param \ManaPHP\Dom\Selector[] $selectors
     * @param array                   $xpaths
     */
    public function __construct($selectors, $xpaths)
    {
        $this->_selectors = $selectors;
        $this->_full_xpath = $xpaths;
    }

    /**
     * @return string[]
     */
    public function extract()
    {
        $data = [];
        foreach ($this->_selectors as $node) {
            $data[] = $node->extract();
        }

        return $data;
    }

    /**
     * @param string $default
     *
     * @return string
     */
    public function extract_first($default = null)
    {
        return $this->_selectors ? $this->_selectors[0]->extract() : $default;
    }

    /**
     * @param string $path
     *
     * @return static
     */
    public function xpath($path)
    {
        if ($path === '') {
            return clone $this;
        }

        $new_selectors = [];
        foreach ($this->_selectors as $selector) {
            $r = $selector->xpath($path);
            if ($r) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $new_selectors = array_merge($new_selectors, $r->_selectors);
            }
        }

        return new SelectorList($new_selectors, array_merge($this->_full_xpath, [$path]));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function css($css)
    {
        if ($css === '') {
            return clone $this;
        }

        return $this->xpath((new CssToXPath())->transform($css));
    }

    /**
     * @param string          $field
     * @param callable|string $func
     *
     * @return static
     */
    public function filter($field = null, $func = null)
    {
        if ($field === '') {
            return clone $this;
        }

        if ($field !== null && $field[0] === '!') {
            $field = substr($field, 1);
            $not = true;
        } else {
            $not = false;
        }

        if (is_string($func)) {
            $is_preg = in_array($func[0], ['@', '#'], true) && substr_count($func, $func[0]) >= 2;
        }

        $new_selectors = [];
        foreach ($this->_selectors as $selector) {
            if ($field === null) {
                $value = $selector;
            } elseif (strpos($field, '()') !== false) {
                if ($field === 'text()') {
                    $value = $selector->text();
                } elseif
                ($field === 'html()') {
                    $value = $selector->html();
                } elseif
                ($field === 'node()') {
                    $value = $selector->node();
                } else {
                    throw new Exception('invalid field');
                }
            } else {
                $value = $selector->attr($field);
            }

            if ($func === null) {
                $r = $value !== '';
            } elseif (is_string($func)) {
                $r = $is_preg ? preg_match($func, $value) : strpos($value, $func) !== false;
            } else {
                $r = $func($value);
            }

            if (($not && !$r) || (!$not && $r)) {
                $new_selectors[] = $selector;
            }
        }

        return new SelectorList($new_selectors, $this->_full_xpath);
    }

    /**
     * @param string $regex
     *
     * @return array
     */
    public function re($regex)
    {
        $data = [];
        foreach ($this->_selectors as $element) {
            if (preg_match($regex, $element->extract(), $match)) {
                $data[] = $match[1];
            } else {
                $data[] = null;
            }
        }

        return $data;
    }

    /**
     * @param string $attr
     * @param string $defaultValue
     *
     * @return string[][]
     */
    public function attr($attr = null, $defaultValue = null)
    {
        $data = [];

        foreach ($this->_selectors as $selector) {
            $data[] = $selector->attr($attr, $defaultValue);
        }

        return $data;
    }

    /**
     * @return string[]
     */
    public function text()
    {
        $data = [];
        foreach ($this->_selectors as $selector) {
            $data[] = $selector->text();
        }

        return $data;
    }

    /**
     * @param bool $as_string
     *
     * @return array
     */
    public function element($as_string = false)
    {
        $data = [];
        foreach ($this->_selectors as $selector) {
            $data[] = $selector->element($as_string);
        }

        return $data;
    }

    /**
     * @return string[]
     */
    public function html()
    {
        $data = [];
        foreach ($this->_selectors as $selector) {
            $data[] = $selector->html();
        }

        return $data;
    }

    /**
     * @return \DOMNode[]
     */
    public function node()
    {
        $data = [];
        foreach ($this->_selectors as $selector) {
            $data[] = $selector->node();
        }

        return $data;
    }

    /**
     * @param string $regex
     * @param string $default
     *
     * @return string
     */
    public function re_first($regex, $default = null)
    {
        $r = $this->re($regex);
        return $r ? $r[0] : $default;
    }

    public function rewind()
    {
        $this->_position = 0;
    }

    public function current()
    {
        return new Selector($this->_selectors[0]);
    }

    public function key()
    {
        return $this->_position;
    }

    public function next()
    {
        $this->_position++;
    }

    public function valid()
    {
        return isset($this->_selectors[$this->_position]);
    }

    public function __toString()
    {
        return json_encode($this->extract(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

}