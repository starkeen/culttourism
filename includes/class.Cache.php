<?php

/**
 * Класс для локального кэширования данных
 */
class Cache
{

    protected static $_instance = [];

    /**
     * Список доступных кэшей
     * @var array
     */
    private $config = [
        'refs' => [
            'dir' => 'refs',
            'lifetime' => 3600,
        ],
        'sysprops' => [
            'dir' => 'sysprops',
            'lifetime' => 3600,
        ],
        'redirects' => [
            'dir' => 'redirects',
            'lifetime' => 3600,
        ],
    ];
    private $cacheDir = null;
    private $cacheCurrent = null;

    private function __construct($cache_id)
    {
        if (isset($this->config[$cache_id])) {
            $this->cacheCurrent = $this->config[$cache_id];
            $this->cacheDir = _DIR_DATA . '/private/cache';
        }
    }

    /**
     * Инстанциатор кэша
     *
     * @param string $cache
     *
     * @return self
     */
    public static function i($cache)
    {
        if (!isset(self::$_instance[$cache])) {
            // создаем новый экземпляр
            self::$_instance[$cache] = new self($cache);
        }
        return self::$_instance[$cache];
    }

    /**
     * Читаем данные из кэша
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
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
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    public function put($key, $value)
    {
        $fileDir = $this->cacheDir . '/' . $this->cacheCurrent['dir'] . '/';
        if (!file_exists($fileDir)) {
            try {
                mkdir($fileDir);
            } catch (Throwable $e) {
                // ничего страшного
            }
        }

        return file_put_contents($fileDir . $key, serialize($value), LOCK_EX) > 0;
    }

    /**
     * Удаляем ключ из кэша
     *
     * @param string $key
     *
     * @return bool|null
     */
    public function remove($key)
    {
        $filename = $this->cacheDir . '/' . $this->cacheCurrent['dir'] . '/' . $key;
        $result = null;
        if (file_exists($filename)) {
            try {
                $result = unlink($filename);
            } catch (Throwable $e) {
                // молча глотаем обиду
            }
        }

        return $result;
    }
}
