<?php

namespace app\rss;

use RSSGenerator;

abstract class RSSComponent implements IRSSGenerator
{
    /** @var RSSGenerator */
    protected $generator;

    public function __construct(IRSSGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function __get($name)
    {
        return $this->generator->{$name} ?? null;
    }

    public function __isset($name)
    {
        return $this->generator->{$name} !== null;
    }

    public function __set($name, $value)
    {
        $this->generator->{$name} = $value;
    }

    abstract public function process(array $data): string;
}