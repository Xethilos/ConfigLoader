<?php

/**
 * @author Xethilos
 */
class GettextWriter extends Nette\Object {
    
    const DISABLE = 0;
    const REWRITE = 1;
    const CHECK = 2;
    
    /** @var int */
    protected $execution = 1;
    
    /** @var string */
    protected $data = "<?php\n";
    
    /** @var string */
    protected $filePath;
    
    /**
     * @param string $filePath
     */
    public function __construct($filePath) {
        $this->filePath = $filePath;
    }
    
    /**
     * @param int $execution
     * @throws OutOfRangeException
     */
    public function setExecution($execution) {
        if (!Nette\Utils\Validators::isInRange($execution, array(0, 2))) {
            throw new OutOfRangeException('Execution value is not valid.');
        }
        
        if (self::CHECK === $execution) {
            if (file_exists($this->filePath)) {
                $this->execution = self::DISABLE;
            } else {
                $this->execution = self::REWRITE;
            }
        } else {
            $this->execution = $execution;
        }
    }
    
    /**
     * @param string $value
     */
    protected function write($value) {
        $this->data .= "_('" . addslashes($value) . "');\n";
    }
    
    /**
     * @param string|array $value
     */
    public function add($value) {
        if ($this->execution === self::DISABLE) {
            return;
        }
        
        foreach ((array) $value as $row) {
            $this->write($row);
        }
    }
    
    public function save() {
        if ($this->execution === self::DISABLE) {
            return;
        }
        
        $this->data .= "?>";
        
        $open = fopen('safe://' . $this->filePath, 'w+');
        fwrite($open, $this->data);
        fclose($open);
    }
}
