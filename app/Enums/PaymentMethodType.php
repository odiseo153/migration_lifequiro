<?php

namespace App\Enums;


enum PaymentMethodType: int
{
    case EFECTIVO = 1;
    case TARJETA = 2;
    case CHEQUE = 3;
    case TRANSFERENCIA = 4;
    case NOTA_CREDITO = 5;
    case CREDITO = 6;
}
