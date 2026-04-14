# laravel-model-channel

A schema-light Laravel capability package for attaching delivery and communication channels to Eloquent models through a polymorphic `channels` table.

## Why this package exists

This package lets a model expose capabilities like mobile or webhook delivery without requiring host apps to add columns such as `users.mobile`.

Channels are stored in the package-owned morph table.

## Installation

```bash
composer require 3neti/laravel-model-channel
php artisan migrate
```

## Basic usage

```php
use LBHurtado\ModelChannel\Contracts\HasMobileChannel;
use LBHurtado\ModelChannel\Contracts\HasWebhookChannel;
use LBHurtado\ModelChannel\Traits\HasChannels;

class User extends Model implements HasMobileChannel, HasWebhookChannel
{
    use HasChannels;
}
```

## Explicit API

```php
$user->setMobileChannel('09173011987');
$user->getMobileChannel();
$user->hasMobileChannel();

$user->setWebhookChannel('https://example.com/webhook');
$user->getWebhookChannel();
$user->hasWebhookChannel();
```

## Generic API

```php
$user->setChannel('telegram', 'my-telegram-id');
$user->getChannel('telegram');
$user->hasChannel('telegram');
```

## Magic properties

Magic access is still supported for backward compatibility.

```php
$user->mobile = '09173011987';
$user->mobile; // 639173011987

$user->webhook = 'https://example.com/webhook';
$user->webhook;
```

## Finders

```php
User::findByMobile('09173011987');
User::findByWebhook('https://example.com/webhook');
User::findByChannel('telegram', 'my-telegram-id');
```

## External package integration

External packages can typehint a capability contract instead of assuming a host column exists.

```php
use LBHurtado\ModelChannel\Contracts\HasMobileChannel;

function sendOtp(HasMobileChannel $user): void
{
    $mobile = $user->getMobileChannel();

    // ...
}
```

## Storage model

This package does not require direct schema changes on your host model tables.
All channel data is stored in the package's polymorphic `channels` table.
