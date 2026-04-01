<?php

namespace App\Models\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        // Auto-scope all queries to the current tenant
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = current_tenant_id();
            if ($tenantId !== null) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
            }
        });

        // Auto-set tenant_id when creating new records
        static::creating(function (Model $model): void {
            if (! $model->tenant_id) {
                $model->tenant_id = current_tenant_id() ?? 1;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
