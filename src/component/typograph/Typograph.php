<?php

declare(strict_types=1);

namespace app\component\typograph;

use EMT\EMTypograph;

class Typograph
{
    /**
     * @var EMTypograph
     */
    private $service;

    private const TYPICAL_MISTAKES = [
        'вв.</nobr>ек' => 'век',
        '<nobr>' => '',
        '</nobr>' => '',
        '−' => '-',
    ];

    public function __construct(EMTypograph $baseService)
    {
        $this->service = $baseService;
    }

    /**
     * @param string $input
     *
     * @return string
     */
    public function typo(string $input): string
    {
        $this->service->setup(
            [
                'Text.paragraphs' => 'off',
                'Text.breakline' => 'off',
                'OptAlign.oa_oquote' => 'off',
                'OptAlign.oa_oquote_extra' => 'off',
                'OptAlign.oa_obracket_coma' => 'off',
                'Space.nbsp_before_open_quote' => 'off',
                'Space.nbsp_before_month' => 'off',
                'Nobr.super_nbsp' => 'off',
                'Nobr.nbsp_in_the_end' => 'off',
                'Nobr.phone_builder' => 'off',
                'Nobr.phone_builder_v2' => 'off',
                'Nobr.spaces_nobr_in_surname_abbr' => 'off',
                'Etc.split_number_to_triads' => 'off',
            ]
        );

        $this->service->set_text($input);
        $result = $this->service->apply();

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
        $replaced = str_replace(array_keys(self::TYPICAL_MISTAKES), array_values(self::TYPICAL_MISTAKES), $input);

        return html_entity_decode($replaced, ENT_QUOTES, 'UTF-8');
    }
}
