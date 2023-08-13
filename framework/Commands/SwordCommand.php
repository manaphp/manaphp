<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\AliasInterface;
use ManaPHP\Cli\Command;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Rendering\Engine\Sword\Compiler;

class SwordCommand extends Command
{
    #[Inject] protected AliasInterface $alias;
    #[Inject] protected Compiler $swordCompiler;

    /**
     * precompile sword template
     *
     * @param bool $replace
     *
     * @return void
     */
    public function compileAction(bool $replace = false): void
    {
        LocalFS::dirDelete('@runtime/sword');
        $this->console->writeLn('delete `@runtime/sword` directory success');

        $ext = 'sword';

        foreach (LocalFS::glob("@app/Views/*.$ext") as $item) {
            $this->compile($item, $replace);
        }

        foreach (LocalFS::glob("@app/Views/*/*.$ext") as $item) {
            $this->compile($item, $replace);
        }

        foreach (LocalFS::glob("@app/Areas/*/Views/*/*.$ext") as $item) {
            $this->compile($item, $replace);
        }

        foreach (LocalFS::glob("@app/Areas/*/Views/*.$ext") as $item) {
            $this->compile($item, $replace);
        }
    }

    /**
     * @param string $file
     * @param bool   $replace
     *
     * @return void
     */
    protected function compile(string $file, bool $replace): void
    {
        if ($replace) {
            $compiled = str_replace('.sword', '.phtml', $file);
        } else {
            $compiled = str_replace($this->alias->get('@root'), $this->alias->resolve('@runtime/sword'), $file);
        }

        $this->swordCompiler->compileFile($file, $compiled);

        $this->console->writeLn("compiled `$compiled` file generated");
    }
}