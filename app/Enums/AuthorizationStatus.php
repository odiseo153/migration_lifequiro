<?php

namespace App\Enums;


enum AuthorizationStatus: int
{
    case PENDIENTE = 1;
    case APROBADO = 2;
    case AUTORIZADO = 4;
    case RECHAZADO = 3;
}
