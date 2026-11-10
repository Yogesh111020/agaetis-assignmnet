<?php

namespace App\Models;

use App\Models\Concerns\HasRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasRouteKey;

    protected $appends = ['route_key'];

    protected $fillable = [
        'name',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
