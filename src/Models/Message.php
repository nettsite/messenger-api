<?php

namespace NettSite\Messenger\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use NettSite\Messenger\Database\Factories\MessageFactory;

/**
 * @property string $id
 * @property string $body
 * @property string|null $url
 * @property string|null $sender_type
 * @property string|null $sender_id
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $sent_at
 */
class Message extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'messenger_messages';

    protected $fillable = [
        'body',
        'url',
        'sender_type',
        'sender_id',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected static function newFactory(): MessageFactory
    {
        return MessageFactory::new();
    }

    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<MessageRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(MessageRecipient::class);
    }

    /** @return HasMany<MessageReceipt, $this> */
    public function receipts(): HasMany
    {
        return $this->hasMany(MessageReceipt::class);
    }

    /** @return HasMany<Reply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }

    public function readCount(): int
    {
        return $this->receipts()->whereNotNull('read_at')->count();
    }

    public function recipientCount(): int
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('messenger.user_model') ?? MessengerUser::class;

        $count = 0;

        foreach ($this->recipients as $recipient) {
            $count += match ($recipient->recipient_type) {
                'all' => $userModel::count(),
                'user' => 1,
                'group' => DB::table('messenger_group_users')
                    ->where('group_id', $recipient->recipient_id)
                    ->count(),
                default => 0,
            };
        }

        return $count;
    }
}
