<?php

namespace app\rss;

/**
 * Компонент для добавления меток utm_ к ссылкам в RSS
 */
class RSSAddUTM extends RSSComponent
{
    /**
     * @var string
     */
    public $rootUrl;

    protected $utm = [
        'utm_source' => null,
        'utm_medium' => 'blog',
        'utm_content' => null,
        'utm_campaign' => 'feed',
        'utm_term' => null,
    ];

    public function __construct(IRSSGenerator $generator, string $source = null)
    {
        parent::__construct($generator);
        $this->utm['utm_source'] = $source;
    }

    /**
     * @param  array $data
     * @return string
     */
    public function process(array $data): string
    {
        $pattern = sprintf('#(.*)href="(%s.*)"(.*)#uUi', $this->getRootUrl());

        foreach($data as $i => $item) {
            $text = preg_replace_callback(
                $pattern,
                function ($matches) use ($item) {
                    $linkOld = $matches[2];
                    $utmContent = date('Ymd', strtotime($item['br_date']));
                    $linkNew = $this->addUTM($linkOld, $utmContent);

                    return str_replace($linkOld, $linkNew, $matches[0]);
                },
                $item['br_text_absolute']
            );

            $data[$i]['br_text_absolute'] = $text;
        }

        return $this->generator->process($data);
    }

    /**
     * @param string      $link
     * @param string|null $content
     *
     * @return string
     */
    private function addUTM(string $link, string $content = null): string
    {
        $result = '';

        $url = parse_url($link);
        $result .= $url['scheme'] . '://' . $url['host'];
        if (!empty($url['port'])) {
            $result .= ':' . $url['port'];
        }
        if (!empty($url['path'])) {
            $result .= $url['path'];
        } else {
            $result .= '/';
        }

        $query = [];
        parse_str($url['query'] ?? '', $query);

        $utmItems = array_merge($this->utm, ['utm_content' => $content]);
        $query = array_merge($query, array_filter($utmItems));

        if (!empty($query)) {
            $result .= '?' . http_build_query($query);
        }
        if (!empty($url['fragment'])) {
            $result .= '#' . $url['fragment'];
        }

        return urldecode($result);
    }

    /**
     * @return string http://host.tld
     */
    private function getRootUrl(): string
    {
        if ($this->rootUrl === null) {
            $this->rootUrl = rtrim(GLOBAL_SITE_URL, '/');
        }

        return $this->rootUrl;
    }
}
