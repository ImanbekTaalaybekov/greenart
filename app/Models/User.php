<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{

    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_CLIENT = 'client';
    public const ROLE_WORKER = 'worker';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_ACCOUNTANT = 'accountant';

    protected $fillable = [
        'name',
        'login',
        'email',
        'phone',
        'password',
        'role',
        'default_worker_id',
        'salary',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'salary' => 'decimal:2',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        $roles = $roles ?: [self::ROLE_CLIENT];

        return in_array($this->role, $roles, true);
    }

    public function defaultWorker(): BelongsTo
    {
        return $this->belongsTo(self::class, 'default_worker_id');
    }
}
