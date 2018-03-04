<?php
namespace ManaPHP\Dom;

class SelectorList implements \Iterator
{
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
     */
    public function __construct($selectors)
    {
        $this->_selectors = $selectors;
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
        $new_selectors = [];
        foreach ($this->_selectors as $selector) {
            $r = $selector->xpath($path);
            if ($r) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $new_selectors = array_merge($new_selectors, $r->_selectors);
            }
        }

        return new SelectorList($new_selectors);
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function css($css)
    {
        return $this->xpath((new CssToXpath())->transform($css));
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
     * @return string[][]
     */
    public function attr()
    {
        $data = [];

        foreach ($this->_selectors as $selector) {
            $data[] = $selector->attr();
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