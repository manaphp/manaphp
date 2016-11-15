<?php

namespace ManaPHP\Http\Request;

use ManaPHP\Component;
use ManaPHP\Http\Request\File\Exception as FileException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Http\Request\File
 *
 * @package request
 */
class File extends Component implements FileInterface
{
    /**
     * @var string
     */
    protected $_key;

    /**
     * @var array
     */
    protected $_file;

    /**
     * @var string
     */
    protected static $_alwaysRejectedExtensions = 'php,pl,py,cgi,asp,jsp,sh,cgi';

    /**
     * \ManaPHP\Http\Request\File constructor
     *
     * @param string $key
     * @param array  $file
     */
    public function __construct($key, $file)
    {
        $this->_key = $key;
        $this->_file = $file;
    }

    /**
     * Returns the file size of the uploaded file
     *
     * @return int
     */
    public function getSize()
    {
        return $this->_file['size'];
    }

    /**
     * Returns the real name of the uploaded file
     *
     * @return string
     */
    public function getName()
    {
        return $this->_file['name'];
    }

    /**
     * Returns the temporary name of the uploaded file
     *
     * @return string
     */
    public function getTempName()
    {
        return $this->_file['tmp_name'];
    }

    /**
     * Returns the mime type reported by the browser
     * This mime type is not completely secure, use getRealType() instead
     *
     * @return string
     */
    public function getType()
    {
        return $this->_file['type'];
    }

    /**
     * Returns the error code
     *
     * @return string
     */
    public function getError()
    {
        return $this->_file['error'];
    }

    /**
     * Returns the file key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * Checks whether the file has been uploaded via Post.
     *
     * @return bool
     */
    public function isUploadedFile()
    {
        return is_uploaded_file($this->_file['tmp_name']);
    }

    /**
     * Moves the temporary file to a destination within the application
     *
     * @param string       $dst
     * @param string|false $allowedExtensions
     *
     * @throws \ManaPHP\Filesystem\Adapter\Exception
     * @throws \ManaPHP\Http\Request\File\Exception
     */
    public function moveTo($dst, $allowedExtensions = 'jpg,jpeg,png,gif,doc,xls,pdf,zip')
    {
        $extension = pathinfo($dst, PATHINFO_EXTENSION);
        if ($extension) {
            $extension = ',' . $extension . ',';

            if (is_string($allowedExtensions)) {
                $allowedExtensions = ',' . str_replace(' ', '', $allowedExtensions) . ',';
                $allowedExtensions = str_replace(',.', ',', $allowedExtensions);

                if (!Text::contains($allowedExtensions, $extension, true)) {
                    throw new FileException('`:extension` file type is not allowed upload'/**m0fc09a879406a3940*/, ['extension' => $extension]);
                }
            }

            if (is_string(self::$_alwaysRejectedExtensions)) {
                $alwaysRejectedExtensions = ',' . str_replace(' ', '', self::$_alwaysRejectedExtensions) . ',';
                $alwaysRejectedExtensions = str_replace(',.', ',', $alwaysRejectedExtensions);
                if (Text::contains($alwaysRejectedExtensions, $extension, true)) {
                    throw new FileException('`:extension` file types is not allowed upload always'/**m0331d91c39adb3af6*/, ['extensions' => self::$_alwaysRejectedExtensions]);
                }
            }
        }

        if ($this->_file['error'] !== UPLOAD_ERR_OK) {
            throw new FileException('error code of upload file is not UPLOAD_ERR_OK: :error'/**m0454e71638e03eee6*/, ['error' => $this->_file['error']]);
        }

        if ($this->filesystem->fileExists($dst)) {
            throw new FileException('`:file` file already exists'/**m0402f85613fe0f167*/, ['file' => $dst]);
        }

        $this->filesystem->dirCreate(dirname($dst));

        if (!move_uploaded_file($this->_file['tmp_name'], $this->alias->resolve($dst))) {
            throw new FileException('move_uploaded_file to `:dst` failed: :last_error_message'/**m01d834f396d846d2b*/, ['dst' => $dst]);
        }

        if (!chmod($this->alias->resolve($dst), 0644)) {
            throw new FileException('chmod `:dst` destination failed: :last_error_message'/**m0a0e7dc6898fb4abe*/, ['dst' => $dst]);
        }
    }

    /**
     * Returns the file extension
     *
     * @return string
     */
    public function getExtension()
    {
        return pathinfo($this->_file['name'], PATHINFO_EXTENSION);
    }
}