<?php

namespace ManaPHP\Aop;

use ManaPHP\Cli\Console;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class Command extends \ManaPHP\Cli\Command
{
    public function selfAction()
    {
        $manaphp = $this->alias->get('@manaphp');
        $this->checkDirectory($manaphp);
    }

    protected function checkDirectory($dir)
    {
        foreach (glob("$dir/*") as $item) {
            if (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
                $this->checkFile($item);
            } elseif (is_dir($item)) {
                $this->checkDirectory($item);
            }
        }
    }

    protected function checkFile($file)
    {
        foreach (file($file) as $i => $line) {
            if (preg_match('#this->(\w+)\\(#', $line, $match) === 1) {
                $method = $match[1];
                if (in_array($method, ['fireEvent', 'attachEvent', 'getNew', 'setShared', 'getShared'], true)) {
                    continue;
                }

                if (str_contains($file, 'ManaPHP/Validating/Validator.php')) {
                    if ($method === 'createError') {
                        continue;
                    }
                }
                $toNext = false;
                foreach (
                    ['/ManaPHP/Streaming/Unpacker.php',
                     '/ManaPHP/Streaming/Packer.php',
                     '/ManaPHP/Streaming/Packer.php',
                     '/ManaPHP/Http/Router.php',
                     '/ManaPHP/Html/Renderer/Engine/Markdown.php',
                     '/ManaPHP/Cli/Console.php',
                     '/ManaPHP/Validating/Validator.php',
                     '/ManaPHP/Http/Server/Adapter/',
                     '/ManaPHP/Di/Container.php',
                     '/ManaPHP/Rpc/Server/',
                     '/ManaPHP/Html/Renderer/Engine/Sword/Compiler.php',
                     '/ManaPHP/Data/Table.php',
                     '/ManaPHP/Component.php',
                     '/ManaPHP/Configuration/Configure.php',
                     '/ManaPHP/Cli/Commands/',
                     '/ManaPHP/Cli/Handler.php',
                     '/ManaPHP/Data/Db/Model/Metadata.php',
                     '/ManaPHP/Cli/Runner.php',
                     '/ManaPHP/Data/Model/Linter.php',
                     '/ManaPHP/Debugging/DataDump.php',
                     '/ManaPHP/Debugging/DebuggerPlugin.php',
                     '/ManaPHP/Event/Listener.php',
                     '/ManaPHP/Mailing/Mailer/Message.php',
                     '/ManaPHP/Messaging/Amqp/Message.php',
                     '/ManaPHP/Loader.php',
                     '/ManaPHP/Http/Router/Route.php',
                     '/ManaPHP/Http/Client/Response.php',
                     '/ManaPHP/Http/Captcha.php',
                     '/ManaPHP/Data/Mongodb/Query.php',
                     '/ManaPHP/Data/Query.php',
                     //              '/ManaPHP/Data/Db/Query.php',
                     '/ManaPHP/Aop/',
                     '/Command.php',
                     '/Model.php',
                     '/Application.php',
                     '/Exception',
                     '/ManaPHP/Html/Dom/',
                     '/ManaPHP/Socket/Server/',
                     '/ManaPHP/Ws/Server/',
                    ] as $ignored
                ) {
                    if (str_contains($file, $ignored)) {
                        $toNext = true;
                        break;
                    }
                }

                if ($toNext) {
                    continue;
                }
                $line = str_replace($match[1], $this->console->colorize($match[1], Console::FC_RED), $line);
                $this->console->writeLn("$file:$i\t" . trim($line));
            }
        }
    }
}