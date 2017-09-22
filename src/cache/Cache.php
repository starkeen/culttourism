<?php

namespace app\cache;

use Throwable;

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
    const CONFIG = [
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

    /** @var string */
    private $cacheDir;

    /** @var mixed */
    private $cacheCurrent;

    private function __construct($cache_id)
    {
        if (isset(self::CONFIG[$cache_id])) {
            $this->cacheCurrent = self::CONFIG[$cache_id];
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
    public static function i($cache): self
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
        if (!file_exists($filename) || !is_file($filename)) {
            return null;
        }
        $created = filectime($filename);
        if (time() - $created > $this->cacheCurrent['lifetime']) {
            $this->remove($key);
            return null;
        }

        $content = file_get_contents($filename);

        return $this->unserialize($content);
    }

    /**
     * Записываем данные в кэш
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    public function put($key, $value): bool
    {
        $fileDir = $this->cacheDir . '/' . $this->cacheCurrent['dir'] . '/';
        if (!file_exists($fileDir)) {
            try {
                mkdir($fileDir);
            } catch (Throwable $e) {
                // ничего страшного
            }
        }

        $fileName = $fileDir . $key;
        $data = $this->serialize($value);

        return (bool) file_put_contents($fileName, $data, LOCK_EX) > 0;
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
                if (is_file($filename)) {
                    $result = @unlink($filename);
                }
            } catch (Throwable $e) {
                // молча глотаем обиду
            }
        }

        return $result;
    }

    /**
     * @param $data
     *
     * @return string
     */
    private function serialize($data): string
    {
        return serialize($data);
    }

    /**
     * @param string $data
     *
     * @return mixed
     */
    private function unserialize(string $data)
    {
        return unserialize($data, []);
    }
}
