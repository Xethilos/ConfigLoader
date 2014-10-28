<?php

$storage = new Caching\Storage(__DIR__ . '/temp');
$cache = new Caching\Cache($storage);
$writer = new GettextWriter(__DIR__ . '/translations/translations.php');

$writer->setExecution($writer::CHECK); // If file exist do nothing

$config = new ConfigLoader(__DIR__, '/configs');

// Cache
$config->setCache($cache);

// Export translations to php file for gettext
$config->setWriter($writer);

// Additional
$config->add('components', function ($values, $namespace) {
    foreach ($values as $name => $class) {
        if (!class_exists($class)) {
            throw new Exception("Component '$class' does not exist for '$namespace'.");
        }
    }
    return $values;
});

$config->execute($config::CHECK); // Process

var_dump($config->getParameters('Add')); // Get parameters from add.neon

var_dump($config->getParameters('AddNamespace')); // Get parameters from add.namespace.neon
var_dump($config->models); // Get models
var_dump($config->resources); // Get resources

$parameters = $config->getParameters('Add');

echo $parameters['limitPerPage'];

$parameters->change(array(
    'limitPerPage' => 50
)); // Change limtPerPage and save to file

// DUMPERD VALUES OF $config->data;
array (7)
    models => array (1)
        0 => "Model\Model" (11)
    routers => array ()
    resources => array (1)
        news => "News" (4)
    privileges => array (1)
        news => array (3)
            add => "Add" (3)
            edit => "Edit" (4)
            delete => "Delete" (6)
    menu => array (1)
        0 => array (2)
            parent => array (1) [ ... ]
            childrens => array (2) [ ... ]
    parameters => array (1)
        Add => Settings
            values protected => array (2) [ ... ]
            filePath protected => ".../config/add.neon" (92)
            namespace protected => "Add" (3)
            cache => Cache #6d6c { ... }
    components => array(1)
        0 => 'Component\Component'
// END
