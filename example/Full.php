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
