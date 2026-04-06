<?php

namespace NettSite\Messenger\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Conversation extends Model
{
    use HasUuids;

    protected $table = 'messenger_conversations';

    protected $fillable = [
        'message_id',
        'user_type',
        'user_id',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /** @return HasMany<ConversationMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
