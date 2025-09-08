<?php

namespace App\Enums;


enum AppointmentStatus: int
{
    case COMPLETADA = 1;
    case PROGRAMADA = 2;
    case POSPUESTA = 3;
    case NO_ASISTIO = 4;
    case ATENDIENDO = 6;
    case EN_ESPERA = 7;
    case RADIOGRAFIA = 8;
    case REPROGRAMADA = 15;
    case NO_RADIOGRAFIA = 16;
    case CONFIRMADA = 18;
    case DESACTIVADA = 19;
}
