<?php

namespace NettSite\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use NettSite\Messenger\Database\Factories\DeviceTokenFactory;

/**
 * @property string $id
 * @property string $user_type
 * @property string $user_id
 * @property string $token
 * @property string $platform
 * @property Carbon|null $last_seen_at
 */
class DeviceToken extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'messenger_device_tokens';

    protected $fillable = [
        'user_type',
        'user_id',
        'token',
        'platform',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    protected static function newFactory(): DeviceTokenFactory
    {
        return DeviceTokenFactory::new();
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /** @param Builder<DeviceToken> $query */
    public function scopeForUser(Builder $query, string $userType, string $userId): void
    {
        $query->where('user_type', $userType)->where('user_id', $userId);
    }
}
