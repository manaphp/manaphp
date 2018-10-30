<?php
namespace ManaPHP\View;

class Listener extends \ManaPHP\Event\Listener
{
    /**
     * @param \ManaPHP\ViewInterface $view
     *
     * @return void
     */
    public function onBeforeRender($view)
    {

    }

    /**
     * @param \ManaPHP\ViewInterface $view
     *
     * @return void
     */
    public function onAfterRender($view)
    {

    }

    /**
     * @param \ManaPHP\ViewInterface $view
     * @param array                  $data
     *
     * @return void
     */
    public function onMissCache($view, $data)
    {

    }

    /**
     * @param \ManaPHP\ViewInterface $view
     * @param array                  $data
     *
     * @return void
     */
    public function onHitCache($view, $data)
    {

    }
}
