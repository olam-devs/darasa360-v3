<?php

namespace App\Http\Controllers;

use App\Services\SchoolProvisioningService;
use Illuminate\Http\Request;

/**
 * Server-to-server endpoints for the Finance app (see routes/internal_api.php
 * and VerifyInternalApiSecret). Not reachable by browsers in practice since
 * every route here requires the shared secret header.
 */
class InternalApiController extends Controller
{
    public function createSchool(Request $request, SchoolProvisioningService $provisioning)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'location_name' => 'required|string|max:255',
            'db_name' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email',
            'owner_phone' => 'required|string',
            'owner_username' => 'required|string',
            'owner_password' => 'required|string|min:6',
            'price_per_user' => 'nullable|numeric|min:0',
        ]);

        try {
            $school = $provisioning->provisionSchool($validated);

            return response()->json([
                'ok' => true,
                'school_id' => $school->id,
                'database_url' => $school->database_url,
                'school_code' => $school->school_code,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
