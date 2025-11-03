<?php

use App\Models\Legacy\Ajuste;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/migrate-patients', [App\Http\Controllers\MigrationController::class, 'migratePatientsByIds']);

// Update assigned plan endpoint
Route::put('/assigned-plans', [App\Http\Controllers\MigrationController::class, 'updateAssignedPlan']);
Route::put('/change-type-of-patient', [App\Http\Controllers\MigrationController::class, 'changeTypeOfPatient']);

// Update assigned plans from legacy database
Route::post('/update-assigned-plans-from-legacy', [App\Http\Controllers\MigrationController::class, 'updateAssignedPlansFromLegacy']);
Route::post('/delete-assigned-plans-for-patient-branch', [App\Http\Controllers\MigrationController::class, 'deleteAssignedPlanForPatientBranch']);

// Test endpoint to check assigned plan data
Route::post('/test-assigned-plan-data', [App\Http\Controllers\MigrationController::class, 'testAssignedPlanData']);

Route::get('/test', function (Request $request) {
    $plans = [461, 462, 458, 434, 435, 436, 437, 438, 439, 441, 442, 443, 444, 445, 446, 453, 454, 455, 456, 412, 416, 417, 419, 420, 422, 423, 426, 428, 395, 396, 397, 398, 400, 401, 402, 404, 406, 407, 399, 355, 354, 353, 352, 351, 350, 349, 347, 346, 344, 343, 341, 337, 336, 335, 329, 328, 327, 326, 325, 324, 323, 322, 314, 313, 311, 309, 308, 299, 287, 286, 285, 283, 278, 277, 276, 275, 274, 273, 268, 267, 266, 265, 264, 263, 262, 261, 258, 257, 256, 255, 254, 253, 252, 251, 250, 249, 248, 247, 246, 244, 243, 242, 241, 240];


    return Ajuste::whereIn('plan_id', $plans)->count();
});
