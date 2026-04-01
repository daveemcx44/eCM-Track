<?php

use App\Support\TenantContext;

if (! function_exists('current_tenant_id')) {
    /**
     * Get the current tenant ID from context, auth user, or default.
     */
    function current_tenant_id(): ?int
    {
        // 1. Explicit context (set by middleware or tests)
        $contextId = TenantContext::get();
        if ($contextId !== null) {
            return $contextId;
        }

        // 2. From authenticated user
        $user = auth()->user();
        if ($user && $user->tenant_id) {
            return (int) $user->tenant_id;
        }

        return null;
    }
}
