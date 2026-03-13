<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'ADMIN';
    case Client = 'CLIENT';
    case Shop = 'SHOP';
}
