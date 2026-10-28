<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** Field collectors only see their own customers and receipts. Returns null for everyone else. */
    protected function ownCollectorId(): ?int
    {
        $user = auth()->user();

        return $user?->role === 'collector' ? ($user->collector?->id ?? 0) : null;
    }
}
