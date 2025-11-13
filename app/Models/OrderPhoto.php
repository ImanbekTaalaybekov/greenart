<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OrderPhoto extends Model
{
    protected $fillable = [
        'order_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function order(): BelongsTo {
        return $this->belongsTo(Order::class);
    }

    protected static function booted()
    {
        static::deleting(function (OrderPhoto $photo) {
            if ($photo->path && Storage::disk('public')->exists($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }
        });
    }
}
