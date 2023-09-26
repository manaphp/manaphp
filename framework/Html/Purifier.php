<?php
declare(strict_types=1);

namespace ManaPHP\Html;

use DOMDocument;
use DOMElement;
use DOMNode;
use Exception;
use ManaPHP\Di\Attribute\Value;

class Purifier implements PurifierInterface
{
    #[Value] protected string $allowedTags = ',a,b,br,code,div,i,img,s,strike,strong,samp,span,sub,sup,small,pre,p,q,div,em,h1,h2,h3,h4,h5,h6,table,u,ul,ol,tr,th,td,hr,li,';
    #[Value] protected string $allowedAttributes = ',title,src,href,width,height,alt,target,';
    /**
     * @var callable
     */
    #[Value] protected mixed $filter = null;

    protected function purifyInternal(array $nodes, string $allowedTags, string $allowedAttributes): void
    {
        $types = [XML_ELEMENT_NODE, XML_ATTRIBUTE_NODE, XML_DOCUMENT_NODE, XML_TEXT_NODE, XML_DOCUMENT_TYPE_NODE];

        /** @var DOMNode|DOMDocument|DOMElement $node */
        foreach ($nodes as $node) {
            if ($node->nodeType === XML_TEXT_NODE) {
                continue;
            }

            if (!in_array($node->nodeType, $types, true)) {
                $node->parentNode->removeChild($node);
                continue;
            }

            $tag = $node->nodeName;

            if (!str_contains($allowedTags, ',' . $tag . ',')) {
                $node->parentNode->removeChild($node);
                continue;
            }

            if ($node->hasAttributes()) {
                foreach (iterator_to_array($node->attributes) as $attributeNode) {
                    $attributeName = $attributeNode->name;
                    $attributeValue = $attributeNode->value;

                    if (!str_contains($allowedAttributes, ',' . $attributeName . ',')) {
                        $node->removeAttribute($attributeName);
                        continue;
                    }

                    if ($attributeName === 'src' || $attributeName === 'href') {
                        /** @noinspection HttpUrlsUsage */
                        if (!str_starts_with($attributeValue, 'http://')
                            && !str_starts_with($attributeValue, 'https://')
                            && str_contains($attributeValue, ':')
                        ) {
                            $node->removeAttributeNode($attributeNode);
                            continue;
                        }
                    }

                    if ($this->filter !== null) {
                        $filter = $this->filter;
                        $r = $filter($tag, $attributeName, $attributeValue);
                        if ($r === false) {
                            $node->removeAttributeNode($attributeNode);
                        } elseif (is_string($r)) {
                            $node->setAttribute($attributeName, $r);
                        }
                    }
                }
            }

            if ($node->hasChildNodes()) {
                $this->purifyInternal(iterator_to_array($node->childNodes), $allowedTags, $allowedAttributes);
            }
        }
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    public function purify(string $html, ?array $allowedTags = null, ?array $allowedAttributes = null): string
    {
        if (!str_contains($html, '<body>')) {
            $html
                = /** @lang text */
                "<!doctype html><html><body>$html</body></html>";
        }

        $doc = new DOMDocument();
        try {
            @$doc->loadHTML($html);
        } catch (Exception $e) {
            return '';
        }

        $body = $doc->getElementsByTagName('body');
        if ($allowedTags !== null) {
            $tags = ',' . implode(',', $allowedTags) . ',';
        } else {
            $tags = $this->allowedTags;
        }

        if ($allowedAttributes !== null) {
            $attributes = ',' . implode(',', $allowedAttributes) . ',';
        } else {
            $attributes = $this->allowedAttributes;
        }

        $this->purifyInternal(iterator_to_array($body->item(0)->childNodes), $tags, $attributes);
        $body = $doc->getElementsByTagName('body');
        return substr($doc->saveHTML($body->item(0)), 6, -7);
    }
}