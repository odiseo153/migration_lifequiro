<?php

namespace App\Enums;

enum AuthorizationConsumptionType: string
{
    case DISCOUNT = 'discount';
    case CREDIT = 'credit';
}