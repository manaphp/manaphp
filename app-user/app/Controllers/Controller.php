<?php

namespace App\Controllers;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Mvc\View\FlashInterface;

class Controller extends \ManaPHP\Mvc\Controller
{
    #[Autowired] protected FlashInterface $flash;
}