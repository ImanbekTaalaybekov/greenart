<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    protected $fillable = [
        'created_by', 'title', 'body', 'audience', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos(): HasMany {
        return $this->hasMany(AnnouncementPhoto::class);
    }

    protected static function booted()
    {
        static::deleting(function (Announcement $announcement) {
            $announcement->photos()->get()->each->delete();
        });
    }
}