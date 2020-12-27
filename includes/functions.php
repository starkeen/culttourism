<?php

function cut_trash_text($data): string
{
    $text = trim(strip_tags($data));

    return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
}

function cut_trash_float($data): float
{
    $text = str_replace(',', '.', trim($data));

    return (float) $text;
}
