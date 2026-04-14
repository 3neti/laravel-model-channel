<?php

namespace LBHurtado\ModelChannel\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use LBHurtado\ModelChannel\Enums\Channel;

trait HasChannels
{
    public function channels(): MorphMany
    {
        return $this->morphMany($this->getChannelModelClassName(), 'model', 'model_type', $this->getModelKeyColumnName())
            ->latest('id');
    }

    public function getChannel(string|Channel $name): ?string
    {
        $name = $this->normalizeChannelName($name);

        return $this->getChannelAttribute($name);
    }

    public function setChannel(string|Channel $name, ?string $value): static
    {
        $name = $this->normalizeChannelName($name);

        if (is_null($value) || $value === '') {
            $this->deleteChannel($name);

            return $this;
        }

        if (! $this->isValidChannel($name, $value)) {
            throw new Exception(sprintf('Channel [%s] is not valid.', $name));
        }

        return $this->forceSetChannel($name, $value);
    }

    public function forceSetChannel(string|Channel $name, string $value): static
    {
        $name = $this->normalizeChannelName($name);
        $value = $this->normalizeChannelValue($name, $value);

        $existing = $this->channels()->where('name', $name)->latest()->first();

        if ($existing && $existing->value === $value) {
            return $this;
        }

        $this->channels()->where('name', $name)->delete();

        $this->channels()->create([
            'name' => $name,
            'value' => $value,
        ]);

        if ($this->relationLoaded('channels')) {
            $this->unsetRelation('channels');
        }

        return $this;
    }

    public function hasChannel(string|Channel $name): bool
    {
        return ! is_null($this->getChannel($name));
    }

    protected function deleteChannel(string|Channel $name): void
    {
        $name = $this->normalizeChannelName($name);

        $this->channels()->where('name', $name)->delete();

        if ($this->relationLoaded('channels')) {
            $this->unsetRelation('channels');
        }
    }

    public function isValidChannel(string|Channel $name, ?string $value = null): bool
    {
        $channel = $name instanceof Channel ? $name : Channel::tryFrom($name);

        if (! $channel instanceof Channel) {
            return false;
        }

        $validator = Validator::make(['value' => $value], ['value' => $channel->rules()]);

        return ! $validator->fails();
    }

    public function getMobileChannel(): ?string
    {
        return $this->getChannel(Channel::MOBILE);
    }

    public function setMobileChannel(?string $mobile): static
    {
        return $this->setChannel(Channel::MOBILE, $mobile);
    }

    public function hasMobileChannel(): bool
    {
        return $this->hasChannel(Channel::MOBILE);
    }

    public function getWebhookChannel(): ?string
    {
        return $this->getChannel(Channel::WEBHOOK);
    }

    public function setWebhookChannel(?string $webhook): static
    {
        return $this->setChannel(Channel::WEBHOOK, $webhook);
    }

    public function hasWebhookChannel(): bool
    {
        return $this->hasChannel(Channel::WEBHOOK);
    }

    public function getTelegramChannel(): ?string
    {
        return $this->getChannel(Channel::TELEGRAM);
    }

    public function setTelegramChannel(?string $telegram): static
    {
        return $this->setChannel(Channel::TELEGRAM, $telegram);
    }

    public function hasTelegramChannel(): bool
    {
        return $this->hasChannel(Channel::TELEGRAM);
    }

    public function getWhatsappChannel(): ?string
    {
        return $this->getChannel(Channel::WHATSAPP);
    }

    public function setWhatsappChannel(?string $whatsapp): static
    {
        return $this->setChannel(Channel::WHATSAPP, $whatsapp);
    }

    public function hasWhatsappChannel(): bool
    {
        return $this->hasChannel(Channel::WHATSAPP);
    }

    public function getViberChannel(): ?string
    {
        return $this->getChannel(Channel::VIBER);
    }

    public function setViberChannel(?string $viber): static
    {
        return $this->setChannel(Channel::VIBER, $viber);
    }

    public function hasViberChannel(): bool
    {
        return $this->hasChannel(Channel::VIBER);
    }

    protected function getChannelTableName(): string
    {
        $modelClass = $this->getChannelModelClassName();

        return (new $modelClass)->getTable();
    }

    protected function getModelKeyColumnName(): string
    {
        return config('model-channel.model_primary_key_attribute') ?? 'model_id';
    }

    protected function getChannelModelClassName(): string
    {
        return config('model-channel.channel_model') ?? \LBHurtado\ModelChannel\Models\Channel::class;
    }

    public function __get($key)
    {
        if ($this->isExcludedChannel($key)) {
            return parent::__get($key);
        }

        if ($channel = $this->getChannelFromEnum($key)) {
            return $this->getChannel($channel);
        }

        return parent::__get($key);
    }

    public function __set($key, $value)
    {
        if ($this->isExcludedChannel($key)) {
            parent::__set($key, $value);

            return;
        }

        if ($channel = $this->getChannelFromEnum($key)) {
            $this->setChannel($channel, $value);

            return;
        }

        parent::__set($key, $value);
    }

    protected function isExcludedChannel(string $key): bool
    {
        return property_exists($this, 'excludedChannels')
            && in_array($key, $this->excludedChannels, true);
    }

    protected function getChannelAttribute(string $name): ?string
    {
        $channel = $this->relationLoaded('channels')
            ? $this->channels->firstWhere('name', $name)
            : $this->channels()->where('name', $name)->first();

        return $channel?->value;
    }

    protected function setChannelAttribute(string $name, ?string $value): void
    {
        $this->setChannel($name, $value);
    }

    private function getChannelFromEnum(string $key): ?Channel
    {
        foreach (Channel::cases() as $channel) {
            if ($channel->value === $key) {
                return $channel;
            }
        }

        return null;
    }

    protected function normalizeChannelName(string|Channel $name): string
    {
        return $name instanceof Channel ? $name->value : $name;
    }

    protected function normalizeChannelValue(string $name, string $value): string
    {
        if ($name === Channel::MOBILE->value) {
            return ltrim(phone($value, 'PH')->formatE164(), '+');
        }

        return $value;
    }

    public static function findByChannel(string|Channel $channelName, string $channelValue): ?static
    {
        $channelName = $channelName instanceof Channel ? $channelName->value : $channelName;

        return static::whereHas('channels', function (Builder $q) use ($channelName, $channelValue) {
            $q->where('name', $channelName);

            if ($channelName === Channel::MOBILE->value) {
                try {
                    $e164 = ltrim(phone($channelValue, 'PH')->formatE164(), '+');
                    $national = preg_replace('/\D+/', '', $channelValue);
                    $dialable = phone($channelValue, 'PH')->formatForMobileDialingInCountry('PH');
                    $dialable = preg_replace('/\D+/', '', $dialable);

                    $q->where(function ($sub) use ($e164, $national, $dialable) {
                        $sub->where('value', $e164)
                            ->orWhere('value', 'LIKE', "%{$national}%")
                            ->orWhere('value', 'LIKE', "%{$dialable}%");
                    });
                } catch (\Throwable $e) {
                    $q->whereRaw('0 = 1');
                }
            } else {
                $q->where('value', $channelValue);
            }
        })->first();
    }

    public static function findByMobile(string $mobile): ?static
    {
        return static::findByChannel(Channel::MOBILE, $mobile);
    }

    public static function findByWebhook(string $webhook): ?static
    {
        return static::findByChannel(Channel::WEBHOOK, $webhook);
    }

    public static function __callStatic($method, $parameters)
    {
        if (str_starts_with($method, 'findBy')) {
            $channelName = strtolower(str_replace('findBy', '', $method));

            if (! isset($parameters[0])) {
                throw new InvalidArgumentException('Channel value is required');
            }

            return static::findByChannel($channelName, $parameters[0]);
        }

        return parent::__callStatic($method, $parameters);
    }
}
