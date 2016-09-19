<?php
namespace Application;

use ManaPHP\Db\Adapter\Mysql;
use ManaPHP\DbInterface;

class Cli extends \ManaPHP\Cli\Application
{
    public function registerServices()
    {
        $self = $this;
        $this->_dependencyInjector->setShared('configure', function () {
            return new Configure();
        });

        $this->_dependencyInjector->set('db', function () use ($self) {
            $mysql = new Mysql((array)$self->configure->database);
            $mysql->attachEvent('db:beforeQuery', function (DbInterface $source, $data) use ($self) {
                $self->logger->debug('SQL: ', $source->getSQL());
            });
            return $mysql;
        });

        $this->_dependencyInjector->setShared('redis', function () {
            $redis = new \Redis();
            $redis->connect('localhost');
            return $redis;
        });
    }

    public function main()
    {
        date_default_timezone_set('PRC');

        $this->loader->registerNamespaces([basename($this->alias->get('@app')) => $this->alias->get('@app')]);
        $this->registerServices();

        $this->debugger->start();

        exit($this->handle());
    }
}