<?php
namespace ManaPHP\Model;

class Listener extends \ManaPHP\Event\Listener
{
    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return void|false
     */
    public function onBeforeSave($model)
    {

    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return void
     */
    public function onAfterSave($model)
    {

    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return void|false
     */
    public function onBeforeCreate($model)
    {

    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return void
     */
    public function onAfterCreate($model)
    {

    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return void|false
     */
    public function onBeforeUpdate($model)
    {

    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return void
     */
    public function onAfterUpdate($model)
    {

    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return void|false
     */
    public function onBeforeDelete($model)
    {

    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return void
     */
    public function onAfterDelete($model)
    {

    }
}