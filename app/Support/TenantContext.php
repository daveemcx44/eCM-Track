<?php

namespace App\Support;

class TenantContext
{
    private static ?int $currentTenantId = null;

    public static function set(?int $tenantId): void
    {
        self::$currentTenantId = $tenantId;
    }

    public static function get(): ?int
    {
        return self::$currentTenantId;
    }

    public static function clear(): void
    {
        self::$currentTenantId = null;
    }
}
