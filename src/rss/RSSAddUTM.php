<?php

namespace app\rss;

/**
 * Компонент для добавления меток utm_ к ссылкам в RSS
 */
class RSSAddUTM extends RSSComponent
{
    /** @var string */
    public $rootUrl;

    /**
     * @param array $data
     * @return string
     */
    public function process(array $data): string
    {
        $pattern = sprintf('#(.*)href="(%s.*)"(.*)#uUi', $this->getRootUrl());

        foreach($data as $i => $item) {
            $text = preg_replace_callback($pattern, function ($matches) {
                $linkOld = $matches[2];
                $linkNew = $this->addUTM($linkOld);

                return str_replace($linkOld, $linkNew, $matches[0]);
            }, $item['br_text_absolute']);
            $data[$i]['br_text_absolute'] = $text;
        }

        return $this->generator->process($data);
    }

    /**
     * @param string $link
     *
     * @return string
     */
    private function addUTM(string $link): string
    {
        $result = '';

        $url = parse_url($link);
        $result .= $url['scheme'] . '://' . $url['host'];
        if (!empty($url['port'])) {
            $result .= ':' . $url['port'];
        }
        if (!empty($url['path'])) {
            $result .= $url['path'];
        }

        $query = [];
        parse_str($url['query'] ?? '', $query);

        if (!empty($query)) {
            $result .= '?' . http_build_query($query);
        }
        if (!empty($url['fragment'])) {
            $result .= '#' . $url['fragment'];
        }

        return urldecode($result);
    }

    /**
     * @return string
     */
    private function getRootUrl(): string
    {
        if ($this->rootUrl === null) {
            $this->rootUrl = _SITE_URL;
        }

        return $this->rootUrl;
    }
}