<?php

namespace ManaPHP\Http\Request;

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
     * @param bool $real
     *
     * @return string
     */
    public function getType($real = true);

    /**
     * Move the temporary file to a destination
     *
     * @param string $dst
     * @param string $allowedExtensions
     * @param bool   $overwrite
     */
    public function moveTo($dst, $allowedExtensions = 'jpg,jpeg,png,gif,doc,xls,pdf,zip', $overwrite = false);

    /**
     * Returns the file extension
     *
     * @return string
     */
    public function getExtension();

    /**
     * @return void
     */
    public function delete();
}