<?php
declare(strict_types=1);

namespace ManaPHP\Html\Dom;

use ArrayAccess;
use ArrayIterator;
use Countable;
use DOMElement;
use DOMNode;
use DOMText;
use IteratorAggregate;
use Traversable;
use function array_slice;
use function count;
use function in_array;
use function is_string;

class SelectorList implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @var DOMElement[]
     */
    protected array $nodes;
    protected Document $document;

    public function __construct(Document|SelectorList $document, array $nodes)
    {
        $this->document = $document instanceof self ? $document->document : $document;
        $this->nodes = $nodes;
    }

    public function xpath(string $path): static
    {
        if ($path === '') {
            return clone $this;
        }
        $query = $this->document->getQuery();

        $nodes = [];
        foreach ($this->nodes as $node) {
            /** @var DOMNode $node2 */
            foreach ($query->xpath($path, $node) as $node2) {
                $nodes[$node2->getNodePath()] = $node2;
            }
        }

        return new SelectorList($this, array_values($nodes));
    }

    public function css(string $css): static
    {
        if ($css === '') {
            return clone $this;
        }

        $query = $this->document->getQuery();
        $nodes = [];
        foreach ($this->nodes as $node) {
            /** @var DOMNode $node2 */
            foreach ($query->css($css, $node) as $node2) {
                $nodes[$node2->getNodePath()] = $node2;
            }
        }

        return new SelectorList($this, array_values($nodes));
    }

    public function add(string|SelectorList $selectors): static
    {
        if (is_string($selectors)) {
            $selectors = (new Selector($this->document))->find($selectors);
        }

        if (!$selectors->nodes) {
            return clone $this;
        }

        if (!$this->nodes) {
            return clone $selectors;
        }

        return new SelectorList($this, $this->nodes + $selectors->nodes);
    }

    public function children(?string $css = null): static
    {
        return $this->css('child::' . ($css ?? '*'));
    }

    public function closest(?string $css = null): static
    {
        return $this->css('ancestor-or-self::' . ($css ?? '*'));
    }

    public function each(callable $func): array
    {
        $data = [];
        foreach ($this->nodes as $index => $selector) {
            $data[$index] = $func($selector, $index);
        }

        return $data;
    }

    public function eq(int $index): static
    {
        if ($index === 0) {
            return new SelectorList($this, $this->nodes ? [current($this->nodes)] : []);
        }

        if ($index < 0) {
            $index = count($this->nodes) + $index;
        }

        if ($index < 0 || $index >= count($this->nodes)) {
            return new SelectorList($this, []);
        } else {
            $keys = array_keys($this->nodes);
            return new SelectorList($this, [$this->nodes[$keys[$index]]]);
        }
    }

    public function filter(callable $func): static
    {
        $index = 0;
        $nodes = [];
        foreach ($this->nodes as $node) {
            $selector = new Selector($this->document, $node);
            if ($func($selector, $index++) !== false) {
                $nodes[] = $node;
            }
        }

        return new SelectorList($this, $nodes);
    }

    public function find(?string $css = null): static
    {
        return $this->css('descendant::' . ($css ?? '*'));
    }

    public function first(): ?Selector
    {
        return count($this->nodes) > 0 ? new Selector($this->document, current($this->nodes)) : null;
    }

    public function has(string $css): static
    {
        return $this->css('child::' . ($css ?? '*'));
    }

    public function is(string $css): bool
    {
        $r = $this->css('self::' . ($css ?? '*') . '[1]');
        return (bool)$r->nodes;
    }

    public function next(?string $css = null): static
    {
        return $this->css('following-sibling::' . ($css ?? '*') . '[1]');
    }

    public function nextAll(?string $css = null): static
    {
        return $this->css('following-sibling::' . ($css ?? '*'));
    }

    public function not(string $css): static
    {
        return $this->css('!self::' . ($css ?? '*'));
    }

    public function parent(?string $css = null): static
    {
        if ($css === '') {
            return clone $this;
        }

        return $this->css('parent::' . ($css ?? '*'));
    }

    public function parents(?string $css = null): static
    {
        return $this->css('ancestor::' . ($css ?? '*'));
    }

    public function prev(?string $css = null): static
    {
        return $this->css('preceding-sibling::' . ($css ?? '*') . '[1]');
    }

    public function prevAll(?string $css = null): static
    {
        return $this->css('preceding-sibling::' . ($css ?? '*'));
    }

    public function siblings(?string $css = null): static
    {
        $query = $this->document->getQuery();

        $nodes = [];
        foreach ($this->nodes as $node) {
            $cur_xpath = $node->getNodePath();
            foreach ($query->css('parent::' . ($css ?: '*'), $node) as $node2) {
                /** @var DOMNode $node2 */
                if ($node2->getNodePath() !== $cur_xpath) {
                    $nodes[$node2->getNodePath()] = $node2;
                }
            }
        }

        return new SelectorList($this, array_values($nodes));
    }

    public function slice(int $offset, ?int $length = null): static
    {
        $nodes = array_slice($this->nodes, $offset, $length);
        return new SelectorList($this, $nodes);
    }

    public function remove(string $css): static
    {
        /** @var DOMNode $node */
        $query = $this->document->getQuery();
        foreach ($this->nodes as $node) {
            foreach ($query->css($css, $node) as $node2) {
                $node2->parentNode->removeChild($node2);
            }
        }

        return $this;
    }

    public function removeAttr(string $css, null|string|array $attr = null): static
    {
        if ($attr) {
            $attr = (array)preg_split('#[\s,]+#', $attr, -1, PREG_SPLIT_NO_EMPTY);
        }

        /** @var DOMElement $node */
        $query = $this->document->getQuery();
        foreach ($this->nodes as $node_0) {
            foreach ($query->css($css, $node_0) as $node) {
                foreach ($node->attributes as $attribute) {
                    if (!$attr || in_array($attribute->name, $attr, true)) {
                        $node->removeAttribute($attribute->name);
                    }
                }
            }
        }

        return $this;
    }

    public function retainAttr(string $css, string|array $attr): static
    {
        if (is_string($attr)) {
            $attr = (array)preg_split('#[\s,]+#', $attr, -1, PREG_SPLIT_NO_EMPTY);
        }

        /** @var DOMElement $node */
        $query = $this->document->getQuery();
        foreach ($this->nodes as $node_0) {
            foreach ($query->css($css, $node_0) as $node) {
                foreach ($node->attributes as $attribute) {
                    if (!in_array($attribute->name, $attr, true)) {
                        $node->removeAttribute($attribute->name);
                    }
                }
            }
        }

        return $this;
    }

    public function strip(string $css): static
    {
        /** @var DOMNode $node */
        $query = $this->document->getQuery();
        foreach ($this->nodes as $node) {
            foreach ($query->css($css, $node) as $node2) {
                $node2->parentNode->replaceChild(new DOMText($node2->textContent), $node2);
            }
        }

        return $this;
    }

    public function name(): array
    {
        $data = [];

        foreach ($this->nodes as $node) {
            $data[] = $node->textContent;
        }

        return $data;
    }

    public function attr(string $attr): array
    {
        $data = [];

        foreach ($this->nodes as $node) {
            $data[] = $node->getAttribute($attr);
        }

        return $data;
    }

    public function attr_first(string $attr): ?string
    {
        return $this->nodes ? current($this->nodes)->getAttribute($attr) : null;
    }

    public function text(): array
    {
        $data = [];
        foreach ($this->nodes as $node) {
            $data[] = $node->textContent;
        }

        return $data;
    }

    public function text_first(): ?string
    {
        return $this->nodes ? current($this->nodes)->textContent : null;
    }

    public function url(string $attr): array
    {
        $data = [];

        foreach ($this->nodes as $node) {
            $data[] = $this->document->absolutizeUrl($node->getAttribute($attr));
        }

        return $data;
    }

    public function url_first(string $attr): ?string
    {
        return $this->nodes ? $this->document->absolutizeUrl(current($this->nodes)->getAttribute($attr)) : null;
    }

    public function html(): array
    {
        $data = [];
        foreach ($this->nodes as $node) {
            $data[] = $node->ownerDocument->saveHTML($node);
        }

        return $data;
    }

    public function html_first(): ?string
    {
        if ($this->nodes) {
            $node = current($this->nodes);
            return $node->ownerDocument->saveHTML($node);
        } else {
            return null;
        }
    }

    public function links(?string $regex = null): array
    {
        /** @var DOMElement $node */
        /** @var DOMElement $node2 */
        $query = $this->document->getQuery();
        $document = $this->document;

        $data = [];
        foreach ($this->nodes as $node) {
            if ($node->nodeName === 'a') {
                $href = $document->absolutizeUrl($node->getAttribute('href'));

                if ($regex && !preg_match($regex, $href)) {
                    continue;
                }

                $data[$node->getNodePath()] = ['href' => $href, 'text' => $node->textContent];
            } else {
                foreach ($query->xpath('descendant::a[@href]', $node) as $node2) {
                    $href = $document->absolutizeUrl($node2->getAttribute('href'));

                    if ($regex && !preg_match($regex, $href)) {
                        continue;
                    }

                    $data[$node2->getNodePath()] = ['href' => $href, 'text' => $node2->textContent];
                }
            }
        }

        return array_values($data);
    }

    public function images(?string $regex = null, string $attr = 'src'): array
    {
        /** @var DOMElement $node */
        /** @var DOMElement $node2 */
        $query = $this->document->getQuery();
        $document = $this->document;

        $data = [];
        foreach ($this->nodes as $node) {
            if ($node->nodeName === 'img') {
                $src = $document->absolutizeUrl($node->getAttribute($attr));

                if ($regex && !preg_match($regex, $src)) {
                    continue;
                }

                $data[$node->getNodePath()] = $src;
            } else {
                foreach ($query->xpath("descendant::img[@$attr]", $node) as $node2) {
                    $src = $document->absolutizeUrl($node2->getAttribute($attr));

                    if ($regex && !preg_match($regex, $src)) {
                        continue;
                    }

                    $data[$node2->getNodePath()] = $src;
                }
            }
        }

        return array_values($data);
    }

    public function extract(array $rules): array
    {
        $data = [];

        foreach ($this->nodes as $node) {
            $selector = new Selector($this->document, $node);
            $data[] = $selector->extract($rules);
        }

        return $data;
    }

    public function extract_first(array $rules): array
    {
        if ($this->nodes) {
            /** @noinspection OneTimeUseVariablesInspection */
            $selector = new Selector($this->document, current($this->nodes));
            return $selector->extract($rules);
        } else {
            return [];
        }
    }

    public function path(): array
    {
        $data = [];

        foreach ($this->nodes as $node) {
            $data[] = $node->getNodePath();
        }

        return $data;
    }

    public function node(): array
    {
        return $this->nodes;
    }

    public function getIterator(): ArrayIterator|Traversable
    {
        $selectors = [];
        foreach ($this->nodes as $node) {
            $selectors[] = new Selector($this->document, $node);
        }
        return new ArrayIterator($selectors);
    }

    public function count(): int
    {
        return count($this->nodes);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
    }

    public function offsetGet(mixed $offset): Selector
    {
        return new Selector($this->document, $this->nodes[$offset]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->nodes[$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
    }

    public function __toString()
    {
        return json_stringify($this->text());
    }
}