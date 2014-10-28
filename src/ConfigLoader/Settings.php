<?php

/**
 * @author Xethilos
 */
class Settings implements ArrayAccess {
    
    /** @var array */
    protected $values = array();
    
    /** @var string */
    protected $filePath;
    
    /** @var string */
    protected $namespace;
    
    /** @var Nette\Caching\Cache */
    protected $cache = NULL;
    
    /**
     * @param array $values
     * @param string $filePath
     * @param string $namespace
     * @param Nette\Caching\Cache|null $cache
     */
    public function __construct(array $values, $filePath, $namespace, $cache) {
        $this->values = $values;
        $this->filePath = $filePath;
        $this->namespace = $namespace;
        $this->cache = $cache;
    }
    
    /**
     * @param string $offset
     */
    public function offsetExists($offset) {
        return isset($this->values[$offset]);
    }
    
    /**
     * @param string $offset
     */
    public function offsetGet($offset) {
        return $this->values[$offset];
    }
    
    /**
     * @param string $offset
     * @param mixed $value
     * @throws ConfigException
     */
    public function offsetSet($offset, $value) {
        throw new ConfigException('You can change array with function change().');
    }
    
    /**
     * @param string $offset
     * @throws ConfigException
     */
    public function offsetUnset($offset) {
        throw new ConfigException('You cannot unset value.');
    }
    
    /**
     * Merge and check changes
     * 
     * @param array $array
     * @param string $path
     * @throws ConfigException
     */
    protected function merge(array $array, $path) {
        foreach ($array as $index => $value) {
            if (!isset($this->values[$index])) {
                throw new ConfigException("Key '$index' does not exist in path '$path'");
            }
            
            if (is_array($value)) {
                $this->merge($value, $index);
            } else {
                $this->values[$index] = $value;
            }
        }
    }
    
    /**
     * Save changes to neon
     * 
     * @param array $array
     */
    public function change(array $array) {
        $this->merge($array, $this->namespace);
        
        $neon = new Neon($this->filePath);
        
        $neon->encode(array('parameters' => $this->values) + $neon->decode());
        
        $this->changeCache($this->namespace, $this);
    }
    
    /**
     * Save changes to cache
     */
    public function changeCache() {
        if ($this->cache === NULL) {
            return;
        }
        
        $data = $this->cache->load('_configLoader');
        
        $data['parameters'][$this->namespace] = $this;
        
        $this->cache->save('_configLoader', $data);
    }
}
