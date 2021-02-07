<?php

namespace ManaPHP\Cli\Commands;

use FilesystemIterator;
use ManaPHP\Cli\Command;
use ManaPHP\Helper\LocalFS;
use Phar;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class PharCommand extends Command
{
    /**
     * create manacli.phar file
     *
     * @return void
     */
    public function manacliAction()
    {
        $this->alias->set('@phar', '@data/manacli_phar');
        $pharFile = $this->alias->resolve('@root/manacli.phar');

        $this->console->writeLn(['cleaning `:dir` dir', 'dir' => $this->alias->resolve('@phar')]);
        LocalFS::dirReCreate('@phar');

        $this->console->writeLn('copying manaphp framework files.');
        LocalFS::dirCopy('@root/ManaPHP', '@phar/ManaPHP');
        //LocalFS::dirCopy('@root/Application', '@phar/Application');
        LocalFS::fileCopy('@root/manacli.php', '@phar/manacli.php');

        $flags = FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME;
        $phar = new Phar($pharFile, $flags, basename($pharFile));
        $phar->buildFromDirectory($this->alias->resolve('@phar'));
        $phar->setStub($phar::createDefaultStub('manacli.php'));
        $this->console->writeLn('compressing files');
        $phar->compressFiles(Phar::BZ2);

        $this->console->writeLn(['`:phar` created successfully', 'phar' => $this->alias->resolve($pharFile)]);
    }
}