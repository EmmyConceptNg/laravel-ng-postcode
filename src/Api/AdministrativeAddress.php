<?php

namespace EmmanuelKolawole\NgPostcode\Api;

final class AdministrativeAddress
{
    public function __construct(
        public readonly ?string $stateName,
        public readonly ?string $lgaName,
        public readonly ?string $localityName,
        public readonly ?string $zone,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            stateName: $data['state_name'] ?? null,
            lgaName: $data['lga_name'] ?? null,
            localityName: $data['locality_name'] ?? null,
            zone: $data['zone'] ?? null,
        );
    }
}
