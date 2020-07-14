<?php

declare(strict_types=1);

namespace app\api\google_search\constant;

use MyCLabs\Enum\Enum;

/**
 * @method static self SEARCH_TYPE_UNDEFINED()
 * @method static self IMAGE()
 */
class SearchType extends Enum
{
    public const SEARCH_TYPE_UNDEFINED = 'SEARCH_TYPE_UNDEFINED'; // Search type unspecified (defaults to web search).
    public const IMAGE = 'image'; // Image search.
}
