<?php

/**
 * @property-write string $divider
 */
class Neon extends Nette\Object {
    
    /** @var string */
    private $decodeString;
    
    /** @var string */
    private $file;
    
    /** @var string */
    private $divider = '    ';
    
    /**
     * @param string $file
     */
    public function __construct($file = NULL) {
        $this->file = $file;
    }
    
    /**
     * @param array $array
     * @return string
     */
    public function encode(array $array) {
        $this->decodeString = '';
        
        $this->arrayWalk($array, 0);
        
        if ($this->file) {
            $open = fopen('safe://' . $this->file, 'w');
            
            fwrite($open, $this->decodeString);
            
            fclose($open);
        }
        
        return $this->decodeString;
    }
    
    /**
     * @param string $divider
     * @return self
     */
    public function setDivider($divider) {
        $this->divider = $divider;
        
        return $this;
    }
    
    /**
     * @param array $array
     * @param int $step
     */
    private function arrayWalk(array $array, $step) {
        foreach ($array as $key => $value) {
            $this->walk($key, $value, $step);
        }
    }
    
    /**
     * @param string $key
     * @param string $value
     * @param int $step
     * @throws Exception
     */
    private function walk($key, $value, $step) {
        if (is_object($value)) {
            throw new Exception('Neon::decode: Key must not object.');
        }
        
        $this->decodeString .= str_repeat($this->divider, $step) . $key . ':';
        
        if (is_array($value) && $value) {
            $this->decodeString .= "\n";
            
            $this->arrayWalk($value, $step + 1);
        } else {
            $this->decodeString .= ' ';
            
            if (is_array($value)) {
                $this->decodeString .= '[]';
            } else if (is_numeric($value)) {
                $this->decodeString .= $value;
            } else if (is_string($value)) {
                $this->decodeString .= "'" . $value . "'";
            } else if (is_bool($value)) {
                $this->decodeString .= $value ? 'yes' : 'no';
            } else {
                $this->decodeString .= 'null';
            }
            
            $this->decodeString .= "\n";
        }
    }
    
    /**
     * @return string
     * @throws Exception
     */
    public function decode() {
        if (!$this->file) {
            throw new Exception('Neon::decode: File must be set.');
        }
        
        $neon = new Nette\Neon\Decoder;
        
        return $neon->decode(file_get_contents($this->file));
    }
}
