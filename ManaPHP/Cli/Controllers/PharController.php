<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

class PharController extends Controller
{
    /**
     * @CliCommand create manacli.phar file
     */
    public function manacliCommand()
    {
        $this->alias->set('@phar', '@data/manacli_phar');
        $pharFile = $this->alias->resolve('@root/manacli.phar');

        $this->console->writeLn('cleaning `:dir` dir', ['dir' => $this->alias->resolve('@phar')]);
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $this->filesystem->dirReCreate('@phar');

        $this->console->writeLn('copying manaphp framework files.');
        $this->filesystem->dirCopy('@root/ManaPHP', '@phar/ManaPHP');
        //$di->filesystem->dirCopy('@root/Application', '@phar/Application');
        $this->filesystem->fileCopy('@root/manacli.php', '@phar/manacli.php');

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $phar = new \Phar($pharFile, \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::KEY_AS_FILENAME, basename($pharFile));
        $phar->buildFromDirectory($this->alias->resolve('@phar'));
        $phar->setStub($phar::createDefaultStub('manacli.php'));
        $this->console->writeLn('compressing files');
        $phar->compressFiles(\Phar::BZ2);

        $this->console->writeLn('`:phar` created successfully', ['phar' => $this->alias->resolve($pharFile)]);
    }
}