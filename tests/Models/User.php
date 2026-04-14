<?php

declare(strict_types=1);

namespace LBHurtado\ModelChannel\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LBHurtado\ModelChannel\Contracts\ChannelsInterface;
use LBHurtado\ModelChannel\Contracts\HasMobileChannel;
use LBHurtado\ModelChannel\Contracts\HasWebhookChannel;
use LBHurtado\ModelChannel\Database\Factories\UserFactory;
use LBHurtado\ModelChannel\Traits\HasChannels;

class User extends Authenticatable implements ChannelsInterface, HasMobileChannel, HasWebhookChannel
{
    use HasChannels;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
