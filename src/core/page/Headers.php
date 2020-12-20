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

    /**
     * @param string $location
     * @param bool|null $terminate
     */
    public function sendRedirect(string $location, bool $terminate = false): void
    {
        $this->add('HTTP/1.1 301 Moved Permanently');
        $this->add('Location: ' . $location);
        if ($terminate) {
            $this->flush();
            exit();
        }
    }
}
