<?php

namespace app\rss;

use app\includes\Bitly;
use RuntimeException;

class RSSBitlyer extends RSSComponent
{
    /**
     * @var Bitly
     */
    private $bitly;

    /**
     * @var string
     */
    public $rootUrl;

    public function __construct(IRSSGenerator $generator, Bitly $bitly)
    {
        parent::__construct($generator);
        $this->bitly = $bitly;
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws RuntimeException
     */
    public function process(array $data): string
    {
        $pattern = sprintf('#(.*)href="(%s.*)"(.*)#uUi', $this->getRootUrl());

        foreach($data as $i => $item) {
            $text = preg_replace_callback(
                $pattern,
                function ($matches) {
                    $linkOld = $matches[2];
                    $linkNew = $this->bitly->short($linkOld);
                    return str_replace($linkOld, $linkNew, $matches[0]);
                },
                $item['br_text_absolute']
            );
            $data[$i]['br_text_absolute'] = $text;
        }

        return $this->generator->process($data);
    }

    /**
     * @return string
     */
    private function getRootUrl(): string
    {
        if ($this->rootUrl === null) {
            $this->rootUrl = GLOBAL_SITE_URL;
        }
        return $this->rootUrl;
    }
}
