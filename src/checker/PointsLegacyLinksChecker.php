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
        18200,
        18284,
        18355,
        18358,
        18359,
        18386,
        18404,
        18405,
        18413,
        18415,
        18449,
        18465,
        18469,
        18470,
        18472,
        18473,
        18511,
        18512,
        18525,
        18529,
        18533,
        18556,
        18574,
        18578,
        18597,
        18648,
        18678,
        18703,
        18753,
        18776,
        18780,
        18801,
        18919,
        18920,
        18934,
        18971,

        19086,
        19108,
        19112,
        19148,
        19216,
        19306,
        19437,
        19438,
        19439,
        19440,
        19441,
        19442,
        19443,
        19444,
        19446,
        19447,
        19448,
        19449,
        19450,
        19451,
        19452,
        19453,
        19454,
        19455,
        19456,
        19457,
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
