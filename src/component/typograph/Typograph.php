<?php

declare(strict_types=1);

namespace app\component\typograph;

use JoliTypo\Fixer;
use JoliTypo\Fixer\Dash;
use JoliTypo\Fixer\Ellipsis;
use JoliTypo\Fixer\NoSpaceBeforeComma;
use JoliTypo\Fixer\SmartQuotes;

class Typograph
{
    private const TYPICAL_MISTAKES = [
        'вв.</nobr>ек' => 'век',
        '<nobr>' => '',
        '</nobr>' => '',
        '−' => '-',
        '-' => '-',
        '–' => '-',
        ' − ' => ' – ',
    ];

    private const FIXERS_LIST = [
        Dash::class,
        Ellipsis::class,
        SmartQuotes::class,
        NoSpaceBeforeComma::class,
    ];

    private Fixer $fixer;

    public function __construct(Fixer $fixer)
    {
        $this->fixer = $fixer;
        $this->fixer->setLocale('ru_RU');
        $this->fixer->setRules(self::FIXERS_LIST);
    }

    /**
     * @param string $input
     *
     * @return string
     */
    public function typo(string $input): string
    {
        $result = $this->fixer->fix($input);

        return $this->postProcessing($result);
    }

    /**
     * Исправляет в тексте ошибки, допущенные типографом
     *
     * @param string $input
     *
     * @return string
     */
    private function postProcessing(string $input): string
    {
        $replaced = str_replace(
            array_keys(self::TYPICAL_MISTAKES),
            array_values(self::TYPICAL_MISTAKES),
            $input
        );

        return html_entity_decode($replaced, ENT_QUOTES, 'UTF-8');
    }
}
