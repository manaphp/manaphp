<?php

namespace ManaPHP\Di;

interface Injectable
{
    /**
     * @param \ManaPHP\Di\InjectorInterface $injector
     * @param mixed                         $self
     *
     * @return void
     */
    public function setInjector($injector, $self = null);
}