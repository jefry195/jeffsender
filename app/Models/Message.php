<?php

namespace App\Models;

use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory, HasModule;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'module',
        'owner_id',
        'platform_id',
        'customer_id',
        'conversation_id',
        'uuid',
        'direction',
        'type',
        'body',
        'status',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'body' => 'array',
        'meta' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['created_at_diff', 'created_at_time'];

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the created_at_diff
     *
     * @param  string  $value
     * @return string
     */
    public function getCreatedAtDiffAttribute($value)
    {
        return $this->created_at?->diffForHumans([
            'short' => true,
        ]);
    }

    public function getCreatedAtTimeAttribute($value)
    {
        return $this->created_at?->format('d M, y | h:i A');
    }

    public function scopeUnRead($query)
    {
        return $query->where('direction', 'in')->where('status', 'received');
    }

    /**
     * Get the message body.
     */
    public function getBody(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }

        return data_get($this->body, $key);
    }

    /**
     * Get the text content of the message.
     *
     * If the message type is "text", this will return the body of the message.
     * Otherwise, this will return the type of the message.
     */
    public function getText(): ?string
    {
        return $this->getBody('body.text') ?? $this->getBody('body.body') ?? $this->getBody('text') ?? $this->getBody('body');
    }

    /**
     * Determine if the message can be auto replied.
     */
    public function canAutoReply(): bool
    {
        $isTextMessage = $this->type === 'text' || $this->type === 'button';
        $platformEnabledAutoReply = $this->platform?->isAutoReplyEnabled();
        $hasPlanAccess = validateUserPlan('auto_reply', true, $this->owner_id);

        return $isTextMessage && $platformEnabledAutoReply && $hasPlanAccess;
    }

    public function getMeta($key, $default = null)
    {
        return data_get($this->meta, $key, $default);
    }
}
