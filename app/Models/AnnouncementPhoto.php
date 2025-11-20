<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AnnouncementPhoto extends Model
{
    protected $fillable = ['announcement_id','path','original_name','mime_type','size'];

    public function announcement(): BelongsTo {
        return $this->belongsTo(Announcement::class);
    }

    protected static function booted()
    {
        static::deleting(function (AnnouncementPhoto $photo) {
            if ($photo->path && Storage::disk('public')->exists($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }
        });
    }
}
