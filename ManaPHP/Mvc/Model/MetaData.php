<?php

namespace ManaPHP\Mvc\Model {

    use ManaPHP\Component;
    use ManaPHP\Mvc\Model;

    /**
     * ManaPHP\Mvc\Model\MetaData
     *
     * <p>Because ManaPHP\Mvc\Model requires meta-data like field names, data types, primary keys, etc.
     * this component collect them and store for further querying by ManaPHP\Mvc\Model.
     * ManaPHP\Mvc\Model\MetaData can also use adapters to store temporarily or permanently the meta-data.</p>
     *
     * <p>A standard ManaPHP\Mvc\Model\MetaData can be used to query model attributes:</p>
     *
     * <code>
     *    $metaData = new ManaPHP\Mvc\Model\MetaData\Memory();
     *    $attributes = $metaData->getAttributes(new Robots());
     *    print_r($attributes);
     * </code>
     *
     */
    abstract class MetaData extends Component implements MetaDataInterface
    {
        const MODELS_ATTRIBUTES = 0;

        const MODELS_PRIMARY_KEY = 1;

        const MODELS_NON_PRIMARY_KEY = 2;

        const MODELS_IDENTITY_COLUMN = 8;

        protected $_metaData;

        /**
         * @param string|\ManaPHP\Mvc\ModelInterface $model
         *
         * @return array
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        protected function _fetchMetaDataFromRDBMS($model)
        {
            if (is_string($model)) {
                $modelName = $model;
                $model = new $model();
            } else {
                $modelName = get_class($model);
            }

            $readConnection = $model->getReadConnection();
            $escapedTable = $readConnection->escapeIdentifier($model->getSource());
            $columns = $readConnection->fetchAll('DESCRIBE ' . $escapedTable, null, \PDO::FETCH_NUM);
            if (count($columns) === 0) {
                throw new Exception("Cannot obtain table columns for the mapped source '" . $model->getSource() . "' used in model " . $modelName);
            }

            $attributes = [];
            $primaryKeys = [];
            $nonPrimaryKeys = [];
            $autoIncrementAttribute = null;
            foreach ($columns as $column) {
                $columnName = $column[0];

                $attributes[] = $columnName;

                if ($column[3] === 'PRI') {
                    $primaryKeys[] = $columnName;
                } else {
                    $nonPrimaryKeys = $columnName;
                }

                if ($column[5] === 'auto_increment') {
                    $autoIncrementAttribute = $columnName;
                }
            }

            return [
                self::MODELS_ATTRIBUTES => $attributes,
                self::MODELS_PRIMARY_KEY => $primaryKeys,
                self::MODELS_NON_PRIMARY_KEY => $nonPrimaryKeys,
                self::MODELS_IDENTITY_COLUMN => $autoIncrementAttribute,
            ];
        }

        /**
         * Reads the complete meta-data for certain model
         *
         *<code>
         *    print_r($metaData->readMetaData(new Robots()));
         *</code>
         *
         * @param string|\ManaPHP\Mvc\ModelInterface $model
         *
         * @return array
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        protected function _readMetaData($model)
        {
            $modelName = is_string($model) ? $model : get_class($model);
            if (!isset($this->_metaData[$modelName])) {
                $data = $this->read($modelName);
                if ($data !== null) {
                    $this->_metaData[$modelName] = $data;
                } else {
                    $data = $this->_fetchMetaDataFromRDBMS($model);
                    $this->_metaData[$modelName] = $data;
                    $this->write($modelName, $data);
                }
            }

            return $this->_metaData[$modelName];
        }

        /**
         * Returns table attributes names (fields)
         *
         *<code>
         *    print_r($metaData->getAttributes(new Robots()));
         *</code>
         *
         * @param string|\ManaPHP\Mvc\ModelInterface $model
         *
         * @return array
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        public function getAttributes($model)
        {
            return $this->_readMetaData($model)[self::MODELS_ATTRIBUTES];
        }

        /**
         * Returns an array of fields which are part of the primary key
         *
         *<code>
         *    print_r($metaData->getPrimaryKeyAttributes(new Robots()));
         *</code>
         *
         * @param string|\ManaPHP\Mvc\ModelInterface $model
         *
         * @return array
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        public function getPrimaryKeyAttributes($model)
        {
            return $this->_readMetaData($model)[self::MODELS_PRIMARY_KEY];
        }

        /**
         * Returns attribute which is auto increment or null
         *
         * @param string|\ManaPHP\Mvc\ModelInterface $model
         *
         * @return string |null
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        public function getAutoIncrementAttribute($model)
        {
            return $this->_readMetaData($model)[self::MODELS_IDENTITY_COLUMN];
        }

        /**
         * Returns an array of fields which are not part of the primary key
         *
         * @param string|\ManaPHP\Mvc\ModelInterface $model
         *
         * @return    array
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        public function getNonPrimaryKeyAttributes($model)
        {
            return $this->_readMetaData($model)[self::MODELS_NON_PRIMARY_KEY];
        }

        /**
         * Check if a model has certain attribute
         *
         *<code>
         *    var_dump($metaData->hasAttribute(new Robots(), 'name'));
         *</code>
         *
         * @param string|\ManaPHP\Mvc\ModelInterface $model
         * @param string                             $attribute
         *
         * @return boolean
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        public function hasAttribute($model, $attribute)
        {
            return isset($this->_readMetaData($model)[self::MODELS_ATTRIBUTES][$attribute]);
        }
    }
}
