<?php

namespace Modules\QAReply\App\Models;

use App\Models\User;
use App\Traits\HasModule;
use App\Traits\HasOwner;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Model;
use Modules\QAReply\App\Models\QaReplyItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QaReply extends Model
{
    use HasFactory, HasOwner, HasModule, HasStatus;

    protected $fillable = [
        'owner_id',
        'module',
        'title',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function items()
    {
        return $this->hasMany(QaReplyItem::class, 'qa_reply_id');
    }
}
