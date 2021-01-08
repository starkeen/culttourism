<?php

declare(strict_types=1);

namespace app\api\google_search;

class Request
{
    private string $query;

    private int $limit = 10;

    private int $page = 0;

    /**
     * @var string[]
     */
    private array $options = [];

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
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     */
    public function setPage(int $page): void
    {
        $this->page = $page;
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

    public function getData(): array
    {
        return array_merge(
            [
                'q' => $this->getQuery(),
                'num' => $this->getLimit(),
            ],
            $this->options
        );
    }
}
