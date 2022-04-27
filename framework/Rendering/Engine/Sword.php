<?php
declare(strict_types=1);

namespace ManaPHP\Rendering\Engine;

use ManaPHP\Component;
use ManaPHP\Rendering\EngineInterface;

/**
 * @property-read \ManaPHP\ConfigInterface                 $config
 * @property-read \ManaPHP\AliasInterface                  $alias
 * @property-read \ManaPHP\Rendering\Engine\Sword\Compiler $swordCompiler
 */
class Sword extends Component implements EngineInterface
{
    protected string $doc_root;

    protected array $compiled = [];

    public function __construct(?string $doc_root = null)
    {
        $this->doc_root = $doc_root ?? $_SERVER['DOCUMENT_ROOT'];
    }

    public function getCompiledFile(string $source): string
    {
        if (str_starts_with($source, $root = $this->alias->get('@root'))) {
            $compiled = '@runtime/sword' . substr($source, strlen($root));
        } elseif ($this->doc_root !== '' && str_starts_with($source, $this->doc_root)) {
            $compiled = '@runtime/sword/' . substr($source, strlen($this->doc_root));
        } else {
            $compiled = "@runtime/sword/$source";
            if (DIRECTORY_SEPARATOR === '\\') {
                $compiled = str_replace(':', '_', $compiled);
            }
        }

        $compiled = $this->alias->resolve($compiled);

        if ($this->config->get('debug') || !file_exists($compiled) || filemtime($source) > filemtime($compiled)) {
            $this->swordCompiler->compileFile($source, $compiled);
        }

        return $compiled;
    }

    public function render(string $file, array $vars = []): void
    {
        extract($vars, EXTR_SKIP);

        $this->compiled[$file] ??= $this->getCompiledFile($file);

        require $this->compiled[$file];
    }
}