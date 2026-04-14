<?php

use LBHurtado\ModelChannel\Contracts\HasMobileChannel;
use LBHurtado\ModelChannel\Contracts\HasWebhookChannel;
use LBHurtado\ModelChannel\Enums\Channel;
use LBHurtado\ModelChannel\Tests\Models\User;

it('implements the capability contracts', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(HasMobileChannel::class)
        ->and($user)->toBeInstanceOf(HasWebhookChannel::class);
});

it('can set and get a channel explicitly', function () {
    $user = User::factory()->create();

    $user->setChannel(Channel::WEBHOOK, 'https://example.com/webhook');

    expect($user->getChannel(Channel::WEBHOOK))->toBe('https://example.com/webhook')
        ->and($user->hasChannel(Channel::WEBHOOK))->toBeTrue();
});

it('can delete a channel by setting null', function () {
    $user = User::factory()->create();

    $user->setChannel(Channel::WEBHOOK, 'https://example.com/webhook');
    $user->setChannel(Channel::WEBHOOK, null);

    expect($user->getChannel(Channel::WEBHOOK))->toBeNull()
        ->and($user->hasChannel(Channel::WEBHOOK))->toBeFalse()
        ->and($user->channels()->where('name', Channel::WEBHOOK->value)->exists())->toBeFalse();
});

it('can detect channel presence', function () {
    $user = User::factory()->create();

    expect($user->hasChannel(Channel::TELEGRAM))->toBeFalse();

    $user->setChannel(Channel::TELEGRAM, 'sample-telegram-id');

    expect($user->hasChannel(Channel::TELEGRAM))->toBeTrue();
});

it('magic property still works and stays in sync with explicit api', function () {
    $user = User::factory()->create();

    $user->mobile = '09171234567';

    expect($user->mobile)->toBe('639171234567')
        ->and($user->getMobileChannel())->toBe('639171234567');

    $user->setWebhookChannel('https://example.com/webhook');

    expect($user->webhook)->toBe('https://example.com/webhook');
});

it('can set mobile via explicit helper', function () {
    $user = User::factory()->create();

    $user->setMobileChannel('09173011987');

    expect($user->getMobileChannel())->toBe('639173011987');
});

dataset('mobile_formats', [
    ['09173011987', '639173011987'],
    ['9173011987', '639173011987'],
    ['+639173011987', '639173011987'],
]);

it('normalizes mobile to e164 without plus', function (string $input, string $expected) {
    $user = User::factory()->create();

    $user->setMobileChannel($input);

    expect($user->getMobileChannel())->toBe($expected);
})->with('mobile_formats');

it('hasMobileChannel works', function () {
    $user = User::factory()->create();

    expect($user->hasMobileChannel())->toBeFalse();

    $user->setMobileChannel('09173011987');

    expect($user->hasMobileChannel())->toBeTrue();
});

it('findByMobile works across multiple formats', function () {
    $user = User::factory()->create();
    $user->setMobileChannel('09173011987');

    expect(User::findByMobile('+639173011987')?->id)->toBe($user->id)
        ->and(User::findByMobile('9173011987')?->id)->toBe($user->id);
});

it('can delete mobile by setting null through helper', function () {
    $user = User::factory()->create();
    $user->setMobileChannel('09173011987');

    $user->setMobileChannel(null);

    expect($user->getMobileChannel())->toBeNull()
        ->and($user->hasMobileChannel())->toBeFalse();
});

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

it('findByWebhook works', function () {
    $user = User::factory()->create();
    $user->setWebhookChannel('https://example.com/webhook');

    expect(User::findByWebhook('https://example.com/webhook')?->id)->toBe($user->id);
});

it('throws for invalid channel names', function () {
    $user = User::factory()->create();

    expect(fn () => $user->setChannel('email', 'test@example.com'))
        ->toThrow(Exception::class, 'Channel [email] is not valid.');
});

it('does not add duplicate channel records for the same name-value pair', function () {
    $user = User::factory()->create();

    $user->setMobileChannel('09173011987');
    $user->setMobileChannel('09173011987');

    expect($user->channels()->where('name', Channel::MOBILE->value)->count())->toBe(1);
});

it('updates the value if the channel value changes', function () {
    $user = User::factory()->create();

    $user->setMobileChannel('09173011987');
    $user->setMobileChannel('09181234567');

    expect($user->channels()->where('name', Channel::MOBILE->value)->count())->toBe(1)
        ->and($user->getMobileChannel())->toBe('639181234567');
});

it('preserves other channel values when updating a specific one', function () {
    $user = User::factory()->create();

    $user->setMobileChannel('09173011987');
    $user->setWebhookChannel('https://example.com/webhook');
    $user->setMobileChannel('09181234567');

    expect($user->channels()->count())->toBe(2)
        ->and($user->getMobileChannel())->toBe('639181234567')
        ->and($user->getWebhookChannel())->toBe('https://example.com/webhook');
});
