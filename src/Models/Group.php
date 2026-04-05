<?php

namespace NettSite\Messenger\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use NettSite\Messenger\Database\Factories\GroupFactory;

class Group extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'messenger_groups';

    protected $fillable = [
        'name',
    ];

    protected static function newFactory(): GroupFactory
    {
        return GroupFactory::new();
    }

    public function users(): MorphToMany
    {
        /** @var class-string $userModel */
        $userModel = config('messenger.user_model');

        return $this->morphedByMany($userModel, 'user', 'messenger_group_users');
    }
}
