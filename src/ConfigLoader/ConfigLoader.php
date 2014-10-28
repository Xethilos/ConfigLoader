<?php

/**
 * @author Xethilos
 * 
 * @property-read array $models
 * @property-read array $routers
 * @property-read array $privileges
 * @property-read array $resources
 * @property-read array $menu
 * @property-read array $parameters
 */
class ConfigLoader extends Nette\Object {
    
    const DISABLED = 0;
    const CHECK = 1;
    
    protected $data = array(
        'models' => array(),
        'routers' => array(),
        'resources' => array(),
        'privileges' => array(),
        'menu' => array(),
        'parameters' => array()
    );
    
    /** @var GettextWriter */
    protected $writer = NULL;
    
    /** @var Nette\Caching\Cache */
    protected $cache = NULL;
    
    /** @var string */
    protected $namespace = NULL;
    
    /** @var string */
    protected $appDir;
    
    /** @var array */
    protected $additional = array();
    
    /**
     * @param string $appDir
     * @param string $namespace
     */
    public function __construct($appDir, $namespace = NULL) {
        $this->appDir = $appDir;
        $this->namespace = $namespace;
    }
    
    /**
     * @param GettextWriter $writer
     * @return self
     */
    public function setWriter(GettextWriter $writer) {
        $this->writer = $writer;
        
        return $this;
    }
    
    /**
     * @param string $name
     * @param Closure $callback
     * @return self
     * @throws ConfigException
     */
    public function add($name, $callback = NULL) {
        if (isset($this->additional[$name])) {
            throw new ConfigException("Additional '$name' already exist.");
        }
        
        $this->additional[$name] = $callback;
        
        return $this;
    }
    
    /**
     * @param Nette\Caching\Cache $cache
     * @return self
     */
    public function setCache(Nette\Caching\Cache $cache) {
        $this->cache = $cache;
        
        return $this;
    }
    
    protected function checkCache() {
        if ($this->cache === NULL) {
            return NULL;
        }
        
        $value = $this->cache->load('_configLoader');
        
        if ($value === NULL) {
            return NULL;
        } else {
            $this->data = $value;
            
            return TRUE;
        }
    }
    
    /**
     * @param array $data
     */
    protected function saveToCache(array $data) {
        if ($this->cache === NULL) {
            return;
        }
        
        $this->cache->save('_configLoader', $data);
    }
    
    /**
     * @return boolean
     */
    protected function processDisabled() {
        $finder = new Nette\Utils\Finder;
        
        if ($this->checkCache() === NULL) {
            foreach ($finder->find('*.neon')->from($this->appDir . $this->namespace) as $file) {
                $this->parse($file);
            }
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    protected function processCheck() {
        $finder = new Nette\Utils\Finder;
        
        if ($this->processDisabled() === FALSE) {
            foreach ($finder->find('*.neon')->from($this->appDir . $this->namespace) as $file) {
                if ($file->getMTime() > $this->cache->getMTime('_configLoader')) {
                    $this->parse($file);
                }
            }
        }
    }
    
    /**
     * Process data from configs
     * 
     * @return self
     */
    public function execute($type = 0) {
        if ($type === self::DISABLED) {
            $this->processDisabled();
        } else {
            $this->processCheck();
        }
        
        return $this;
    }
    
    /**
     * @param SplFileInfo $file
     */
    protected function parse($file) {
        $neon = new Neon((string) $file);
        
        $baseName = str_replace(' ', '', Strings::capitalize(str_replace('.', ' ',$file->getBasename('.neon'))));

        $array = $neon->decode();
        
        // Process models
        if (isset($array['model'])) {
            foreach ((array) $array['model'] as $model) {
                if (!in_array($model, $this->data['models'])) {
                    $this->data['models'][] = $model;
                }
            }
        }
        
        // Process routers
        if (isset($array['router'])) {
            foreach ((array) $array['router'] as $router) {
                if (!in_array($router, $this->data['routers'])) {
                    $this->data['routers'][] = $router;
                }
            }
        }
        
        // Process authorizator
        if (isset($array['authorizator']) && is_array($array['authorizator'])) {
            $authorizator = &$array['authorizator'];
            
            foreach (array('resources', 'privileges') as $row) {
                if (isset($authorizator[$row]) && is_array($authorizator[$row])) {
                    $this->data[$row] = $authorizator[$row];
                    
                    if ($row === 'privileges') {
                        foreach ($authorizator[$row] as $privileges) {
                            $this->writeGettext($privileges);
                        }
                    } else {
                        $this->writeGettext($authorizator[$row]);
                    }
                }
            }
            
        }
        
        // Process menu
        if (isset($array['menu']) && is_array($array['menu'])) {
            $this->data['menu'][] = $array['menu'];
            
            $this->processMenu($array['menu']);
        }
        
        // Process parameters
        if (isset($array['parameters']) && is_array($array['parameters'])) {
            $this->data['parameters'][$baseName] = new Settings($array['parameters'], (string) $file, $baseName, $this->cache);
        }
        
        foreach ($this->additional as $name => $callback) {
            if (isset($array[$name])) {
                if (is_callable($callback)) {
                    $this->data[$name] = $callback($array[$name], $baseName);
                } else {
                    $this->data[$name] = $array[$name];
                }
             }
        }
        
        if ($this->writer !== NULL) {
            $this->writer->save();
        }
        
        $this->saveToCache($this->data);
    }
    
    /**
     * @use GettextWriter
     * @param array $array
     */
    private function writeGettext(array $array) {
        if ($this->writer === NULL) {
            return;
        }
        
        $this->writer->add($array);
    }
    
    /**
     * @use GettextWriter
     * @param array $array
     */
    private function processMenu(array $array) {
        if ($this->writer === NULL) {
            return;
        }
        
        foreach (array('parent', 'childrens') as $name) {
            foreach ($array[$name] as $row) {
                $this->writer->add($row['name']);
            }
        }
    }
    
    /**
     * @param string $namespace
     * @return Settings
     * @throws ConfigException
     */
    public function getParameters($namespace = NULL) {
        if ($namespace !== NULL && !isset($this->data['parameters'][$namespace])) {
            throw new ConfigException("Namespace '$namespace' does not exist.");
        }
        
        if ($namespace === NULL) {
            return $this->data['parameters'];
        }
        
        return $this->data['parameters'][$namespace];
    }
    
    /**
     * @param string $name
     * @return mixed
     */
    public function &__get($name) {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        
        return parent::__get($name);
    }
}
