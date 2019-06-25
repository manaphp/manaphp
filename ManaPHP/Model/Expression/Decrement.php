<?php
namespace ManaPHP\Model\Expression;

class Decrement extends Increment
{
    public function __construct($step = 1)
    {
        parent::__construct(-$step);
    }
}