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
    /**
     * Fast, read-only check - used by Finance to confirm whether a school
     * creation actually succeeded when the create call's own response was
     * lost to this host's proxy-level timeout on long-running requests
     * (the create call itself runs 100+ migrations and can take minutes;
     * this lookup takes milliseconds and won't hit the same issue).
     *
     * IMPORTANT: the School row itself is created *before* migrations run
     * (see SchoolProvisioningService::provisionSchool), so row-existence
     * alone does NOT prove the school is actually usable - this host has
     * been observed to kill the request mid-migration, leaving a school
     * row with an incomplete/broken tenant database and no owner account.
     * user_id only gets set as the very last step (after migrations and
     * owner-account creation both succeeded and the transaction committed),
     * so that's what "exists" here actually means: fully provisioned, not
     * just "a row happens to be there".
     */
    public function schoolExists(Request $request)
    {
        $dbName = $request->query('db_name');
        $school = $dbName ? \App\Models\School::where('database_url', $dbName)->first() : null;
        $complete = $school && $school->user_id !== null;

        return response()->json([
            'ok' => true,
            'exists' => $complete,
            'row_exists_but_incomplete' => $school && !$complete,
            'school_id' => $school->id ?? null,
        ]);
    }

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
