<?php

namespace App\Enums;

enum PetMatchStatus: string
{
    case Pending = 'PENDING';
    case Confirmed = 'CONFIRMED';
    case Dismissed = 'DISMISSED';
}
