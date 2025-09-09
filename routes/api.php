<?php

use Carbon\Carbon;
use App\Models\User;
use App\Models\Patient;
use App\Models\WhereHeMetUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::get('/test', function (Request $request) {
$where_met_us_id = null;
                    $is_refencia_acceptable = true;

                    if ($is_refencia_acceptable) {
                        $referencia = strtolower(trim('TarjetaPop'));

                        $matches = WhereHeMetUs::all(); // todos los registros posibles
                        $bestScore = 0;
                        $bestMatchId = null;

                        foreach ($matches as $match) {
                            similar_text($referencia, strtolower($match->name), $percent);

                            if ($percent > $bestScore) {
                                $bestScore = $percent;
                                $bestMatchId = $match->id;
                            }
                        }

                        // Si encontramos algo con similitud aceptable (ej. más del 60%)
                        if ($bestScore >= 60) {
                            $where_met_us_id = $bestMatchId;
                        }
                    }

return $where_met_us_id;
    });
