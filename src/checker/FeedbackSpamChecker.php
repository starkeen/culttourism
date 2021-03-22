<?php

declare(strict_types=1);

namespace app\checker;

class FeedbackSpamChecker
{
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
    ];

    public function isSpamURL(?string $url): bool
    {
        if ($url === null) {
            return false;
        }
        $host = parse_url(trim($url), PHP_URL_HOST);

        return in_array($host, self::SPAM_DOMAINS, true);
    }
}
