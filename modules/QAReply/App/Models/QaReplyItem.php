<?php

namespace Modules\QAReply\App\Models;

use App\Models\User;
use App\Models\Template;
use App\Traits\HasOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QaReplyItem extends Model
{
    use HasFactory, HasOwner;

    protected $fillable = [
        'qa_reply_id',
        'owner_id',
        'template_id',
        'key',
        'type',
        'value',
    ];

    public function qa_reply(): BelongsTo
    {
        return $this->belongsTo(QaReply::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
