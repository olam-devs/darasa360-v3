<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    /**
     * Get all academic years
     */
    public function index()
    {
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        return response()->json($academicYears);
    }

    /**
     * Get active academic years (for dropdowns)
     */
    public function active()
    {
        $academicYears = AcademicYear::where('is_active', true)
            ->orderBy('start_date', 'desc')
            ->get();
        return response()->json($academicYears);
    }

    /**
     * Get the current academic year
     */
    public function current()
    {
        $currentYear = AcademicYear::current();

        if (!$currentYear) {
            return response()->json(['error' => 'No current academic year set'], 404);
        }

        return response()->json($currentYear);
    }

    /**
     * Create a new academic year
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
        ]);

        // If this is set as current, unset others
        if ($validated['is_current'] ?? false) {
            AcademicYear::where('is_current', true)->update(['is_current' => false]);
        }

        $academicYear = AcademicYear::create([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_current' => $validated['is_current'] ?? false,
            'is_active' => true,
        ]);

        return response()->json($academicYear, 201);
    }

    /**
     * Update an academic year
     */
    public function update(Request $request, $id)
    {
        $academicYear = AcademicYear::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // If this is set as current, unset others
        if ($validated['is_current'] ?? false) {
            AcademicYear::where('is_current', true)
                ->where('id', '!=', $id)
                ->update(['is_current' => false]);
        }

        $academicYear->update($validated);

        return response()->json($academicYear);
    }

    /**
     * Set an academic year as current
     */
    public function setCurrent($id)
    {
        $academicYear = AcademicYear::findOrFail($id);
        $academicYear->setAsCurrent();

        return response()->json([
            'message' => 'Academic year set as current',
            'academic_year' => $academicYear
        ]);
    }

    /**
     * Delete an academic year
     */
    public function destroy($id)
    {
        $academicYear = AcademicYear::findOrFail($id);

        // Check if there are fee assignments for this year
        $hasAssignments = \DB::connection('tenant')
            ->table('particular_student')
            ->where('academic_year_id', $id)
            ->exists();

        if ($hasAssignments) {
            return response()->json([
                'error' => 'Cannot delete academic year with existing fee assignments'
            ], 400);
        }

        $academicYear->delete();

        return response()->json(['message' => 'Academic year deleted successfully']);
    }
}
