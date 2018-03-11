<?php
namespace ManaPHP\Dom;

class SelectorList implements \IteratorAggregate, \Countable
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
     * @var \ManaPHP\Dom\Selector
     */
    protected $_root;

    /**
     * SelectorList constructor.
     *
     * @param \ManaPHP\Dom\Selector[]                         $selectors
     * @param \ManaPHP\Dom\Selector|\ManaPHP\Dom\SelectorList $root
     */
    public function __construct($selectors, $root)
    {
        $this->_selectors = $selectors;
        if ($root instanceof SelectorList) {
            $this->_root = $root->_root;
        } else {
            $this->_root = $root;
        }
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

        return new SelectorList($new_selectors, $this);
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

        $new_selectors = [];
        foreach ($this->_selectors as $selector) {
            $r = $selector->css($css);
            if ($r) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $new_selectors = array_merge($new_selectors, $r->_selectors);
            }
        }

        return new SelectorList($new_selectors, $this);
    }

    /**
     * @param string|static $selectors
     *
     * @return static
     */
    public function add($selectors)
    {
        if (is_string($selectors)) {
            $selectors = $this->_root->find($selectors);
        }

        if (!$selectors->_selectors) {
            return clone $this;
        }

        if (!$this->_selectors) {
            return clone $selectors;
        }

        return new SelectorList(array_unique(array_merge($this->_selectors, $selectors->_selectors)), $this);
    }

    /**@param string $css
     *
     * @return static
     */
    public function children($css = null)
    {
        return $this->css('child::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function closest($css = null)
    {
        return $this->css('ancestor-or-self::' . ($css === null ? '*' : $css));
    }

    /**
     * @param callable $func
     *
     * @return static
     */
    public function each($func)
    {
        foreach ($this->_selectors as $index => $selector) {
            $r = $func($selector, $index);
            if ($r !== null) {
                break;
            }
        }

        return $this;
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function eq($index)
    {
        if ($index < 0) {
            $index = count($this->_selectors) + $index;
        }

        return new SelectorList(isset($this->_selectors[$index]) ? [$this->_selectors[$index]] : [], $this);
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

        return new SelectorList($new_selectors, $this);
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function find($css = null)
    {
        return $this->css('descendant::' . ($css === null ? '*' : $css));
    }

    /**
     * @return \ManaPHP\Dom\Selector|null
     */
    public function first()
    {
        return isset($this->_selectors[0]) ? $this->_selectors[0] : null;
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function has($css)
    {
        return $this->css('child::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return bool
     */
    public function is($css)
    {
        $r = $this->css('self::' . ($css === null ? '*' : $css) . '[1]');
        return count($r->_selectors) > 0;
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function next($css = null)
    {
        return $this->css('following-sibling::' . ($css === null ? '*' : $css) . '[1]');
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function nextAll($css = null)
    {
        return $this->css('following-sibling::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function not($css)
    {
        return $this->css('!self::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function parent($css = null)
    {
        if ($css === '') {
            return clone  $this;
        }

        return $this->css('parent::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function parents($css = null)
    {
        return $this->css('ancestor::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function prev($css = null)
    {
        return $this->css('preceding-sibling::' . ($css === null ? '*' : $css) . '[1]');
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function prevAll($css = null)
    {
        return $this->css('preceding-sibling::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function siblings($css = null)
    {
        $new_selectors = [];
        foreach ($this->_selectors as $selector) {
            $r = $selector->css('parent::' . ($css ?: '*'));
            if ($r) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $new_selectors = array_merge($new_selectors, array_diff($r->_selectors, [$selector]));
            }
        }

        return new SelectorList(array_unique($new_selectors), $this);
    }

    /**
     * @param int $offset
     * @param int $length
     *
     * @return static
     */
    public function slice($offset, $length = null)
    {
        $new_selectors = array_slice($this->_selectors, $offset, $length);
        return new SelectorList($new_selectors, $this);
    }

    /**
     * @return static
     */
    public function last()
    {
        return $this->eq(-1);
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
     *
     * @return string[]|string
     */
    public function name()
    {
        $data = [];

        foreach ($this->_selectors as $selector) {
            $data[] = $selector->name();
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

    /**
     * @return \ArrayIterator|\Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_selectors);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->_selectors);
    }

    /**
     * @return \ManaPHP\Dom\Selector[]
     */
    public function toArray()
    {
        return $this->_selectors;
    }

    public function __toString()
    {
        return json_encode($this->extract(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}