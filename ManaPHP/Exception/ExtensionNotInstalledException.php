<?php
namespace ManaPHP\Exception;

class ExtensionNotInstalledException extends RuntimeException
{
    public function __construct($extension = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct("`$extension` is not installed, or the extension is not loaded");
    }
}