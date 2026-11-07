<?php

namespace EmmanuelKolawole\NgPostcode\Api;

final class LookupResult
{
    public function __construct(
        public readonly string $postcode,
        public readonly bool $valid,
        public readonly ?string $status,
        public readonly bool $verified,
        public readonly ?AdministrativeAddress $administrativeAddress,
        public readonly ?string $recentHouseAddress,
        public readonly ?string $buildingUseStatus,
        public readonly array $raw,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            postcode: $data['postcode'] ?? '',
            valid: $data['valid'] ?? false,
            status: $data['status'] ?? null,
            verified: $data['verified'] ?? false,
            administrativeAddress: is_array($data['administrative_address'] ?? null) ? AdministrativeAddress::fromArray($data['administrative_address']) : null,
            recentHouseAddress: $data['recent_house_address']['recent'] ?? null,
            buildingUseStatus: $data['building_use_status'] ?? null,
            raw: $data,
        );
    }
}
