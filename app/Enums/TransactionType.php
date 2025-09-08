<?php

namespace App\Enums;


enum TransactionType: int
{
    case ITEMS = 1;
    case COMBOS_OFERTAS = 2;
    case PAGO_PLAN = 3;
    case NOTA_CREDITO = 4;
    case PAGO_DEUDA = 5;
}
