<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'loan_officer' => 'Loan Officer',
        'collector' => 'Field Collector',
    ];

    protected $fillable = ['name', 'email', 'password', 'phone', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function collector(): HasOne
    {
        return $this->hasOne(Collector::class);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Staff who can be named as a loan/credit officer. Administrator accounts are only listed for
     * other administrators; $keep still includes an already-assigned officer so editing doesn't drop them.
     */
    public function scopeOfficers(Builder $query, ?int $keep = null): void
    {
        $query->whereIn('role', ['loan_officer', 'manager', 'admin'])
            ->when(! auth()->user()?->isAdmin(), fn ($q) => $q->where(fn ($w) => $w->where('role', '!=', 'admin')
                ->when($keep, fn ($w) => $w->orWhere('id', $keep))))
            ->orderBy('name');
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
    }
}
