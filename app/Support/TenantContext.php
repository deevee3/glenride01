<?php

namespace App\Support;

use App\Models\Tenant;

class TenantContext
{
    private static ?Tenant $tenant = null;

    public static function set(?Tenant $tenant): void
    {
        self::$tenant = $tenant;
    }

    public static function current(): ?Tenant
    {
        return self::$tenant;
    }

    public static function forget(): void
    {
        self::$tenant = null;
    }
}

