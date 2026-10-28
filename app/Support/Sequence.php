<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class Sequence
{
    /**
     * Next human-readable reference such as CUS007, RCP-1034 or BCL-0003.
     * Uses the highest numeric suffix already stored so deleted rows never cause a clash.
     */
    public static function next(string $modelClass, string $column, string $prefix, int $pad = 3, int $start = 1): string
    {
        /** @var Model $model */
        $model = new $modelClass;
        $max = $model->newQuery()
            ->where($column, 'like', $prefix.'%')
            ->pluck($column)
            ->map(fn ($v) => (int) preg_replace('/\D/', '', substr($v, strlen($prefix))))
            ->max();

        $next = max((int) $max + 1, $start);

        return $prefix.str_pad((string) $next, $pad, '0', STR_PAD_LEFT);
    }
}
