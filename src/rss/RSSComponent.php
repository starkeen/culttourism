<?php

namespace app\rss;

abstract class RSSComponent implements RSSGeneratorInterface
{
    /**
     * @var RSSGeneratorInterface
     */
    protected $generator;

    public function __construct(RSSGeneratorInterface $generator)
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
