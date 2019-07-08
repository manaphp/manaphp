<?php
namespace ManaPHP\Db;

interface AssignmentInterface
{
    /**
     * @param string $name
     *
     * @return static
     */
    public function setField($name);

    /**
     *
     * @return string
     */
    public function getSql();

    /**
     * @return array
     */
    public function getBind();
}