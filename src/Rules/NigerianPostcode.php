<?php

namespace EmmanuelKolawole\NgPostcode\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use EmmanuelKolawole\NgPostcode\Postcode;
use EmmanuelKolawole\NgPostcode\State;

final class NigerianPostcode implements ValidationRule
{
    private array $states;

    public function __construct(State|string ...$states)
    {
        $this->states = array_map(
            fn(State|string $s) => $s instanceof State ? $s->value : strtoupper($s),
            $states,
        );

        foreach ($this->states as $code) {
            State::from($code);
        }
    }


    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        if (!is_string($value)) {
            $fail('The :attribute must be a valid Nigerian postcode.');
            return;
        }

        $postcode = Postcode::tryParse($value);

        if ($postcode === null) {
            $fail('The :attribute must be a valid Nigerian postcode.');
            return;
        }

        if ($this->states !== [] && ! in_array($postcode->state->value, $this->states, true)) {
            $fail('The :attribute must be a postcode in ' . implode(', ', $this->states) . '.');
        }
    }
}
