<?php

namespace NettSite\Messenger\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use NettSite\Messenger\Contracts\MessengerAuthenticatable;
use NettSite\Messenger\Database\Factories\MessengerUserFactory;
use NettSite\Messenger\Traits\HasMessenger;

class MessengerUser extends Authenticatable implements FilamentUser, MessengerAuthenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasMessenger;
    use HasUuids;

    protected $table = 'messenger_users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected static function newFactory(): MessengerUserFactory
    {
        return MessengerUserFactory::new();
    }
}
