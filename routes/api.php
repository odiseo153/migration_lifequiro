<?php

use Carbon\Carbon;
use App\Models\Patient;
use Illuminate\Http\Request;
use App\Models\Legacy\Centro;
use App\Models\Legacy\Paciente;
use Illuminate\Support\Facades\Route;
use App\Console\Commands\MigratePlanes;
use App\Console\Commands\MigrateCentros;
use App\Console\Commands\MigratePatients;
use App\Console\Commands\MigratePlanesAsignados;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::get('/test', function (Request $request) {
$date=Carbon::parse('2021-08-05 15:36:21');
return $date->format('H:i:s');
    });
