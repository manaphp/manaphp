<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use PHPUnit\Framework\TestCase;

class FilesystemAdapterFileTest extends TestCase
{
    /**
     * @var \ManaPHP\FilesystemInterface
     */
    protected $_filesystem;

    public function setUp()
    {
        $di = new FactoryDefault();
        $di->alias->set('@file', __DIR__ . '/FileSystem/File');

        $this->_filesystem = $di->filesystem;
        $this->_filesystem->dirDelete(__DIR__ . '/FileSystem/File');
    }

    public function test_fileExists()
    {
        $this->_filesystem->filePut('@file/fileExists', '');
        $this->assertTrue($this->_filesystem->fileExists('@file/fileExists'));
        $this->_filesystem->fileDelete('@file/fileExists');

        $this->_filesystem->fileDelete('@file/missing');
        $this->assertFalse($this->_filesystem->fileExists('@file/missing'));
    }

    public function test_fileDelete()
    {
        $this->assertFalse($this->_filesystem->fileExists('@file/missing'));
        $this->_filesystem->fileDelete('@file/missing');

        $this->_filesystem->filePut('@file/fileDelete', '');
        $this->assertTrue($this->_filesystem->fileExists('@file/fileDelete'));
        $this->_filesystem->fileDelete('@file/fileDelete');
    }

    public function test_fileGet()
    {
        $this->_filesystem->filePut('@file/fileGet', 'MANAPHP');
        $this->assertEquals('MANAPHP', $this->_filesystem->fileGet('@file/fileGet'));
        $this->_filesystem->fileDelete('@file/fileGet');

        $this->_filesystem->fileDelete('@file/missing');
        $this->assertFalse($this->_filesystem->fileGet('@file/missing'));
    }

    public function test_filePut()
    {
        $this->_filesystem->fileDelete('@file/filePut');
        $this->_filesystem->filePut('@file/filePut', 'MANAPHP');
        $this->assertEquals('MANAPHP', $this->_filesystem->fileGet('@file/filePut'));

        $this->_filesystem->filePut('@file/filePut', '1');
        $this->assertEquals('1', $this->_filesystem->fileGet('@file/filePut'));

        $this->_filesystem->fileDelete('@file/filePut');
    }

    public function test_fileAppend()
    {
        $this->_filesystem->fileDelete('@file/fileAppend');

        $this->_filesystem->fileAppend('@file/fileAppend', 'M');
        $this->assertEquals('M', $this->_filesystem->fileGet('@file/fileAppend'));

        $this->_filesystem->fileAppend('@file/fileAppend', 'A');
        $this->assertEquals('MA', $this->_filesystem->fileGet('@file/fileAppend'));

        $this->_filesystem->fileDelete('@file/fileAppend');
    }

    public function test_fileMove()
    {
        $this->_filesystem->filePut('@file/fileMoveOld', 'move');
        $this->_filesystem->fileDelete('@file/fileMoveNew');
        $this->_filesystem->fileMove('@file/fileMoveOld', '@file/fileMoveNew');
        $this->assertFalse($this->_filesystem->fileExists('@file/fileMoveOld'));
        $this->assertTrue($this->_filesystem->fileExists('@file/fileMoveNew'));
        $this->assertEquals('move', $this->_filesystem->fileGet('@file/fileMoveNew'));

        $this->_filesystem->fileDelete('@file/fileMoveNew');
    }

    public function test_fileCopy()
    {
        $this->_filesystem->filePut('@file/fileCopySrc', 'copy');
        $this->_filesystem->fileDelete('@file/fileCopyDst');
        $this->_filesystem->fileCopy('@file/fileCopySrc', '@file/fileCopyDst');
        $this->assertTrue($this->_filesystem->fileExists('@file/fileCopySrc'));
        $this->assertTrue($this->_filesystem->fileExists('@file/fileCopyDst'));
        $this->assertEquals('copy', $this->_filesystem->fileGet('@file/fileCopyDst'));

        $this->_filesystem->fileDelete('@file/fileCopySrc');
        $this->_filesystem->fileDelete('@file/fileCopyDst');
    }
}