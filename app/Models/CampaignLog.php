<?php

namespace App\Models;

use App\Traits\HasModule;
use App\Traits\HasOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;

class CampaignLog extends Model
{
    use HasFactory, HasModule, HasOwner, Prunable;

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(7));
    }

    protected $casts = [
        'meta' => 'array',
    ];

    protected $guarded = [];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isSuccess(): bool
    {
        return boolval($this->send_at);
    }
}
