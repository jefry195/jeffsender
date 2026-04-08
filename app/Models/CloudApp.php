<?php

namespace App\Models;

use App\Models\User;
use App\Traits\UUID;
use App\Models\Platform;
use App\Traits\HasActivityLog;
use App\Traits\HasFilter;
use App\Traits\HasModule;
use App\Traits\HasOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CloudApp extends Model
{
    use HasFactory, UUID, HasFilter, HasActivityLog, HasModule, HasOwner;

    protected $fillable = ['module', 'platform_id', 'name', 'site_link', 'owner_id', 'uuid', 'key'];

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function logs()
    {
        return $this->hasMany(CloudAppLog::class, 'app_id');
    }
}
