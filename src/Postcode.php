<?php

namespace EmmanuelKolawole\NgPostcode;

use InvalidArgumentException;

final class Postcode
{
    private function __construct(
        public readonly State $state,
        public readonly string $lga,
        public readonly string $district,
        public readonly string $area,
        public readonly string $building,
    ) {}

    public static function parse(string $input): self
    {

        $compact = strtoupper(preg_replace('/[\s-]+/', '', $input));


        if (strlen($compact) !== 11) {
            throw new InvalidArgumentException("Invalid postcode length. Expected 11 characters, got " . strlen($compact));
        }

        $state = substr($compact, 0, 2);
        $lga = substr($compact, 2, 2);
        $district = substr($compact, 4, 3);
        $area = substr($compact, 7, 2);
        $building = substr($compact, 9, 2);

        if (!preg_match('/^[A-Z]{2}$/', $state)) {
            throw new InvalidArgumentException("Invalid state segment: $state");
        }

        $stateEnum = State::tryFrom($state);
        if ($stateEnum === null) {
            throw new InvalidArgumentException("Unknown state code: $state");
        }


        if (!preg_match('/^(?:0[1-9]|[1-9][0-9])$/', $lga)) {
            throw new InvalidArgumentException("Invalid LGA segment: $lga");
        }
        if (!preg_match('/^[A-Z0-9]{3}$/', $district)) {
            throw new InvalidArgumentException("Invalid district segment: $district");
        }
        if (!preg_match('/^[A-Z]{2}$/', $area)) {
            throw new InvalidArgumentException("Invalid area segment: $area");
        }
        if (!preg_match('/^(?:0[1-9]|[1-9][0-9])$/', $building)) {
            throw new InvalidArgumentException("Invalid building segment: $building");
        }

        return new self($stateEnum, $lga, $district, $area, $building);
    }

    public static function tryParse(string $input): ?self
    {
        try {
            return self::parse($input);
        } catch (InvalidArgumentException) {

            return null;
        }
    }

    public function canonical(): string
    {
        return implode('-', $this->segments());
    }

    public function compact(): string
    {
        return implode('', $this->segments());
    }

    public function spaced(): string
    {
        return implode(' ', $this->segments());
    }

    /** @return list<string> */
    private function segments(): array
    {
        return [$this->state->value, $this->lga, $this->district, $this->area, $this->building];
    }
}
