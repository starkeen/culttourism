<?php

class RSSBitlyer implements IRSSGenerator
{
    /** @var RSSGenerator */
    private $generator;
    /** @var Bitly */
    private $bitly;

    public function __construct(RSSGenerator $generator, Bitly $bitly)
    {
        $this->generator = $generator;
        $this->bitly = $bitly;
    }

    public function __set($name, $value)
    {
        $this->generator->{$name} = $value;
    }

    public function process(array $data)
    {
        $pattern = sprintf('#(.*)href="(%s.*)"(.*)#uUi', _SITE_URL);
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
}