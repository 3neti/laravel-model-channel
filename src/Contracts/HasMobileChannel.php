<?php

namespace LBHurtado\ModelChannel\Contracts;

interface HasMobileChannel
{
    public function getMobileChannel(): ?string;

    public function setMobileChannel(?string $mobile): static;

    public function hasMobileChannel(): bool;
}
