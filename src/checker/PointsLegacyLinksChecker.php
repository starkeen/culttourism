<?php

declare(strict_types=1);

namespace app\checker;

/**
 * Набор идентификаторов точек, для которых включен редирект со старого формата адресов
 */
class PointsLegacyLinksChecker
{
    private const REDIRECT_BY_ID_MAX = 18000; // редирект с урлов в старом формате не выше этого идентификатора

    private const REDIRECT_EXCEPTIONS = [ // редирект с урлов в старом формате включаем для списка идентификаторов
        18029,
        18040,
        18146,
        18147,
        18405,
        18449,
        18465,
        18469,
        18511,
        18525,
        18529,
        18533,
        18678,
        18703,
        18753,
        18780,
        18920,
        18934,
        18971,

        19086,
        19112,
        19148,
        19216,
        19306,
        19437,
        19439,
        19443,
        19444,
        19446,
        19453,
        19522,
        19762,
        19827,

        20192,
        20193,

        24428,

        31033,
    ];

    /**
     * @param int $objectId
     * @return bool
     */
    public static function isLegacyRedirectEnabled(int $objectId): bool
    {
        return $objectId > 0
            && (
                $objectId <= self::REDIRECT_BY_ID_MAX
                || in_array($objectId, self::REDIRECT_EXCEPTIONS, true)
            );
    }
}
