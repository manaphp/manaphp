<?php

namespace ManaPHP\Mvc\Model;

/**
 * Interface ManaPHP\Mvc\Model\ManagerInterface
 *
 * @package ManaPHP\Mvc\Model
 */
interface ManagerInterface
{
    /**
     * Sets the mapped source for a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     * @param string                             $source
     *
     * @return static
     */
    public function setModelSource($model, $source);

    /**
     * Returns the mapped source for a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     *
     * @return string
     */
    public function getModelSource($model);

    /**
     * Sets both write and read connection service for a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     * @param string                             $connectionService
     */
    public function setConnectionService($model, $connectionService);

    /**
     * Sets write connection service for a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     * @param string                             $connectionService
     */
    public function setWriteConnectionService($model, $connectionService);

    /**
     * Sets read connection service for a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     * @param string                             $connectionService
     */
    public function setReadConnectionService($model, $connectionService);

    /**
     * Returns the connection to write data related to a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     *
     * @return \ManaPHP\DbInterface
     */
    public function getWriteConnection($model);

    /**
     * Returns the connection to read data related to a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     *
     * @return \ManaPHP\DbInterface
     */
    public function getReadConnection($model);

    /**
     * Returns the connection service name used to read data related to a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     *
     * @return string
     */
    public function getReadConnectionService($model);

    /**
     * Returns the connection service name used to write data related to a model
     *
     * @param \ManaPHP\Mvc\ModelInterface|string $model
     *
     * @return string
     */
    public function getWriteConnectionService($model);

    /**
     * Creates a \ManaPHP\Mvc\Model\Query\Builder
     *
     * @param string|array $params
     *
     * @return \ManaPHP\Mvc\Model\QueryBuilderInterface
     */
    public function createBuilder($params = null);

    /**
     * Returns the last query created or executed in the
     *
     * @return string
     */
    public function getLastQuery();
}