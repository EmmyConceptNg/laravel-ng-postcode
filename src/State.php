<?php

namespace EmmanuelKolawole\NgPostcode;

enum State: string
{
    case Abia = 'AB';
    case Adamawa = 'AD';
    case AkwaIbom = 'AK';
    case Anambra = 'AN';
    case Bauchi = 'BA';
    case Bayelsa = 'BY';
    case Benue = 'BE';
    case Borno = 'BR';
    case CrossRiver = 'CR';
    case Delta = 'DE';
    case Ebonyi = 'EB';
    case Edo = 'ED';
    case Ekiti = 'EK';
    case Enugu = 'EN';
    case Gombe = 'GM';
    case Imo = 'IM';
    case Jigawa = 'JI';
    case Kaduna = 'KD';
    case Kano = 'KN';
    case Katsina = 'KT';
    case Kebbi = 'KE';
    case Kogi = 'KG';
    case Kwara = 'KW';
    case Lagos = 'LA';
    case Nasarawa = 'NA';
    case Niger = 'NI';
    case Ogun = 'OG';
    case Ondo = 'ON';
    case Osun = 'OS';
    case Oyo = 'OY';
    case Plateau = 'PL';
    case Rivers = 'RI';
    case Sokoto = 'SK';
    case Taraba = 'TA';
    case Yobe = 'YB';
    case Zamfara = 'ZA';
    case FCT = 'FC';

    public function label(): string
    {
        return match ($this) {
            self::AkwaIbom => 'Akwa Ibom',
            self::CrossRiver => 'Cross River',
            self::FCT => 'Federal Capital Territory',
            default => $this->name,
        };
    }
}
