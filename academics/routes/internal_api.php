<?php

use App\Http\Controllers\InternalApiController;
use App\Http\Middleware\VerifyInternalApiSecret;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal cross-app API
|--------------------------------------------------------------------------
| Called by the Finance app's SchoolProvisioningService when a school needs
| Academics provisioned (either at creation, or later via the "enable
| Academics" toggle). Not browser-facing - see VerifyInternalApiSecret.
*/

Route::middleware([VerifyInternalApiSecret::class])->prefix('internal-api')->group(function () {
    Route::post('/schools', [InternalApiController::class, 'createSchool']);
    Route::get('/schools/exists', [InternalApiController::class, 'schoolExists']);
});
