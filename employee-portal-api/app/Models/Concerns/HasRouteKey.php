<?php

namespace App\Models\Concerns;

use Vinkla\Hashids\Facades\Hashids;

trait HasRouteKey
{
    public function getRouteKeyName(): string
    {
        return 'route_key';
    }

    public function getRouteKeyAttribute(): string
    {
        return Hashids::encode($this->getKey());
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $decoded = is_string($value) && strlen($value) >= 5 ? Hashids::decode($value) : [];
        $id = count($decoded) === 1 && Hashids::encode($decoded[0]) === $value ? $decoded[0] : null;

        return $query->where($this->getQualifiedKeyName(), $id);
    }
}
