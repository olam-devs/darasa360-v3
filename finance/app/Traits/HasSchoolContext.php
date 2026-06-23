<?php

namespace App\Traits;

use App\Models\Central\School;

/**
 * Trait to provide consistent school context resolution across controllers.
 */
trait HasSchoolContext
{
    /**
     * Get the current school ID for logging and other purposes.
     */
    protected function getSchoolId(): ?int
    {
        $school = $this->getCurrentSchool();
        return $school ? $school->id : null;
    }

    /**
     * Get the current school model.
     */
    protected function getCurrentSchool(): ?School
    {
        try {
            // Method 1: Try app container
            try {
                $school = app('current_school');
                if ($school) {
                    return $school;
                }
            } catch (\Exception $e) {
                // App container failed, continue to other methods
            }

            // Method 2: Try from database name in environment
            $dbName = env('TENANT_DB_DATABASE') ?: env('DB_DATABASE');
            if ($dbName) {
                $school = School::on('central')->where('database_name', $dbName)->first();
                if ($school) {
                    return $school;
                }
            }

            // Method 3: Try from session (impersonation)
            if (session()->has('impersonating_school_id')) {
                $schoolId = session('impersonating_school_id');
                return School::on('central')->find($schoolId);
            }

            // Method 4: If only one school exists, use it as fallback
            $schoolCount = School::on('central')->count();
            if ($schoolCount === 1) {
                return School::on('central')->first();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
