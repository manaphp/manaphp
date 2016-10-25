<?php

namespace ManaPHP\Mvc\Model;

use ManaPHP\Component;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Mvc\Model\Manager
 *
 * @package modelsManager
 */
class Manager extends Component implements ManagerInterface
{
    /**
     * @var array
     */
    protected $_readConnectionServices = [];

    /**
     * @var array
     */
    protected $_writeConnectionServices = [];

    /**
     * @var array
     */
    protected $_sources = [];

    /**
     * @var \ManaPHP\Mvc\Model\QueryBuilderInterface
     */
    protected $_builder;

    /**
     * @var bool
     */
    protected $_recallGetModelSource = false;

    /**
     * Sets the mapped source for a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     * @param string                             $source
     *
     * @return static
     */
    public function setModelSource($model, $source)
    {
        $modelName = is_string($model) ? $model : get_class($model);

        $this->_sources[$modelName] = $source;

        return $this;
    }

    /**
     * Returns the mapped source for a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     *
     * @return string
     */
    public function getModelSource($model)
    {
        $modelName = is_string($model) ? $model : get_class($model);

        if (!isset($this->_sources[$modelName])) {
            if ($this->_recallGetModelSource) {
                return Text::underscore(Text::contains($modelName, '\\') ? substr($modelName, strrpos($modelName, '\\') + 1) : $modelName);
            }

            $modelInstance = is_string($model) ? new $model : $model;
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!isset($this->_sources[$modelName])) {
                $this->_recallGetModelSource = true;
                $this->_sources[$modelName] = $modelInstance->getSource();
                $this->_recallGetModelSource = false;
            }
        }

        return $this->_sources[$modelName];
    }

    /**
     * Sets both write and read connection service for a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     * @param string                             $connectionService
     *
     * @return static
     */
    public function setConnectionService($model, $connectionService)
    {
        $modelName = is_string($model) ? $model : get_class($model);

        $this->_readConnectionServices[$modelName] = $connectionService;
        $this->_writeConnectionServices[$modelName] = $connectionService;

        return $this;
    }

    /**
     * Sets write connection service for a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     * @param string                             $connectionService
     *
     * @return static
     */
    public function setWriteConnectionService($model, $connectionService)
    {
        $modelName = is_string($model) ? $model : get_class($model);

        $this->_writeConnectionServices[$modelName] = $connectionService;

        return $this;
    }

    /**
     * Sets read connection service for a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     * @param string                             $connectionService
     *
     * @return static
     */
    public function setReadConnectionService($model, $connectionService)
    {
        $modelName = is_string($model) ? $model : get_class($model);

        $this->_readConnectionServices[$modelName] = $connectionService;

        return $this;
    }

    /**
     * Returns the connection to write data related to a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     *
     * @return \ManaPHP\DbInterface
     */
    public function getWriteConnection($model)
    {
        $serviceName = $this->getWriteConnectionService($model);

        return $this->_dependencyInjector->getShared($serviceName);
    }

    /**
     * Returns the connection to read data related to a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     *
     * @return \ManaPHP\DbInterface
     */
    public function getReadConnection($model)
    {
        $serviceName = $this->getReadConnectionService($model);

        return $this->_dependencyInjector->getShared($serviceName);
    }

    /**
     * Returns the connection service name used to read data related to a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     *
     * @return string
     */
    public function getReadConnectionService($model)
    {
        $modelName = is_string($model) ? $model : get_class($model);

        return isset($this->_readConnectionServices[$modelName]) ? $this->_readConnectionServices[$modelName] : 'db';
    }

    /**
     * Returns the connection service name used to write data related to a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     *
     * @return string
     */
    public function getWriteConnectionService($model)
    {
        $modelName = is_string($model) ? $model : get_class($model);

        return isset($this->_writeConnectionServices[$modelName]) ? $this->_writeConnectionServices[$modelName] : 'db';
    }

    /**
     * Creates a \ManaPHP\Mvc\Model\Query\Builder
     *
     * @param string|array $params
     *
     * @return \ManaPHP\Mvc\Model\QueryBuilderInterface
     */
    public function createBuilder($params = null)
    {
        $getParameter = [$params];
        $this->_builder = $this->_dependencyInjector->get('queryBuilder', $getParameter);

        return $this->_builder;
    }

    /**
     * Returns the latest query created or executed in the models manager
     *
     * @return string
     */
    public function getLastQuery()
    {
        return $this->_builder->getSql();
    }
}