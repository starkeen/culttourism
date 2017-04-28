<?php

use app\rss\RSSComponent;

/**
 * Компонент подготовки статей для Instant Articles
 */
class RSSInstantArticler extends RSSComponent
{
    const PATTERN = '~(<p[^>]+>)(\s*<a[^>]+>\s*<img[^>]+>\s*</a>\s*)(</p>)~uis';
    const REPLACE = '$2';

    public function __construct(IRSSGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param array $data
     * @return string
     */
    public function process(array $data): string
    {
        $prepared = [];

        foreach ($data as $item) {
            $item['br_text'] = preg_replace(self::PATTERN, self::REPLACE, $item['br_text']);
            $item['br_text_absolute'] = preg_replace(self::PATTERN, self::REPLACE, $item['br_text_absolute']);
            $prepared[] = $item;
        }

        return $this->generator->process($prepared);
    }
}