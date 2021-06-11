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
            return true;
        }

        $host = $this->getDomain($url);
        if ($host === null) {
            return false;
        }

        $spamDomains = $this->cache->get(self::CACHE_KEY);
        if (empty($spamDomains)) {
            $spamDomains = $this->repository->getActualDomainsList();
            throw new \RuntimeException('temp: ' . var_export($spamDomains[0], true));
            $this->cache->put(self::CACHE_KEY, $spamDomains);
        }

        return in_array($host, $spamDomains, true);
    }

    public function appendURL(string $url): void
    {
        if (trim($url) !== '') {
            $host = $this->getDomain($url);
            if ($host !== null) {
                $this->repository->append($host);
                $this->cache->remove(self::CACHE_KEY);
            }
        }
    }

    private function getDomain(string $url): ?string
    {
        $url = trim($url);
        $url = str_replace('[url=', '', $url);
        if (strpos($url, 'http') !== 0) {
            $url = 'http://' . $url;
        }

        return parse_url($url, PHP_URL_HOST);
    }
}
