<?php

namespace LBHurtado\ModelChannel\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use LBHurtado\ModelChannel\Enums\Channel;

interface Channelable
{
    public function channels(): MorphMany;

    public function getChannel(string|Channel $name): ?string;

    public function setChannel(string|Channel $name, ?string $value): static;

    public function forceSetChannel(string|Channel $name, string $value): static;

    public function hasChannel(string|Channel $name): bool;

    public function isValidChannel(string|Channel $name, ?string $value = null): bool;
}
