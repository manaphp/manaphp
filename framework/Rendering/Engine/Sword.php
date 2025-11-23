<?php

declare(strict_types=1);

namespace ManaPHP\Rendering\Engine;

use ManaPHP\Alias\Path;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Rendering\Engine\Sword\Compiler;
use ManaPHP\Rendering\EngineInterface;
use function extract;
use function file_exists;
use function filemtime;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

class Sword implements EngineInterface
{
    #[Autowired] protected Compiler $swordCompiler;

    #[Config] protected bool $app_debug;

    protected string $doc_root;

    protected array $compiled = [];

    public function __construct(?string $doc_root = null)
    {
        $this->doc_root = $doc_root ?? $_SERVER['DOCUMENT_ROOT'];
    }

    public function getCompiledFile(string $source): string
    {
        if (str_starts_with($source, $root = Path::resolve('@root'))) {
            $compiled = '@runtime/sword' . substr($source, strlen($root));
        } elseif ($this->doc_root !== '' && str_starts_with($source, $this->doc_root)) {
            $compiled = '@runtime/sword/' . substr($source, strlen($this->doc_root));
        } else {
            $compiled = "@runtime/sword/$source";
            if (DIRECTORY_SEPARATOR === '\\') {
                $compiled = str_replace(':', '_', $compiled);
            }
        }

        $compiled = Path::resolve($compiled);

        if ($this->app_debug || !file_exists($compiled) || filemtime($source) > filemtime($compiled)) {
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
