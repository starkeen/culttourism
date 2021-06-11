<?php

declare(strict_types=1);

namespace config;

/**
 * Список доступных кэшей и их настройки
 */
class CachesConfig
{
    public const REFS = 'refs';
    public const SYSPROPS = 'sysprops';
    public const REDIRECTS = 'redirects';
    public const CANDIDATES_BLACKLIST = 'candidates_domains_blacklist';

    public const CONFIG = [
        self::REFS => [
            'dir' => 'refs',
            'lifetime' => 3600,
        ],
        self::SYSPROPS => [
            'dir' => 'sysprops',
            'lifetime' => 3600,
        ],
        self::REDIRECTS => [
            'dir' => 'redirects',
            'lifetime' => 3600,
        ],
        self::CANDIDATES_BLACKLIST => [
            'dir' => 'candidates_domains_blacklist',
            'lifetime' => 3600,
        ],
    ];
}
