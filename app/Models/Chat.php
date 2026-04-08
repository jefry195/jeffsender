<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    public $timestamps = false;

    public $primaryKey = 'pkId';

    protected $table = 'Chat';

    protected $guarded = [];

    protected $casts = [
        'wlc_mgs_send_at' => 'datetime',
        'auto_reply_enabled' => 'boolean',
    ];

    public function isAutoReplyEnabled(): bool
    {
        return $this->auto_reply_enabled;
    }
}
