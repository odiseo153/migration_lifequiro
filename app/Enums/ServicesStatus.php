<?php

namespace App\Enums;


enum ServicesStatus: int
{
    case ESPERA = 1;
    case ATENDIENDO = 2;
    case COMPLETADA = 3;
}
