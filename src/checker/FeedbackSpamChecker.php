<?php

declare(strict_types=1);

namespace app\checker;

use app\cache\Cache;

class FeedbackSpamChecker
{
    private const CACHE_KEY = 'list';

    private const SPAM_DOMAINS = [
        'advokat-zp.in.ua',
        'bitdouble.net',
        'bit.ly',
        'bporno.net',
        'creditonline.in.ua',
        'credit.poltava.ua',
        'credit-odessa.com',
        'credit-online.ws',
        'credit-ukraine.com',
        'credit-ukraine.org',
        'de.online-television.net',
        'elojobmax.com.br',
        'forum.3u.com',
        'qadigitin.com',
        'www.howmy.com.tw',
        'india-express.net',
        'karantina.pertanian.go.id',
        'krd-agro.ru',
        'loveawake.ru',
        'lt.druggstorre.biz',
        'mala-pozyczka-online.pl',
        'namehistory.su',
        'onliner.com.ua',
        'pornax.net',
        'seoprofisional.ru',
        'shopbalu.ru',
        'shuralcom.blogspot.com',
        'snaked.info',
        'viagra.ws',
        'www.besteuhren.io',
        'www.no-site.com',
        'www.youtube.com',
        'xlib.info',
        'yandex.ru',
    ];

    private Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function isSpamURL(?string $url): bool
    {
        if ($url === null) {
            return false;
        }

        if (strpos($url, 'http://Ваша') === 0) {
            return false;
        }

        $url = str_replace('[url=', '', trim($url));
        $host = parse_url(trim($url), PHP_URL_HOST);

        $spamDomains = $this->cache->get(self::CACHE_KEY);
        if (empty($spamDomains)) {
            $spamDomains = self::SPAM_DOMAINS;
            $this->cache->put(self::CACHE_KEY, $spamDomains);
        }

        return in_array($host, $spamDomains, true);
    }
}
