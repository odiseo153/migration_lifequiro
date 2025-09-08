<?php

use App\Models\Patient;
use Illuminate\Http\Request;
use App\Models\Legacy\Centro;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/migrate-patients', function (Request $request) {
return  Patient::get();
});
