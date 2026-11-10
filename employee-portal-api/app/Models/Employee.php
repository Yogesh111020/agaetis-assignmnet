<?php

namespace App\Models;

use App\Models\Concerns\HasRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasRouteKey;
    use SoftDeletes;

    protected $appends = ['route_key'];

    protected $fillable = [
        'name',
        'employee_id',
        'email',
        'role_id',
        'area',
        'region',
        'zone',
        'joining_date',
        'status',
        'password',
    ];

    protected $hidden = [
        'role_id',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
