<?php

namespace App\Enums;

enum PetReportStatus: string
{
    case Lost = 'LOST';
    case Found = 'FOUND';
    case Cancelled = 'CANCELLED';
}
