<?php

class Storage extends Nette\Caching\Storages\FileStorage implements Nette\Caching\IStorage {
    
    /**
     * 
     * @param string $key
     * @return int splFileInfo::getMTime()
     */
    public function getMTime($key) {
        $hash = $this->getCacheFile($key);
        
        $file = new SplFileInfo($hash);
        
        return $file->getMTime();
    }
}
