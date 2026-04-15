<?php

use LBHurtado\ModelChannel\Contracts\HasMobileChannel;
use LBHurtado\ModelChannel\Contracts\HasWebhookChannel;
use LBHurtado\ModelChannel\Enums\Channel;
use LBHurtado\ModelChannel\Tests\Models\User;

dataset('normalized_mobile_inputs', [
    'local format' => ['09171234567', '639171234567'],
    'spaced local format' => ['0917 123 4567', '639171234567'],
    'international no plus' => ['639171234567', '639171234567'],
    'international with plus' => ['+639171234567', '639171234567'],
    'formatted international' => ['+63 (917) 123-4567', '639171234567'],
]);

dataset('mobile_finder_inputs', [
    ['9171234567'],
    ['09171234567'],
    ['0917 123 4567'],
    ['639171234567'],
    ['+639171234567'],
    ['+63 (917) 123-4567'],
]);

/*
|--------------------------------------------------------------------------
| Capability contracts
|--------------------------------------------------------------------------
*/

it('implements the capability contracts', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(HasMobileChannel::class)
        ->and($user)->toBeInstanceOf(HasWebhookChannel::class);
});

/*
|--------------------------------------------------------------------------
| Generic capability API
|--------------------------------------------------------------------------
*/

it('can set and get a channel explicitly using a string', function () {
    $user = User::factory()->create();

    $user->setChannel('webhook', 'https://example.com/webhook');

    expect($user->getChannel('webhook'))->toBe('https://example.com/webhook')
        ->and($user->hasChannel('webhook'))->toBeTrue();
});

it('can set and get a channel explicitly using enum', function () {
    $user = User::factory()->create();

    $user->setChannel(Channel::WEBHOOK, 'https://example.com/webhook');

    expect($user->getChannel(Channel::WEBHOOK))->toBe('https://example.com/webhook')
        ->and($user->hasChannel(Channel::WEBHOOK))->toBeTrue();
});

it('can delete a channel by setting null', function () {
    $user = User::factory()->create();

    $user->setChannel(Channel::WEBHOOK, 'https://example.com/webhook');
    expect($user->hasChannel(Channel::WEBHOOK))->toBeTrue();

    $user->setChannel(Channel::WEBHOOK, null);

    expect($user->getChannel(Channel::WEBHOOK))->toBeNull()
        ->and($user->hasChannel(Channel::WEBHOOK))->toBeFalse()
        ->and($user->channels()->where('name', Channel::WEBHOOK->value)->exists())->toBeFalse();
});

it('can delete a channel by setting empty string', function () {
    $user = User::factory()->create();

    $user->setChannel(Channel::WEBHOOK, 'https://example.com/webhook');
    expect($user->hasChannel(Channel::WEBHOOK))->toBeTrue();

    $user->setChannel(Channel::WEBHOOK, '');

    expect($user->getChannel(Channel::WEBHOOK))->toBeNull()
        ->and($user->hasChannel(Channel::WEBHOOK))->toBeFalse()
        ->and($user->channels()->where('name', Channel::WEBHOOK->value)->exists())->toBeFalse();
});

it('can detect generic channel presence', function () {
    $user = User::factory()->create();

    expect($user->hasChannel(Channel::TELEGRAM))->toBeFalse();

    $user->setChannel(Channel::TELEGRAM, '123456789');

    expect($user->hasChannel(Channel::TELEGRAM))->toBeTrue();
});

it('magic property still works and stays in sync with explicit api', function () {
    $user = User::factory()->create();

    $user->mobile = '09171234567';

    expect($user->mobile)->toBe('639171234567')
        ->and($user->getMobileChannel())->toBe('639171234567')
        ->and($user->hasMobileChannel())->toBeTrue();

    $user->setWebhookChannel('https://example.com/webhook');

    expect($user->webhook)->toBe('https://example.com/webhook')
        ->and($user->getWebhookChannel())->toBe('https://example.com/webhook');
});

it('keeps explicit api in sync with magic property reads', function () {
    $user = User::factory()->create();

    $user->setWebhookChannel('https://example.com/webhook');

    expect($user->webhook)->toBe('https://example.com/webhook')
        ->and($user->getWebhookChannel())->toBe('https://example.com/webhook');
});

it('does not add duplicate channel records for the same name-value pair', function () {
    $user = User::factory()->create();

    $user->setWebhookChannel('https://example.com/webhook');
    $user->setWebhookChannel('https://example.com/webhook');

    expect($user->channels()->where('name', Channel::WEBHOOK->value)->count())->toBe(1);
});

it('updates a channel value without affecting other channels', function () {
    $user = User::factory()->create();

    $user->setMobileChannel('09171234567');
    $user->setWebhookChannel('https://example.com/one');

    $user->setWebhookChannel('https://example.com/two');

    expect($user->getMobileChannel())->toBe('639171234567')
        ->and($user->getWebhookChannel())->toBe('https://example.com/two')
        ->and($user->channels()->count())->toBe(2);
});

it('works with preloaded channels using explicit helpers', function () {
    $user = User::factory()->create();
    $user->setMobileChannel('09171234567');
    $user->setWebhookChannel('https://example.com/webhook');

    $fresh = User::with('channels')->findOrFail($user->id);

    expect($fresh->getMobileChannel())->toBe('639171234567')
        ->and($fresh->getWebhookChannel())->toBe('https://example.com/webhook')
        ->and($fresh->hasMobileChannel())->toBeTrue()
        ->and($fresh->hasWebhookChannel())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Mobile capability
|--------------------------------------------------------------------------
*/

it('can set mobile via explicit helper', function () {
    $user = User::factory()->create();

    $user->setMobileChannel('09171234567');

    expect($user->getMobileChannel())->toBe('639171234567');
});

it('normalizes mobile to e164 without plus', function (string $input, string $expected) {
    $user = User::factory()->create();

    $user->setMobileChannel($input);

    expect($user->getMobileChannel())->toBe($expected);
})->with('normalized_mobile_inputs');

it('hasMobileChannel works', function () {
    $user = User::factory()->create();

    expect($user->hasMobileChannel())->toBeFalse();

    $user->setMobileChannel('09171234567');

    expect($user->hasMobileChannel())->toBeTrue();
});

it('can remove mobile channel via explicit helper', function () {
    $user = User::factory()->create();

    $user->setMobileChannel('09171234567');
    expect($user->hasMobileChannel())->toBeTrue();

    $user->setMobileChannel(null);

    expect($user->getMobileChannel())->toBeNull()
        ->and($user->hasMobileChannel())->toBeFalse();
});

it('can find a user by mobile using explicit finder', function (string $input) {
    $user = User::factory()->create();
    $user->setMobileChannel('09171234567');

    $found = User::findByMobile($input);

    expect($found)->not->toBeNull()
        ->and($found->is($user))->toBeTrue();
})->with('mobile_finder_inputs');

/*
|--------------------------------------------------------------------------
| Webhook capability
|--------------------------------------------------------------------------
*/

it('can set webhook via explicit helper', function () {
    $user = User::factory()->create();

    $user->setWebhookChannel('https://example.com/webhook');

    expect($user->getWebhookChannel())->toBe('https://example.com/webhook');
});

it('webhook validation works', function () {
    $user = User::factory()->create();

    expect($user->isValidChannel(Channel::WEBHOOK, 'https://example.com/webhook'))->toBeTrue()
        ->and($user->isValidChannel(Channel::WEBHOOK, 'not-a-url'))->toBeFalse();
});

it('hasWebhookChannel works', function () {
    $user = User::factory()->create();

    expect($user->hasWebhookChannel())->toBeFalse();

    $user->setWebhookChannel('https://example.com/webhook');

    expect($user->hasWebhookChannel())->toBeTrue();
});

it('can find a user by webhook', function () {
    $user = User::factory()->create();
    $user->setWebhookChannel('https://example.com/webhook');

    $found = User::findByWebhook('https://example.com/webhook');

    expect($found)->not->toBeNull()
        ->and($found->is($user))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Helper methods for telegram / whatsapp / viber
|--------------------------------------------------------------------------
*/

it('supports telegram helper methods', function () {
    $user = User::factory()->create();

    $user->setTelegramChannel('123456789');

    expect($user->getTelegramChannel())->toBe('123456789')
        ->and($user->hasTelegramChannel())->toBeTrue();
});

it('supports whatsapp helper methods', function () {
    $user = User::factory()->create();

    $user->setWhatsappChannel('09171234567');

    expect($user->getWhatsappChannel())->not->toBeNull()
        ->and($user->hasWhatsappChannel())->toBeTrue();
});

it('supports viber helper methods', function () {
    $user = User::factory()->create();

    $user->setViberChannel('09171234567');

    expect($user->getViberChannel())->not->toBeNull()
        ->and($user->hasViberChannel())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Validation failures
|--------------------------------------------------------------------------
*/

it('throws for invalid channel names', function () {
    $user = User::factory()->create();

    expect(fn () => $user->setChannel('email', 'test@example.com'))
        ->toThrow(Exception::class, 'Channel [email] is not valid.');
});

it('rejects invalid webhook values', function () {
    $user = User::factory()->create();

    expect(fn () => $user->setWebhookChannel('not-a-valid-url'))
        ->toThrow(Exception::class, 'Channel [webhook] is not valid.');
});

it('rejects invalid telegram values', function () {
    $user = User::factory()->create();

    expect(fn () => $user->setChannel(Channel::TELEGRAM, 'invalid-telegram-id'))
        ->toThrow(Exception::class, 'Channel [telegram] is not valid.');
});