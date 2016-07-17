<?php
namespace ManaPHP\Authentication;

interface   UserIdentityInterface
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();
}