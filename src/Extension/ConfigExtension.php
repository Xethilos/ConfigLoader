<?php

class ConfigExtension extends Nette\DI\CompilerExtension {
    
    /** @var array */
    private $defaults = array(
        'writer' => array(
            'use' => FALSE,
            'execution' => GettextWriter::REWRITE
        ),
        'cache' => array(
            'use' => TRUE
        ),
        'loader' => array(
            'directory' => '/addons/configs',
            'execution' => ConfigLoader::CHECK,
            'addons' => array()
        )
    );
    
    public function loadConfiguration() {
        $config = $this->getConfig($this->defaults);
        
        $builder = $this->getContainerBuilder();
        
        // Cache storage
        $builder->addDefinition($this->prefix('storage'))
                    ->setClass('Caching\Storage', array($builder->expand('%tempDir%') . '/cache'))
                    ->setAutowired(FALSE);
        
        // Cache
        $builder->addDefinition($this->prefix('cache'))
                    ->setClass('Caching\Cache', array('@' . $this->prefix('storage'), 'ConfigLoader'));

        // Config Loader
        $loader = $builder->addDefinition($this->prefix('loader'))
                            ->setClass('ConfigLoader', array($builder->expand('%appDir%'), $config['loader']['directory']))
                            ->addSetup('setCache', array('@' . $this->prefix('cache')));
        
        if ($config['loader']['addons']) {
            foreach ($this->config['loader']['addons'] as $name => $callback) {
                if (!is_string($name)) {
                    throw new ConfigException(sprintf('First parameter of additional must be string, %s given.', gettype($callback)));
                }
                
                $loader->addSetup('add', array($name, $callback));
            } 
        }
        
        $loader->addSetup('execute', array($config['loader']['execution']));
        
        // Gettext Writer
        if ($config['writer']['use'] === TRUE) {
            $builder->addDefinition($this->prefix('gettext.writer'))
                        ->setClass('GettextWriter');
            
            $loader->addSetup('setWriter', array('GettextWriter'));
        }
    }
}
