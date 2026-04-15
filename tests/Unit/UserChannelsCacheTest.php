<?php

use Illuminate\Support\Facades\Cache;
use LBHurtado\ModelChannel\Enums\Channel;
use LBHurtado\ModelChannel\Tests\Models\User;

beforeEach(function () {
    config()->set('model-channel.cache.enabled', true);
    config()->set('model-channel.cache.store', 'array');
    config()->set('model-channel.cache.ttl', 600);
    config()->set('model-channel.cache.prefix', 'model-channel-test');
    config()->set('model-channel.cache.channels', ['mobile', 'webhook']);
    config()->set('model-channel.cache.null_marker', '__model_channel_null__');

    Cache::store('array')->flush();
});

it('caches mobile lookup results by model id', function () {
    $user = User::factory()->create();
    $user->setMobileChannel('09171234567');

    $found = User::findByMobile('09171234567');

    expect($found)->not->toBeNull()
        ->and($found->is($user))->toBeTrue();

    $key = implode(':', [
        'model-channel-test',
        'lookup',
        str_replace('\\', '.', User::class),
        Channel::MOBILE->value,
        sha1('639171234567'),
    ]);

    expect(Cache::store('array')->get($key))->toBe($user->getKey());
});

it('caches lookup misses', function () {
    $found = User::findByMobile('09171234567');

    expect($found)->toBeNull();

    $key = implode(':', [
        'model-channel-test',
        'lookup',
        str_replace('\\', '.', User::class),
        Channel::MOBILE->value,
        sha1('639171234567'),
    ]);

    expect(Cache::store('array')->get($key))->toBe('__model_channel_null__');
});

it('invalidates cached mobile lookup when the mobile changes', function () {
    $user = User::factory()->create();
    $user->setMobileChannel('09171234567');

    User::findByMobile('09171234567');

    $oldKey = implode(':', [
        'model-channel-test',
        'lookup',
        str_replace('\\', '.', User::class),
        Channel::MOBILE->value,
        sha1('639171234567'),
    ]);

    expect(Cache::store('array')->get($oldKey))->toBe($user->getKey());

    $user->setMobileChannel('09181234567');

    expect(Cache::store('array')->get($oldKey))->toBeNull()
        ->and(User::findByMobile('09171234567'))->toBeNull()
        ->and(User::findByMobile('09181234567')?->is($user))->toBeTrue();
});

it('invalidates cached mobile lookup when the mobile is deleted', function () {
    $user = User::factory()->create();
    $user->setMobileChannel('09171234567');

    User::findByMobile('09171234567');

    $key = implode(':', [
        'model-channel-test',
        'lookup',
        str_replace('\\', '.', User::class),
        Channel::MOBILE->value,
        sha1('639171234567'),
    ]);

    expect(Cache::store('array')->get($key))->toBe($user->getKey());

    $user->setMobileChannel(null);

    expect(Cache::store('array')->get($key))->toBeNull()
        ->and(User::findByMobile('09171234567'))->toBeNull();
});

it('does not use lookup cache when caching is disabled', function () {
    config()->set('model-channel.cache.enabled', false);

    $user = User::factory()->create();
    $user->setWebhookChannel('https://example.com/webhook');

    $found = User::findByWebhook('https://example.com/webhook');

    expect($found)->not->toBeNull()
        ->and($found->is($user))->toBeTrue();

    $key = implode(':', [
        'model-channel-test',
        'lookup',
        str_replace('\\', '.', User::class),
        Channel::WEBHOOK->value,
        sha1('https://example.com/webhook'),
    ]);

    expect(Cache::store('array')->get($key))->toBeNull();
});