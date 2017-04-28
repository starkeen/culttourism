<?php

use app\rss\RSSComponent;

/**
 * Компонент для добавления меток utm_ к ссылкам в RSS
 */
class RSSAddUTM extends RSSComponent
{
    /**
     * @param array $data
     * @return string
     */
    public function process(array $data): string
    {
        return $this->generator->process($data);
    }
}