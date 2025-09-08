<?php

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

Route::get('/migrate-all', function (Request $request) {
MigrateCentros::handle();
MigratePatients::handle();
MigratePlanes::handle();
MigratePlanesAsignados::handle();

return 'Migración completada';
});

Route::get('/test', function (Request $request) {


    return Paciente::limit(10)->select('id','estado_civil','sexo')->get();
    });
