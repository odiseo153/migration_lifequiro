<?php

namespace App\Enums;


enum AppointmentType: int
{
    case CONSULTA = 1;
    case RADIOGRAFIA = 2;
    case REPORTE = 3;
    case MIP = 4;
    case MR = 5;
    case AP = 6;
    case COMPARACION = 7;
    case RADIOGRAFIA_RC = 8;
}
