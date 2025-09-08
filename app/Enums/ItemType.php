<?php

namespace App\Enums;


enum ItemType: int
{
    case CONSULTA = 1;
    case RADIOGRAFIA = 2;
    case REPORTE = 3;
    case COMPARACION = 4;
    case TERAPIA_FISICA = 5;
    case TRACCION = 6;
    case AJUSTE = 7;
    case ANALISIS_DE_POSTURA = 8;
}
