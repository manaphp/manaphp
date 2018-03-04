<?php
namespace ManaPHP\Dom;

class Selector
{
    /**
     * @var \SimpleXMLElement
     */
    protected $_node;

    /**
     * @var string
     */
    protected $_xpath;

    /**
     * @var string
     */
    protected $_css;

    /**
     * Selector constructor.
     *
     * @param string|\SimpleXMLElement $text
     */
    public function __construct($text)
    {
        if (is_string($text)) {
            $this->_node = new \SimpleXMLElement($text);
        } else {
            $this->_node = $text;
        }
    }

    /**
     * @param string|array $query
     *
     * @return \ManaPHP\Dom\SelectorList
     */
    public function xpath($query)
    {
        if (is_array($query)) {
            $tr = [];

            /** @noinspection ForeachSourceInspection */
            foreach ($query as $k => $v) {
                $tr['$' . $k] = is_int($v) ? $v : "'$v'";
            }

            $query = strtr($query[0], $tr);
        }

        $this->_xpath = $query;

        $selectors = [];
        foreach ($this->_node->xpath($query) as $element) {
            $selectors[] = new Selector($element);
        }
        return new SelectorList($selectors);
    }

    /**
     * @param string $css
     *
     * @return \ManaPHP\Dom\SelectorList
     */
    public function css($css)
    {
        $this->_css = $css;
        return $this->xpath((new CssToXPath())->transform($css));
    }

    /**
     * @return string
     */
    public function extract()
    {
        return (string)$this->_node;
    }

    /**
     * @return array
     */
    public function attr()
    {
        $data = [];

        foreach ($this->_node->attributes() as $attribute) {
            $data[$attribute->getName()] = (string)$attribute;
        }

        return $data;
    }

    /**
     * @return string
     */
    public function text()
    {
        return (string)$this->_node;
    }

    /**@param bool $as_string
     *
     * @return string|array
     */
    public function element($as_string = false)
    {
        return $as_string ? $this->_node->asXML() : ['html' => $this->_node->asXML(), 'name' => $this->_node->getName(), 'text' => $this->text(), 'attr' => $this->attr()];
    }

    /**
     * @return string
     */
    public function html()
    {
        return $this->_node->asXML();
    }
}