<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'client_id',
        'worker_id',
        'description',
        'payment_type',
        'payment_money',
        'status',
    ];

    protected $casts = [
        'payment_money' => 'decimal:2',
    ];

    public function client(): BelongsTo {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function worker(): BelongsTo {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function photos(): HasMany {
        return $this->hasMany(OrderPhoto::class);
    }

    public function reports(): HasMany {
        return $this->hasMany(OrderReport::class);
    }

    public function isIncluded(): bool {
        return $this->payment_type === 'included';
    }
}
