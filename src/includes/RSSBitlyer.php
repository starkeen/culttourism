<?php

class RSSBitlyer implements IRSSGenerator
{
    /** @var RSSGenerator */
    private $generator;

    /** @var Bitly */
    private $bitly;

    /** @var string */
    public $rootUrl;

    public function __construct(IRSSGenerator $generator, Bitly $bitly)
    {
        $this->generator = $generator;
        $this->bitly = $bitly;
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
     *
     * @return string
     */
    public function process(array $data)
    {
        $pattern = sprintf('#(.*)href="(%s.*)"(.*)#uUi', $this->getRootUrl());
        foreach($data as $i => $item) {
            $text = preg_replace_callback($pattern, function ($matches) {
                $linkOld = $matches[2];
                $linkNew = $this->bitly->short($linkOld);

                return str_replace($linkOld, $linkNew, $matches[0]);
            }, $item['br_text_absolute']);
            $data[$i]['br_text_absolute'] = $text;
        }

        return $this->generator->process($data);
    }

    /**
     * @return string
     */
    private function getRootUrl()
    {
        if ($this->rootUrl === null) {
            $this->rootUrl = _SITE_URL;
        }
        return $this->rootUrl;
    }
}