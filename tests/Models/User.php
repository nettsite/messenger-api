<?php

namespace NettSite\Messenger\Tests\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use NettSite\Messenger\Contracts\MessengerAuthenticatable;
use NettSite\Messenger\Tests\Factories\UserFactory;
use NettSite\Messenger\Traits\HasMessenger;

class User extends Authenticatable implements MessengerAuthenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasMessenger;
    use HasUuids;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
