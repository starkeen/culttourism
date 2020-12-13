<?php

declare(strict_types=1);

namespace app\core\page;

class Headers
{
    /**
     * @var string[]
     */
    private $headers = [];

    public function add(string $header): void
    {
        $this->headers[] = $header;
    }

    public function flush(): void
    {
        foreach ($this->headers as $header) {
            header($header);
        }
    }
}
