<?php

namespace ManaPHP\Security;

use DOMDocument;
use ManaPHP\Component;

class HtmlPurifier extends Component implements HtmlPurifierInterface
{
    /**
     * @var string
     */
    protected $_allowedTags = ',a,b,br,code,div,i,img,s,strike,strong,samp,span,sub,sup,small,pre,p,q,div,em,h1,h2,h3,h4,h5,h6,table,u,ul,ol,tr,th,td,hr,li,';

    /**
     * @var string
     */
    protected $_allowedAttributes = ',title,src,href,width,height,alt,target,';

    /**
     * @var callable
     */
    protected $_filter;

    /**
     * HtmlPurifier constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['allowedTags'])) {
            $this->_allowedTags = ',' . implode(',', $options['allowedTags']) . ',';
        }

        if (isset($options['allowedAttributes'])) {
            $this->_allowedAttributes = ',' . implode(',', $options['allowedAttributes']) . ',';
        }

        if (isset($options['filter'])) {
            $this->_filter = $options['filter'];
        }
    }

    /**
     * @param \DOMNode[] $nodes
     * @param string     $allowedTags
     * @param string     $allowedAttributes
     */
    protected function _purify($nodes, $allowedTags, $allowedAttributes)
    {
        /** @var \DOMNode|\DOMDocument|\DOMElement $node */
        foreach ($nodes as $node) {
            if ($node->nodeType === XML_TEXT_NODE) {
                continue;
            }

            if (!in_array($node->nodeType, [XML_ELEMENT_NODE, XML_ATTRIBUTE_NODE, XML_DOCUMENT_NODE, XML_TEXT_NODE, XML_DOCUMENT_TYPE_NODE], true)) {
                $node->parentNode->removeChild($node);
                continue;
            }

            $tag = $node->nodeName;

            if (strpos($allowedTags, ',' . $tag . ',') === false) {
                $node->parentNode->removeChild($node);
                continue;
            }

            if ($node->hasAttributes()) {
                foreach (iterator_to_array($node->attributes) as $attributeNode) {

                    $attributeName = $attributeNode->name;
                    $attributeValue = $attributeNode->value;

                    if (strpos($allowedAttributes, ',' . $attributeName . ',') === false) {
                        $node->removeAttribute($attributeName);
                        continue;
                    }

                    if ($attributeName === 'src' || $attributeName === 'href') {
                        if (strpos($attributeValue, 'http://') !== 0 && strpos($attributeValue, 'https://') !== 0 && strpos($attributeValue, ':') !== false) {
                            $node->removeAttributeNode($attributeNode);
                            continue;
                        }
                    }

                    if ($this->_filter !== null) {
                        $r = call_user_func($this->_filter, $tag, $attributeName, $attributeValue);
                        if ($r === false) {
                            $node->removeAttributeNode($attributeNode);
                            continue;
                        } elseif (is_string($r)) {
                            $node->setAttribute($attributeName, $r);
                        }
                    }
                }
            }

            if ($node->hasChildNodes()) {
                $this->_purify(iterator_to_array($node->childNodes), $allowedTags, $allowedAttributes);
            }
        }
    }

    /**
     * @param string $html
     * @param array  $allowedTags
     * @param array  $allowedAttributes
     *
     * @return string
     */
    public function purify($html, $allowedTags = null, $allowedAttributes = null)
    {
        if (strpos($html, '<body>') === false) {
            $html
                = /** @lang text */
                "<!doctype html><html><body>$html</body></html>";
        }

        $doc = new DOMDocument();
        try {
            @$doc->loadHTML($html);
        } catch (\Exception $e) {
            return '';
        }

        $body = $doc->getElementsByTagName('body');
        if ($allowedTags !== null) {
            $tags = ',' . implode(',', $allowedTags) . ',';
        } else {
            $tags = $this->_allowedTags;
        }

        if ($allowedAttributes !== null) {
            $attributes = ',' . implode(',', $allowedAttributes) . ',';
        } else {
            $attributes = $this->_allowedAttributes;
        }

        $this->_purify(iterator_to_array($body->item(0)->childNodes), $tags, $attributes);
        $body = $doc->getElementsByTagName('body');
        return substr($doc->saveHTML($body->item(0)), 6, -7);
    }
}