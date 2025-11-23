<?php

declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Alias\Path;
use ManaPHP\Cli\Command;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Rendering\Engine\Sword\Compiler;
use function str_replace;

class SwordCommand extends Command
{
    #[Autowired] protected Compiler $swordCompiler;

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
        $this->console->writeLn('delete "@runtime/sword" directory success');

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

    protected function compile(string $file, bool $replace): void
    {
        if ($replace) {
            $compiled = str_replace('.sword', '.phtml', $file);
        } else {
            $compiled = str_replace(Path::resolve('@root'), Path::resolve('@runtime/sword'), $file);
        }

        $this->swordCompiler->compileFile($file, $compiled);

        $this->console->writeLn("compiled `$compiled` file generated");
    }
}
