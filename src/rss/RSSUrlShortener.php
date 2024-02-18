<?php

namespace app\rss;

use app\services\shortio\ShortIoClient;
use RuntimeException;

class RSSUrlShortener extends RSSComponent
{
    private ShortIoClient $shortener;

    public ?string $rootUrl = null;

    public function __construct(RSSGeneratorInterface $generator, ShortIoClient $shortener)
    {
        parent::__construct($generator);
        $this->shortener = $shortener;
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

        foreach ($data as $i => $item) {
            $text = preg_replace_callback(
                $pattern,
                function ($matches) {
                    $linkOld = $matches[2];
                    $linkNew = $this->shortener->short($linkOld);

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
