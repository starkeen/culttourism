<?php

namespace app\cache;

use config\CachesConfig;
use http\Exception\RuntimeException;
use Throwable;

/**
 * Класс для локального кэширования данных
 */
class Cache
{
    /**
     * @var self[]
     */
    protected static array $instances = [];

    /** @var string */
    private $cacheDir;

    /** @var mixed */
    private $cacheCurrent;

    private function __construct(string $cacheId)
    {
        if (isset(CachesConfig::CONFIG[$cacheId])) {
            $this->cacheCurrent = CachesConfig::CONFIG[$cacheId];
            $this->cacheDir = GLOBAL_DIR_CACHE;
        }
    }

    /**
     * Инстанциатор кэша
     *
     * @param string $cache
     *
     * @return self
     */
    public static function i(string $cache): self
    {
        if (!isset(self::$instances[$cache])) {
            // создаем новый экземпляр
            self::$instances[$cache] = new self($cache);
        }

        return self::$instances[$cache];
    }

    /**
     * Читаем данные из кэша
     *
     * @param string $key
     *
     * @return array|string|null
     */
    public function get(string $key)
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
     * @param mixed $value
     *
     * @return bool
     */
    public function put(string $key, $value): bool
    {
        $fileDir = $this->cacheDir . '/' . $this->cacheCurrent['dir'] . '/';
        if (!file_exists($fileDir)) {
            mkdir($fileDir);
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
    public function remove(string $key)
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
     * @param mixed $data
     *
     * @return string
     */
    private function serialize($data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $data
     *
     * @return mixed
     */
    private function unserialize(string $data)
    {
        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }
}
