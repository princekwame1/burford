<?php

namespace App\Support;

class Money
{
    public static function format(float|int|string|null $amount, bool $symbol = true): string
    {
        $value = number_format((float) $amount, 2);

        return $symbol ? 'GH₵ '.$value : $value;
    }
}
