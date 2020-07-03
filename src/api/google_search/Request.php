<?php

declare(strict_types=1);

namespace app\api\google_search;

class Request
{
    /**
     * @var string
     */
    private $query;

    /**
     * @var int
     */
    private $limit = 10;

    /**
     * @var string[]
     */
    private $options = [];

    public function __construct(string $query)
    {
        $this->query = $query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return string[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string[] $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function setOption(string $name, string $value): void
    {
        $this->options[$name] = $value;
    }
}
