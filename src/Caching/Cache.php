<?
namespace Caching;

class Cache extends Nette\Caching\Cache {
    
    /**
     * @param string $key
     * @return int splFileInfo::getMTime()
     */
    public function getMTime($key) {
        return $this->getStorage()->getMTime($this->generateKey($key));
    }
}
