<?php

namespace App\Enums;


enum PlanStatus: int
{
    case Activo = 1;
    case Completado = 2;
    case Expirado = 3;
    case Desactivado = 4;
}
