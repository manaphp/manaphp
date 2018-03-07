<?php
namespace ManaPHP\Dom;

class Query
{
    /**
     * @var \DOMDocument
     */
    protected $_dom;

    /**
     * @var \DOMXPath
     */
    protected $_xpath;

    /**
     * @var \ManaPHP\Dom\CssToXPath
     */
    protected $_cssToXPath;

    /**
     * Query constructor.
     *
     * @param \DOMDocument $domDocument
     */
    public function __construct($domDocument)
    {
        $this->_dom = $domDocument;

        $this->_xpath = new \DOMXPath($domDocument);

        $this->_cssToXPath = new CssToXPath();
    }

    /**
     * @param string   $expression
     * @param \DOMNode $context
     *
     * @return \DOMNodeList
     */
    public function xpath($expression, $context)
    {
        return $this->_xpath->query($expression, $context);
    }

    /**
     * @param string   $expression
     * @param \DOMNode $context
     *
     * @return \DOMNodeList
     */
    public function css($expression, $context)
    {
        $xpath = $this->_cssToXPath->transform($expression);
        return $this->xpath($xpath, $context);
    }
}