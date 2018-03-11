<?php
namespace ManaPHP\Dom;

use ManaPHP\Component;
use ManaPHP\Dom\Document\Exception as DocumentException;

/**
 * Class Document
 * @package ManaPHP\Dom
 *
 * @property \ManaPHP\Http\Client $httpClient
 */
class Document extends Component
{
    /**
     * @var string
     */
    protected $_source;

    /**
     * @var string
     */
    protected $_str;

    /**
     * @var \DOMDocument
     */
    protected $_dom;

    /**
     * @var \ManaPHP\Dom\Query
     */
    protected $_query;

    /**
     * @var array
     */
    protected $_domErrors = [];

    /**
     * Document constructor.
     *
     * @param string $str
     */
    public function __construct($str = null)
    {
        if ($str !== null) {
            $this->load($str);
        }
    }

    /**
     * @param string $str
     *
     * @return static
     */
    public function load($str)
    {
        if (preg_match('#^https?://#', $str)) {
            $this->loadUrl($str);
        } elseif ($str[0] === '@' || $str[0] === '/' || $str[1] === ':') {
            $this->loadFile($str);
        } else {
            $this->loadString($str);
        }

        return $this;
    }

    /**
     * @param string $file
     *
     * @return static
     */
    public function loadFile($file)
    {
        $this->_source = $file;
        $str = $this->filesystem->fileGet($file);
        return $this->loadString($str);
    }

    /**
     * @param string $url
     *
     * @return static
     */
    public function loadUrl($url)
    {
        $this->_source = $url;

        $this->httpClient->get($url);
        $str = $this->httpClient->getResponseBody();
        return $this->loadString($str);
    }

    /**
     * @param string $str
     *
     * @return static
     */
    public function loadString($str)
    {
        $this->_str = $str;

        $this->_dom = new \DOMDocument();
        libxml_clear_errors();
        $old_use_internal_errors = libxml_use_internal_errors(true);
        $old_disable_entity_loader = libxml_disable_entity_loader(true);

        /** @noinspection SubStrUsedAsStrPosInspection */
        if (substr($str, 0, 5) === '<?xml') {
            $r = $this->_dom->loadXML($str);
        } else {
            $r = $this->_dom->loadHTML($str);
        }

        $this->_domErrors = libxml_get_errors();
        libxml_clear_errors();

        libxml_disable_entity_loader($old_disable_entity_loader);
        libxml_use_internal_errors($old_use_internal_errors);

        if (!$r) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new DocumentException('xx');
        }

        $this->_query = new Query($this->_dom);
		
        return $this;
    }

    /**
     * @return bool $raw
     *
     * @return string
     */
    public function getString($raw = true)
    {
        return $raw ? $this->_str : $this->_dom->saveHTML($this->_dom->documentElement);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_domErrors;
    }

    /**
     * @param string $file
     *
     * @return static
     */
    public function save($file)
    {
        $this->filesystem->filePut($file, $this->getString());

        return $this;
    }

    /**
     * @return \ManaPHP\Dom\Query
     */
    public function getQuery()
    {
        return $this->_query;
    }
}