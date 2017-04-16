<?php

/**
 * @property string $url
 */
interface IRSSGenerator
{
    public function __set($name, $value);

    public function process(array $data);
}