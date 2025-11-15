<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderSchedule extends Model
{
    protected $fillable = [
        'order_id',
        'worker_id',
        'scheduled_for',
        'status',
    ];

    protected $casts = [
        'scheduled_for' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
