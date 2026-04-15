# laravel-model-channel

A schema-light Laravel capability package for attaching delivery and communication channels to Eloquent models through a polymorphic `channels` table.

## Why this package exists

This package lets a model expose capabilities like mobile or webhook delivery without requiring host apps to add direct columns such as `users.mobile` or `users.webhook`.

Channels are stored in the package-owned morph table, keeping your application schema clean and flexible.

---

## Installation

```bash
composer require 3neti/laravel-model-channel
php artisan migrate
```

---

## Supported Channel Types

Out of the box, the package supports:

- `mobile` (normalized to E.164 without `+`)
- `webhook` (validated URL)
- `telegram` (numeric ID)
- `whatsapp` (phone-based)
- `viber` (phone-based)

All channels are validated via enum-backed rules.

---

## Basic usage

```php
use Illuminate\Database\Eloquent\Model;
use LBHurtado\ModelChannel\Contracts\HasMobileChannel;
use LBHurtado\ModelChannel\Contracts\HasWebhookChannel;
use LBHurtado\ModelChannel\Traits\HasChannels;

class User extends Model implements HasMobileChannel, HasWebhookChannel
{
    use HasChannels;
}
```

---

## Explicit capability API

### Mobile

```php
$user->setMobileChannel('09171234567');

$user->getMobileChannel(); // 639171234567
$user->hasMobileChannel(); // true
```

Accepted formats:

```php
$user->setMobileChannel('09171234567');
$user->setMobileChannel('0917 123 4567');
$user->setMobileChannel('639171234567');
$user->setMobileChannel('+639171234567');
$user->setMobileChannel('+63 (917) 123-4567');
```

All normalize to:

```php
639171234567
```

---

### Webhook

```php
$user->setWebhookChannel('https://example.com/webhook');

$user->getWebhookChannel(); // https://example.com/webhook
$user->hasWebhookChannel(); // true
```

---

## Generic API

```php
$user->setChannel('webhook', 'https://example.com/webhook');

$user->getChannel('webhook');
$user->hasChannel('webhook');
```

Using enum:

```php
use LBHurtado\ModelChannel\Enums\Channel;

$user->setChannel(Channel::WEBHOOK, 'https://example.com/webhook');
```

Delete a channel:

```php
$user->setChannel(Channel::WEBHOOK, null);
$user->setChannel(Channel::WEBHOOK, '');
```

---

## Magic properties (backward compatibility)

```php
$user->mobile = '09171234567';

$user->mobile;              // 639171234567
$user->getMobileChannel();  // 639171234567
```

---

## Additional helpers

### Telegram

```php
$user->setTelegramChannel('123456789');
$user->getTelegramChannel();
$user->hasTelegramChannel();
```

### WhatsApp

```php
$user->setWhatsappChannel('09171234567');
$user->getWhatsappChannel();
$user->hasWhatsappChannel();
```

### Viber

```php
$user->setViberChannel('09171234567');
$user->getViberChannel();
$user->hasViberChannel();
```

---

## Finders

```php
User::findByMobile('09171234567');
User::findByWebhook('https://example.com/webhook');
User::findByChannel('telegram', '123456789');
```

Mobile finder supports multiple formats.

---

## Performance & Caching

### Database Indexing (Recommended)

For optimal performance, especially for `findByMobile()` and `findByChannel()`, add indexes:

```php
$table->index(['name', 'value']);
$table->index(['model_type', 'model_id', 'name']);
```

---

### Lookup Caching (Optional)

Caching is designed for **finder methods only**, not accessor reads.

Supported cached operations:

- `findByMobile()`
- `findByWebhook()`
- `findByChannel()` (for configured channels)

Example config:

```php
'cache' => [
    'enabled' => true,
    'ttl' => 600,
    'channels' => ['mobile', 'webhook'],
]
```

---

### Cache Behavior

- caches **model IDs only** (not full models)
- caches **misses** to prevent repeated DB queries
- automatically **invalidates on update or delete**
- does **NOT cache accessor methods** like `getMobileChannel()`

---

## External package integration

```php
use LBHurtado\ModelChannel\Contracts\HasMobileChannel;

function sendOtp(HasMobileChannel $user)
{
    $mobile = $user->getMobileChannel();
}
```

---

## Storage model

No schema changes required.

All data is stored in the polymorphic `channels` table.
