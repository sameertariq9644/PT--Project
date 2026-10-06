<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Affiliate = 'affiliate';

    /**
     * The route a user of this role lands on after logging in.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.leads',
            self::Affiliate => 'affiliate.portal',
        };
    }
}
