<?php

declare(strict_types=1);

namespace app\api\yandex_search;

use InvalidArgumentException;

class ResultCorrection
{
    private const TYPE_MISSPELL = 'Misspell';
    private const TYPE_LAYOUT = 'KeyboardLayout';
    private const TYPE_VOLAPYUK = 'Volapyuk';

    private const TYPES = [
        self::TYPE_MISSPELL,
        self::TYPE_LAYOUT,
        self::TYPE_VOLAPYUK,
    ];

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $sourceText;

    /**
     * @var string
     */
    private $resultText;

    public function __construct(string $type)
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Неизвестный тип коррекции');
        }
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getSourceText(): string
    {
        return $this->sourceText;
    }

    /**
     * @param string $sourceText
     */
    public function setSourceText(string $sourceText): void
    {
        $this->sourceText = $sourceText;
    }

    /**
     * @return string
     */
    public function getResultText(): string
    {
        return $this->resultText;
    }

    /**
     * @param string $resultText
     */
    public function setResultText(string $resultText): void
    {
        $this->resultText = $resultText;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
