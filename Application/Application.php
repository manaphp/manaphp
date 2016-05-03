<?php
namespace Application {

    use ManaPHP\Db\Adapter\Mysql;
    use ManaPHP\DbInterface;
    use ManaPHP\Log\Adapter\File;
    use ManaPHP\Log\Logger;
    use ManaPHP\Mvc\Router;
    use ManaPHP\Mvc\Router\Group;
    use ManaPHP\Security\Crypt;

    class Application extends \ManaPHP\Mvc\Application
    {
        public $debuggerFile;

        protected function registerServices()
        {
            $self = $this;

            $this->_dependencyInjector->setShared('configure', new Configure());

            $this->_dependencyInjector->setShared('router', function () {
                return (new Router())->mount(new Group(), 'Home', '/');
            });

            $this->_dependencyInjector->setShared('logger', function () use ($self) {
                return (new Logger())->addAdapter(new File($self->configure->log->file));
            });

            $this->_dependencyInjector->setShared('crypt', function () use ($self) {
                return new Crypt($self->configure->crypt->key);
            });

            $this->_dependencyInjector->set('db', function () use ($self) {
                $mysql = new Mysql((array)$this->configure->database);
                $mysql->attachEvent('db:beforeQuery', function ($event, DbInterface $source, $data) use ($self) {
                    $self->logger->debug('SQL: ' . $source->getSQL());
                });

                return $mysql;
            });

            $this->_dependencyInjector->setShared('authorization', new Authorization());
        }

        public function main()
        {
            date_default_timezone_set('PRC');

            $this->debugger->listenException();

            $this->registerServices();

            $this->debuggerFile = $this->configure->resolvePath('@data/Debugger/' . date('Ymd') . '/' . md5('!@#31' . mt_rand() . microtime(true)) . '.html');
            if (isset($_GET['_debugger_file'])) {
                $file = base64_decode($_GET['_debugger_file']);
                if (is_file($file)) {
                    exit(file_get_contents($file));
                } else {
                    if (strpos($file, $this->configure->resolvePath('@data/Debugs')) === false) {
                        throw new Exception('Are you a hacker? please stop!');
                    } else {
                        throw new Exception('Debugger File is not exists: ' . $file);
                    }
                }
            }

            // $this->logger->debug('start');

            //   $this->useImplicitView(false);

            $this->registerModules(['Home']);

            return $this->handle()->getContent();
        }

    }
}