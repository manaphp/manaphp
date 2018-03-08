<?php
namespace ManaPHP\Dom;

use ManaPHP\Dom\Query\Exception as QueryException;

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
        $r = @$this->_xpath->query($expression, $context);
        if ($r === false) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new QueryException(['`:xpath` xpath is invalid expression', 'xpath' => $expression]);
        }

        return $r;
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