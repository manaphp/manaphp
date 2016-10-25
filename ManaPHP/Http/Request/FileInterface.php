<?php

namespace ManaPHP\Http\Request;

/**
 * Interface ManaPHP\Http\Request\FileInterface
 *
 * @package request
 */
interface FileInterface
{

    /**
     * Returns the file key
     *
     * @return string
     */
    public function getKey();

    /**
     * Returns the file size of the uploaded file
     *
     * @return int
     */
    public function getSize();

    /**
     * Returns the real name of the uploaded file
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the temporal name of the uploaded file
     *
     * @return string
     */
    public function getTempName();

    /**
     * Returns the mime type reported by the browser
     * This mime type is not completely secure, use getRealType() instead
     *
     * @return string
     */
    public function getType();

    /**
     * Move the temporary file to a destination
     *
     * @param string       $destination
     * @param string|false $allowedExtensions
     */
    public function moveTo($destination, $allowedExtensions = 'jpg,jpeg,png,gif,doc,xls,pdf,zip');

    /**
     * Returns the file extension
     *
     * @return string
     */
    public function getExtension();
}