<?php

declare(strict_types=1);

namespace app\utils;

class JSON
{
    public static function echo(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}
