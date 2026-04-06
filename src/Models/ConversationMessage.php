<?php

namespace NettSite\Messenger\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string $author_type
 * @property string $author_id
 * @property string $body
 * @property Carbon|null $read_at
 */
class ConversationMessage extends Model
{
    use HasUuids;

    protected $table = 'messenger_conversation_messages';

    protected $fillable = [
        'conversation_id',
        'author_type',
        'author_id',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }
}
