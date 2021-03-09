<?php

declare(strict_types=1);

namespace app\checker;

class FeedbackSpamChecker
{
    private const SPAM_DOMAINS = [
        'advokat-zp.in.ua',
        'creditonline.in.ua',
        'credit-odessa.com',
        'credit-online.ws',
        'credit-ukraine.org',
        'forum.3u.com',
        'qadigitin.com',
        'india-express.net',
        'karantina.pertanian.go.id',
        'lt.druggstorre.biz',
        'pornax.net',
        'shopbalu.ru',
        'snaked.info',
        'www.besteuhren.io',
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
