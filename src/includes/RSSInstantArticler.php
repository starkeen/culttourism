<?php

/**
 * Компонент подготовки статей для Instant Articles
 */
class RSSInstantArticler implements IRSSGenerator
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

    public function __set($name, $value)
    {
        $this->generator->{$name} = $value;
    }

    public function __isset($name)
    {
        return $this->generator->{$name} !== null;
    }

    /**
     * @param array $data
     * @return string
     */
    public function process(array $data)
    {
        $pattern = '~(<p[^>]+>)(\s*<a[^>]+>\s*<img[^>]+>\s*</a>\s*)(</p>)~uis';
        $replace = '$2';
        $prepared = [];

        foreach ($data as $item) {
            $item['text'] = preg_replace($pattern, $replace, $item['text']);
            $prepared[] = $item;
        }

        return $this->generator->process($prepared);
    }
}