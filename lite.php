<?php
date_default_timezone_set('PRC');

if ($argc === 1) {
    $rootPath = __DIR__ . '/ManaPHP';
} else {
    $rootPath = realpath($argv[1]);
}
$rootPath = str_replace('\\', '/', $rootPath);

$classes = array(
    0 => 'ManaPHP\\Mvc\\Application',
    1 => 'ManaPHP\\Component',
    2 => 'ManaPHP\\ComponentInterface',
    3 => 'ManaPHP\\Di\\FactoryDefault',
    4 => 'ManaPHP\\Di',
    5 => 'ManaPHP\\DiInterface',
    6 => 'ManaPHP\\Di\\Service',
    7 => 'ManaPHP\\Di\\ServiceInterface',
    8 => 'ManaPHP\\Loader',
    9 => 'ManaPHP\\Mvc\\Router',
    10 => 'ManaPHP\\Mvc\\RouterInterface',
    11 => 'ManaPHP\\Mvc\\Router\\Group',
    12 => 'ManaPHP\\Mvc\\Router\\GroupInterface',
    13 => 'ManaPHP\\Mvc\\Router\\Route',
    14 => 'ManaPHP\\Mvc\\Router\\RouteInterface',
    15 => 'ManaPHP\\Mvc\\ModuleInterface',
    16 => 'ManaPHP\\Mvc\\Dispatcher',
    17 => 'ManaPHP\\Mvc\\DispatcherInterface',
    18 => 'ManaPHP\\Mvc\\PhpUnitController',
    19 => 'ManaPHP\\Mvc\\Controller',
    20 => 'ManaPHP\\Mvc\\ControllerInterface',
    21 => 'ManaPHP\\Http\\Response',
    22 => 'ManaPHP\\Http\\ResponseInterface',
    23 => 'ManaPHP\\Http\\Response\\Headers',
    24 => 'ManaPHP\\Http\\Response\\HeadersInterface',
    25 => 'ManaPHP\\Mvc\\Dispatcher\\Listener',
    26 => 'ManaPHP\\Event\\ListenerInterface',
);

$classes = array_reverse($classes);
require $rootPath . '/Autoloader.php';
\ManaPHP\Autoloader::register();

$template_rootPath = 'D:\\wamp\\www\\manaphp\\ManaPHP';

$loadedClasses = [];

$class_parents = [];
foreach ($classes as $class) {
    if (strpos($class, 'Interface') !== false) {
        $loadedClasses[] = $class;
    } else {
        $classReflection = new ReflectionClass($class);
        $class_parents[$class] = [];

        while ($parent = $classReflection->getParentClass()) {
            $class_parents[$class][] = $parent->getName();
            $classReflection = $parent;
        }
    }
}

while (count($classes) !== count($loadedClasses)) {
    foreach ($class_parents as $class => $class_parent) {
        $parents_ok = true;
        foreach ($class_parent as $parent) {
            if (!in_array($parent, $loadedClasses, true)) {
                $parents_ok = false;
                break;
            }
        }
        if ($parents_ok) {
            $loadedClasses[] = $class;
            unset($class_parents[$class]);
        }
    }
}

$lite = '<?php' . PHP_EOL;
foreach ($loadedClasses as $class) {
    $sourceFile = $rootPath . str_replace('\\', '/', substr($class, 7)) . '.php';

    $content = file_get_contents($sourceFile);
    $content = str_replace('<?php', '', $content);
    $lite .= $content;
}
$liteFile = './manaLite' . date('ymd') . '.php';
file_put_contents($liteFile, $lite);
