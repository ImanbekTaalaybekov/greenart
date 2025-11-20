<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementRead extends Model
{
    protected $fillable = ['announcement_id','user_id','read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function announcement(): BelongsTo {
        return $this->belongsTo(Announcement::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
