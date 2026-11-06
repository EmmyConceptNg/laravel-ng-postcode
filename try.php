<?php
require 'vendor/autoload.php';

use EmmanuelKolawole\NgPostcode\Postcode;
use EmmanuelKolawole\NgPostcode\State;


echo Postcode::parse('ek 01 a03 fk 01')->canonical(), PHP_EOL;
var_dump(Postcode::tryParse('EK-00-A03-FK-01'));
var_dump(Postcode::tryParse('XX-01-A03-FK-01'));
try {
    Postcode::parse('930283');
} catch (InvalidArgumentException $e) {
    echo 'Rejected: ', $e->getMessage(), PHP_EOL;
}



echo State::Lagos->label(), PHP_EOL;      // Lagos
echo State::AkwaIbom->label(), PHP_EOL;   // Akwa Ibom
echo State::FCT->label(), PHP_EOL;        // whatever you chose
echo Postcode::parse('EK-01-A03-FK-01')->state->label(), PHP_EOL; // Ekiti