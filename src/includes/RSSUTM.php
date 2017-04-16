<?php

/**
 * Компонент для добавления меток utm_ к ссылкам в RSS
 */
class RSSUTM implements IRSSGenerator
{
    /** @var RSSGenerator */
    private $generator;

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

    /**
     * @param array $data
     * @return string
     */
    public function process(array $data)
    {
        return $this->generator->process($data);
    }
}