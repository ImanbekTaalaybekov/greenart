<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReport extends Model
{

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(OrderReportPhoto::class);
    }

    protected static function booted()
    {
        static::deleting(function (OrderReport $report) {
            $report->photos()->get()->each->delete();
        });
    }
}