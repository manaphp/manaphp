<?php

namespace ManaPHP\Http\Request {

    use ManaPHP\Http\Request\File\Exception as FileException;

    /**
     * ManaPHP\Http\Request\File
     *
     * Provides OO wrappers to the $_FILES
     *
     *<code>
     *    class PostsController extends \ManaPHP\Mvc\Controller
     *    {
     *
     *        public function uploadAction()
     *        {
     *            //Check if the user has uploaded files
     *            if ($this->request->hasFiles() == true) {
     *                //Print the real file names and their sizes
     *                foreach ($this->request->getUploadedFiles() as $file){
     *                    echo $file->getName(), " ", $file->getSize(), "\n";
     *                }
     *            }
     *        }
     *
     *    }
     *</code>
     */
    class File implements FileInterface
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
         * Gets the real mime type of the upload file
         *
         * @return string
         */
        public function getRealType()
        {
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            if (!is_resource($fileInfo)) {
                return '';
            }

            $mime = finfo_file($fileInfo, $this->_file['tmp_name']);
            finfo_close($fileInfo);

            return $mime;
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
         * @return boolean
         */
        public function isUploadedFile()
        {
            return is_uploaded_file($this->_file['tmp_name']);
        }

        /**
         * Moves the temporary file to a destination within the application
         *
         * @param string $destination
         *
         * @throws \ManaPHP\Http\Request\File\Exception
         */
        public function moveTo($destination)
        {

            if ($this->_file['error'] !== UPLOAD_ERR_OK) {
                throw new FileException('Error code of upload file is not UPLOAD_ERR_OK: ' . $this->_file['error']);
            }

            if (is_file($destination)) {
                throw new FileException('File already exists: \'' . $destination . '\'');
            }

            $dir = dirname($destination);
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new FileException('Unable to create \'' . $dir . '\' directory: ' . error_get_last()['message']);
            }

            $r = move_uploaded_file($this->_file['tmp_name'], $destination);
            if ($r !== true) {
                throw new FileException(error_get_last()['message']);
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
}
