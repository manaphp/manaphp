<?php

namespace ManaPHP\Mvc\Model {

    use ManaPHP\Component;

    /**
     * ManaPHP\Mvc\Model\Manager
     *
     * This components controls the initialization of models, keeping record of relations
     * between the different models of the application.
     *
     * A ModelsManager is injected to a model via a Dependency Injector/Services Container such as ManaPHP\Di.
     *
     * <code>
     * $di = new ManaPHP\Di();
     *
     * $di->set('modelsManager', function() {
     *      return new ManaPHP\Mvc\Model\Manager();
     * });
     *
     * $robot = new Robots($di);
     * </code>
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
         * @var \ManaPHP\Mvc\ModelInterface[]
         */
        protected $_initialized = [];

        /**
         * @var array
         */
        protected $_sources = [];

        /**
         * @var \ManaPHP\Mvc\Model\Query\BuilderInterface
         */
        protected $_builder;

        /**
         * Initializes a model in the model manager
         *
         * @param \ManaPHP\Mvc\ModelInterface $model
         *
         * @return boolean
         */
        public function initModel($model)
        {
            $modelName = get_class($model);

            /**
             * Models are just initialized once per request
             */
            if (!isset($this->_initialized[$modelName])) {
                $this->_initialized[$modelName] = $model;

                if (method_exists($model, 'initialize')) {
                    $model->initialize();
                }
            }
        }

        /**
         * Loads a model throwing an exception if it does't exist
         *
         * @param  string  $modelName
         * @param  boolean $newInstance
         *
         * @return \ManaPHP\Mvc\ModelInterface
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        public function getModelInstance($modelName, $newInstance)
        {
            if (isset($this->_initialized[$modelName])) {
                if ($newInstance) {
                    return new $modelName(null, $this->_dependencyInjector);
                }

                return $this->_initialized[$modelName];
            } else {
                if (class_exists($modelName)) {
                    return new $modelName(null, $this->_dependencyInjector);
                }

                throw new Exception("Model '" . $modelName . "' could not be loaded");
            }
        }

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
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        public function getModelSource($model)
        {
            $modelName = is_string($model) ? $model : get_class($model);

            if (isset($this->_sources[$modelName])) {
                return $this->_sources[$modelName];
            } else {
                throw new Exception('The source is not provided: ' . $modelName);
            }
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
         * @param string $params
         *
         * @return \ManaPHP\Mvc\Model\Query\BuilderInterface
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Db\ConditionParser\Exception
         */
        public function createBuilder($params = null)
        {
            $this->_builder = $this->_dependencyInjector->get('ManaPHP\Mvc\Model\Query\Builder',
                [$params, $this->_dependencyInjector]);

            return $this->_builder;
        }

        /**
         * Returns the latest query created or executed in the models manager
         *
         * @return \ManaPHP\Mvc\Model\QueryInterface
         */
        public function getLastQuery()
        {
            return $this->_builder->getSql();
        }
    }
}
