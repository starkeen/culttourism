<?php

namespace app\rss;

/**
 * @property string $url
 */
interface RSSGeneratorInterface
{
    public function __get($name);

    public function __isset($name);

    public function __set($name, $value);

    public function process(array $data);
}
