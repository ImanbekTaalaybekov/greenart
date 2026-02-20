<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkVisit extends Model
{
    protected $fillable = [
        'worker_id',
        'order_id',
        'visit_date',
        'comment',
    ];

    protected $casts = [
        'visit_date' => 'date',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(OrderReport::class);
    }
}
