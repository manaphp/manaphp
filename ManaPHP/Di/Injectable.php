<?php

namespace ManaPHP\Di;

interface Injectable
{
    /**
     * @param \ManaPHP\Di\InjectorInterface $injector
     *
     * @return void
     */
    public function setInjector($injector);
}