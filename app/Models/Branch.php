<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = ['name', 'location', 'phone'];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function collectors(): HasMany
    {
        return $this->hasMany(Collector::class);
    }
}
