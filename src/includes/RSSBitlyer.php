<?php

class RSSBitlyer implements IRSSGenerator
{
    /** @var RSSGenerator */
    private $generator;

    public function __construct(RSSGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function __set($name, $value)
    {
        $this->generator->{$name} = $value;
    }

    public function process(array $data)
    {
        return $this->generator->process($data);
    }
}