<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class FilesystemAdapterFileTest extends TestCase
{
    /**
     * @var \ManaPHP\FilesystemInterface
     */
    protected $filesystem;

    public function setUp()
    {
        $di = new FactoryDefault();
        $di->alias->set('@file', __DIR__ . '/FileSystem/File');

        $this->filesystem = $di->filesystem;
        $this->filesystem->dirDelete(__DIR__ . '/FileSystem/File');
    }

    public function test_fileExists()
    {
        $this->filesystem->filePut('@file/fileExists', '');
        $this->assertTrue($this->filesystem->fileExists('@file/fileExists'));
        $this->filesystem->fileDelete('@file/fileExists');

        $this->filesystem->fileDelete('@file/missing');
        $this->assertFalse($this->filesystem->fileExists('@file/missing'));
    }

    public function test_fileDelete()
    {
        $this->assertFalse($this->filesystem->fileExists('@file/missing'));
        $this->filesystem->fileDelete('@file/missing');

        $this->filesystem->filePut('@file/fileDelete', '');
        $this->assertTrue($this->filesystem->fileExists('@file/fileDelete'));
        $this->filesystem->fileDelete('@file/fileDelete');
    }

    public function test_fileGet()
    {
        $this->filesystem->filePut('@file/fileGet', 'MANAPHP');
        $this->assertEquals('MANAPHP', $this->filesystem->fileGet('@file/fileGet'));
        $this->filesystem->fileDelete('@file/fileGet');

        $this->filesystem->fileDelete('@file/missing');
        $this->assertFalse($this->filesystem->fileGet('@file/missing'));
    }

    public function test_filePut()
    {
        $this->filesystem->fileDelete('@file/filePut');
        $this->filesystem->filePut('@file/filePut', 'MANAPHP');
        $this->assertEquals('MANAPHP', $this->filesystem->fileGet('@file/filePut'));

        $this->filesystem->filePut('@file/filePut', '1');
        $this->assertEquals('1', $this->filesystem->fileGet('@file/filePut'));

        $this->filesystem->fileDelete('@file/filePut');
    }

    public function test_fileAppend()
    {
        $this->filesystem->fileDelete('@file/fileAppend');

        $this->filesystem->fileAppend('@file/fileAppend', 'M');
        $this->assertEquals('M', $this->filesystem->fileGet('@file/fileAppend'));

        $this->filesystem->fileAppend('@file/fileAppend', 'A');
        $this->assertEquals('MA', $this->filesystem->fileGet('@file/fileAppend'));

        $this->filesystem->fileDelete('@file/fileAppend');
    }

    public function test_fileMove()
    {
        $this->filesystem->filePut('@file/fileMoveOld', 'move');
        $this->filesystem->fileDelete('@file/fileMoveNew');
        $this->filesystem->fileMove('@file/fileMoveOld', '@file/fileMoveNew');
        $this->assertFalse($this->filesystem->fileExists('@file/fileMoveOld'));
        $this->assertTrue($this->filesystem->fileExists('@file/fileMoveNew'));
        $this->assertEquals('move', $this->filesystem->fileGet('@file/fileMoveNew'));

        $this->filesystem->fileDelete('@file/fileMoveNew');
    }

    public function test_fileCopy()
    {
        $this->filesystem->filePut('@file/fileCopySrc', 'copy');
        $this->filesystem->fileDelete('@file/fileCopyDst');
        $this->filesystem->fileCopy('@file/fileCopySrc', '@file/fileCopyDst');
        $this->assertTrue($this->filesystem->fileExists('@file/fileCopySrc'));
        $this->assertTrue($this->filesystem->fileExists('@file/fileCopyDst'));
        $this->assertEquals('copy', $this->filesystem->fileGet('@file/fileCopyDst'));

        $this->filesystem->fileDelete('@file/fileCopySrc');
        $this->filesystem->fileDelete('@file/fileCopyDst');
    }
}