<?php

declare(strict_types=1);

namespace app\checker;

use app\cache\Cache;
use app\model\repository\CandidateDomainBlacklistRepository;

class FeedbackSpamChecker
{
    private const CACHE_KEY = 'list';

    private Cache $cache;

    private CandidateDomainBlacklistRepository $repository;

    public function __construct(Cache $cache, CandidateDomainBlacklistRepository $repository)
    {
        $this->cache = $cache;
        $this->repository = $repository;
    }

    public function isSpamURL(?string $url): bool
    {
        if ($url === null) {
            return false;
        }

        if (strpos($url, 'http://Ваша') === 0) {
            return false;
        }

        $host = $this->getDomain($url);

        $spamDomains = $this->cache->get(self::CACHE_KEY);
        if (empty($spamDomains)) {
            $spamDomains = $this->repository->getActualDomainsList();
            $this->cache->put(self::CACHE_KEY, $spamDomains);
        }

        return in_array($host, $spamDomains, true);
    }

    public function appendURL(string $url): void
    {
        if (trim($url) !== '') {
            $host = $this->getDomain($url);
            $this->repository->append($host);
            $this->cache->remove(self::CACHE_KEY);
        }
    }

    private function getDomain(string $url): string
    {
        $url = str_replace('[url=', '', trim($url));

        return parse_url(trim($url), PHP_URL_HOST);
    }
}
