<?php

use App\Models\PhysicalExamination;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Patient;
use App\Models\WhereHeMetUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\PhysicalTherapyCategory;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function (Request $request) {
    ini_set('max_execution_time', 300); // 300 segundos = 5 minutos
    set_time_limit(300);

    DB::transaction(function () {

        // 1. Padre base
        $base = PhysicalTherapyCategory::find(63);

        if (!$base) {
            throw new \Exception("No se encontró el padre base con id=63");
        }

        // 2. Hijos directos del padre base
        $children = $base->children;

        if ($children->isEmpty()) {
            throw new \Exception("El padre base no tiene hijos para replicar");
        }

        // 3. Otros padres con name LIKE '%ejercicio%' (excluyendo el base)
        $otherParents = PhysicalTherapyCategory::where('name', 'like', '%ejercicio%')
            ->where('id', '<>', $base->id)
            ->get();

        // Función recursiva
        $cloneChildren = function ($originalNode, $newParentId) use (&$cloneChildren) {
            // Clonamos el nodo actual en el nuevo padre
            $newNode = PhysicalTherapyCategory::create([
                'name'        => $originalNode->name,
                'description' => $originalNode->description,
                'father_id'   => $newParentId,
                'type'        => $originalNode->type,
            ]);

            // Si el original tiene hijos → clonamos también sus hijos
            foreach ($originalNode->children as $child) {
                $cloneChildren($child, $newNode->id);
            }
        };

        // 4. Replicar en cada padre de destino
        foreach ($otherParents as $parent) {
            foreach ($children as $child) {
                $cloneChildren($child, $parent->id);
            }
        }

        return $base->children;
    });
});
