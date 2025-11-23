<?php

declare(strict_types=1);

namespace ManaPHP\Commands;

use FilesystemIterator;
use ManaPHP\Alias\Path;
use ManaPHP\AliasInterface;
use ManaPHP\Cli\Command;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\LocalFS;
use Phar;
use function basename;
use function sprintf;

class PharCommand extends Command
{
    #[Autowired] protected AliasInterface $alias;

    /**
     * create manacli.phar file
     */
    public function manacliAction(): void
    {
        $this->alias->set('@phar', '@runtime/manacli_phar');
        $pharFile = Path::resolve('@root/manacli.phar');

        $this->console->writeLn(sprintf('cleaning "%s" dir', Path::resolve('@phar')));
        LocalFS::dirReCreate('@phar');

        $this->console->writeLn('copying manaphp framework files.');
        LocalFS::dirCopy('@root/ManaPHP', '@phar/ManaPHP');
        //LocalFS::dirCopy('@root/Application', '@phar/Application');
        LocalFS::fileCopy('@root/manacli.php', '@phar/manacli.php');

        $flags = FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME;
        $phar = new Phar($pharFile, $flags, basename($pharFile));
        $phar->buildFromDirectory(Path::resolve('@phar'));
        $phar->setStub($phar::createDefaultStub('manacli.php'));
        $this->console->writeLn('compressing files');
        $phar->compressFiles(Phar::BZ2);

        $this->console->writeLn(sprintf('"%s" created successfully', Path::resolve($pharFile)));
    }
}
