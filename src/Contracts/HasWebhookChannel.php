<?php

namespace LBHurtado\ModelChannel\Contracts;

interface HasWebhookChannel
{
    public function getWebhookChannel(): ?string;

    public function setWebhookChannel(?string $webhook): static;

    public function hasWebhookChannel(): bool;
}
