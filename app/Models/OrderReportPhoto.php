<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OrderReportPhoto extends Model
{
    protected $fillable = [
        'order_report_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(OrderReport::class, 'order_report_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (OrderReportPhoto $photo) {
            if ($photo->path && Storage::disk('public')->exists($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }
        });
    }
}
