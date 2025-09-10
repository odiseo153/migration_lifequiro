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

return User::inRandomOrder()->first();
    });
