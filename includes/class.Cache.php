<?php

/**
 * Класс для локального кэширования данных
 */
class Cache {

    protected static $_instance = array();

    /**
     * Список доступных кэшей
     * @var array 
     */
    private $config = array(
        'refs' => array(
            'dir' => 'refs',
            'lifetime' => 24 * 3600,
        ),
        'sysprops' => array(
            'dir' => 'sysprops',
            'lifetime' => 3600,
        ),
    );
    private $cacheDir = null;
    private $cacheCurrent = null;

    private function __construct($cache_id) {
        if (isset($this->config[$cache_id])) {
            $this->cacheCurrent = $this->config[$cache_id];
            $this->cacheDir = _DIR_DATA . '/private/cache';
        }
    }

    /**
     * Инстанциатор кэша
     * @param string $cache
     * @return object
     */
    public static function i($cache) {
        if (!isset(self::$_instance[$cache])) {
            // создаем новый экземпляр
            self::$_instance[$cache] = new self($cache);
        }
        return self::$_instance[$cache];
    }

    /**
     * Читаем данные из кэша
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        $filename = $this->cacheDir . '/' . $this->cacheCurrent['dir'] . '/' . $key;
        if (!file_exists($filename)) {
            return null;
        }
        $created = filectime($filename);
        if (time() - $created > $this->cacheCurrent['lifetime']) {
            $this->remove($key);
            return null;
        }
        return unserialize(file_get_contents($filename));
    }

    /**
     * Записываем данные в кэш
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function put($key, $value) {
        $filedir = $this->cacheDir . '/' . $this->cacheCurrent['dir'] . '/';
        if (!file_exists($filedir)) {
            mkdir($filedir);
        }
        return file_put_contents($filedir . $key, serialize($value), LOCK_EX) > 0;
    }

    /**
     * Удаляем ключ из кэша
     * @param string $key
     * @return bool
     */
    public function remove($key) {
        $filename = $this->cacheDir . '/' . $this->cacheCurrent['dir'] . '/' . $key;
        if (file_exists($filename)) {
            return unlink($filename);
        } else {
            return null;
        }
    }

}
