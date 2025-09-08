<?php

namespace App\Traits;

use App\Models\Branch;
use App\Http\Exceptions\GeneralExceptionMessage;

trait BranchScheduleValidatorTrait
{
    /**
     * Valida si una sucursal está disponible en un día y hora específicos.
     *
     * @param int $branchId
     * @param string $day
     * @param string $hour
     * @throws GeneralExceptionMessage
     */
    public function validateBranchAvailability($branchId, string $day, string $hour): void
    {
        // Obtener la sucursal seleccionada
        $branch = Branch::find($branchId);

        $branchSchedule = $branch->schedules()
            ->where('day', $day)
            ->where('hour', $hour)
            ->exists();

        if (!$branchSchedule) {
            // Buscar horas disponibles en la sucursal actual
            $availableHours = $branch->schedules()
                ->where('day', $day)
                ->pluck('hour');

            // Si no hay horas disponibles en esta sucursal, buscar en otras sucursales de la misma empresa
            if ($availableHours->isEmpty()) {
                $availableBranches = $branch->company
                    ->branches()
                    ->whereHas('schedules', function ($query) use ($day, $hour) {
                        $query->where('day', $day)->where('hour', $hour);
                    })
                    ->with(['schedules' => function ($query) use ($day, $hour) {
                        $query->where('day', $day)->where('hour', $hour);
                    }])
                    ->get(['id', 'name']);

                if ($availableBranches->isNotEmpty()) {
                    // Construir mensaje con sucursales disponibles
                    $branchOptions = $availableBranches->map(function ($branch) {
                        return "Sucursal ID: {$branch->id}, Nombre: {$branch->name}";
                    })->implode(' | ');

                    throw new GeneralExceptionMessage(403,
                        "No hay disponibilidad en esta sucursal, pero hay en otras: {$branchOptions}."
                    );
                }
            }

            // Generar mensaje de error final
            // Traducir día del inglés al español
            $dayTranslations = [
                'monday' => 'lunes',
                'tuesday' => 'martes',
                'wednesday' => 'miércoles',
                'thursday' => 'jueves',
                'friday' => 'viernes',
                'saturday' => 'sábado',
                'sunday' => 'domingo'
            ];
            
            $dayInSpanish = $dayTranslations[strtolower($day)] ?? $day;
            
            // Convertir horas a formato 12 horas con AM/PM
            $formattedHours = $availableHours->map(function ($hour) {
                // Extraer solo HH:MM si viene en formato HH:MM:SS
                $hourFormatted = substr($hour, 0, 5);
                $time = \DateTime::createFromFormat('H:i', $hourFormatted);
                return $time ? $time->format('g:i A') : $hourFormatted;
            });
            
            $errorMessage = $availableHours->isEmpty()
                ? 'No hay disponibilidad en esta sucursal ni en otras de la empresa para el día y hora seleccionados.'
                : 'La hora para el día '.$dayInSpanish.' seleccionado no está disponible en esta sucursal. Horas disponibles: ' . $formattedHours->implode(', ');

            throw new GeneralExceptionMessage(403,$errorMessage);
        }
    }
}
