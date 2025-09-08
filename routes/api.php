<?php

use App\Models\Legacy\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Console\Commands\MigratePlanes;
use App\Console\Commands\MigrateCentros;
use App\Console\Commands\MigratePatients;
use App\Console\Commands\MigratePlanesAsignados;
use App\Models\Patient;

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
$pa=Paciente::find(1);
$pa->update([
    'nombre' => 'ANNETTEs',
]);

    return $pa;
    });
