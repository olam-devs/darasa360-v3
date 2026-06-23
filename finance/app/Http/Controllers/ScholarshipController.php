<?php

namespace App\Http\Controllers;

use App\Models\Scholarship;
use App\Models\Student;
use App\Models\Particular;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScholarshipController extends Controller
{
    /**
     * Get all scholarships with optional filters
     */
    public function index(Request $request)
    {
        $query = Scholarship::with(['student.schoolClass', 'particular', 'academicYear', 'appliedBy']);

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('particular_id')) {
            $query->where('particular_id', $request->particular_id);
        }

        if ($request->has('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active === 'true');
        }

        $scholarships = $query->orderBy('applied_date', 'desc')->paginate(15);

        // Calculate totals
        $totalForgiven = Scholarship::where('is_active', true)->sum('forgiven_amount');
        $totalScholarships = Scholarship::where('is_active', true)->count();

        return response()->json([
            'scholarships' => $scholarships,
            'summary' => [
                'total_scholarships' => $totalScholarships,
                'total_forgiven_amount' => $totalForgiven,
            ]
        ]);
    }

    /**
     * Get scholarships for a specific student
     */
    public function studentScholarships($studentId)
    {
        $student = Student::with('schoolClass')->findOrFail($studentId);

        $scholarships = Scholarship::where('student_id', $studentId)
            ->with(['particular', 'academicYear'])
            ->orderBy('applied_date', 'desc')
            ->get();

        $totalForgiven = $scholarships->where('is_active', true)->sum('forgiven_amount');

        return response()->json([
            'student' => $student,
            'scholarships' => $scholarships,
            'total_forgiven' => $totalForgiven,
        ]);
    }

    /**
     * Get detailed student info with particulars grouped by academic year for scholarship assignment
     */
    public function studentDetailsForScholarship($studentId)
    {
        $student = Student::with(['schoolClass', 'particulars'])->findOrFail($studentId);

        // Get existing scholarships for this student
        $existingScholarships = Scholarship::where('student_id', $studentId)
            ->with(['particular', 'academicYear'])
            ->get();

        // Get all academic years
        $academicYears = AcademicYear::where('is_active', true)
            ->orderBy('start_date', 'desc')
            ->get()
            ->keyBy('id');

        // Group particulars by academic year
        $particularsByYear = [];

        foreach ($student->particulars as $particular) {
            $yearId = $particular->pivot->academic_year_id;
            $yearKey = $yearId ?? 'none';
            $yearName = $yearId && isset($academicYears[$yearId])
                ? $academicYears[$yearId]->name
                : 'Unassigned Year';

            if (!isset($particularsByYear[$yearKey])) {
                $particularsByYear[$yearKey] = [
                    'year_id' => $yearId,
                    'year_name' => $yearName,
                    'particulars' => []
                ];
            }

            // Check if there's an existing active scholarship for this particular/year
            $existingScholarship = $existingScholarships
                ->where('particular_id', $particular->id)
                ->where('academic_year_id', $yearId)
                ->where('is_active', true)
                ->first();

            $particularsByYear[$yearKey]['particulars'][] = [
                'particular_id' => $particular->id,
                'particular_name' => $particular->name,
                'sales' => $particular->pivot->sales ?? 0,
                'credit' => $particular->pivot->credit ?? 0,
                'deadline' => $particular->pivot->deadline,
                'has_scholarship' => $existingScholarship !== null,
                'scholarship' => $existingScholarship,
            ];
        }

        return response()->json([
            'student' => $student,
            'particulars_by_year' => $particularsByYear,
            'existing_scholarships' => $existingScholarships,
            'academic_years' => $academicYears->values(),
        ]);
    }

    /**
     * Check if student has scholarship for a particular
     */
    public function checkScholarship(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'particular_id' => 'required|exists:particulars,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        $query = Scholarship::where('student_id', $validated['student_id'])
            ->where('particular_id', $validated['particular_id'])
            ->where('is_active', true);

        if (isset($validated['academic_year_id'])) {
            $query->where('academic_year_id', $validated['academic_year_id']);
        }

        $scholarship = $query->first();

        return response()->json([
            'has_scholarship' => $scholarship !== null,
            'scholarship' => $scholarship,
        ]);
    }

    /**
     * Apply a new scholarship to a student
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'particular_id' => 'required|exists:particulars,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'original_amount' => 'required|numeric|min:0',
            'forgiven_amount' => 'required|numeric|min:0',
            'scholarship_type' => 'required|in:full,partial',
            'scholarship_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'applied_date' => 'required|date',
        ]);

        // Check if scholarship already exists for this student/particular/year
        $existingQuery = Scholarship::where('student_id', $validated['student_id'])
            ->where('particular_id', $validated['particular_id']);

        if (isset($validated['academic_year_id'])) {
            $existingQuery->where('academic_year_id', $validated['academic_year_id']);
        }

        if ($existingQuery->where('is_active', true)->exists()) {
            return response()->json([
                'error' => 'An active scholarship already exists for this student and fee type.'
            ], 400);
        }

        // Validate forgiven amount doesn't exceed original
        if ($validated['forgiven_amount'] > $validated['original_amount']) {
            return response()->json([
                'error' => 'Forgiven amount cannot exceed the original amount.'
            ], 400);
        }

        // Calculate remaining amount
        $remainingAmount = $validated['original_amount'] - $validated['forgiven_amount'];

        // If full scholarship, forgiven should equal original
        if ($validated['scholarship_type'] === 'full' && $validated['forgiven_amount'] < $validated['original_amount']) {
            $validated['forgiven_amount'] = $validated['original_amount'];
            $remainingAmount = 0;
        }

        DB::beginTransaction();
        try {
            $scholarship = Scholarship::create([
                'student_id' => $validated['student_id'],
                'particular_id' => $validated['particular_id'],
                'academic_year_id' => $validated['academic_year_id'] ?? null,
                'original_amount' => $validated['original_amount'],
                'forgiven_amount' => $validated['forgiven_amount'],
                'remaining_amount' => $remainingAmount,
                'scholarship_type' => $validated['scholarship_type'],
                'scholarship_name' => $validated['scholarship_name'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'applied_date' => $validated['applied_date'],
                'applied_by' => auth()->id(),
                'is_active' => true,
            ]);

            // Update the particular_student pivot to reflect reduced amount
            // The remaining_amount is what the student now owes
            $student = Student::find($validated['student_id']);
            $academicYearId = $validated['academic_year_id'] ?? AcademicYear::where('is_current', true)->first()?->id;

            // Update the sales amount in pivot to the remaining amount (what student actually needs to pay)
            $pivotData = $student->particulars()
                ->wherePivot('particular_id', $validated['particular_id'])
                ->wherePivot('academic_year_id', $academicYearId)
                ->first();

            if ($pivotData) {
                // Update the sales to remaining amount (reduced fee)
                $student->particulars()->updateExistingPivot($validated['particular_id'], [
                    'sales' => $remainingAmount,
                ], false);
            }

            DB::commit();

            return response()->json([
                'message' => 'Scholarship applied successfully',
                'scholarship' => $scholarship->load(['student', 'particular', 'academicYear']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to apply scholarship: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update a scholarship
     */
    public function update(Request $request, $id)
    {
        $scholarship = Scholarship::findOrFail($id);

        $validated = $request->validate([
            'forgiven_amount' => 'sometimes|numeric|min:0',
            'scholarship_type' => 'sometimes|in:full,partial',
            'scholarship_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['forgiven_amount']) && $validated['forgiven_amount'] > $scholarship->original_amount) {
            return response()->json([
                'error' => 'Forgiven amount cannot exceed the original amount.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            if (isset($validated['forgiven_amount'])) {
                $validated['remaining_amount'] = $scholarship->original_amount - $validated['forgiven_amount'];
            }

            $scholarship->update($validated);

            // Update the pivot if forgiven amount changed
            if (isset($validated['forgiven_amount'])) {
                $student = Student::find($scholarship->student_id);
                $student->particulars()->updateExistingPivot($scholarship->particular_id, [
                    'sales' => $scholarship->remaining_amount,
                ], false);
            }

            DB::commit();

            return response()->json([
                'message' => 'Scholarship updated successfully',
                'scholarship' => $scholarship->fresh(['student', 'particular', 'academicYear']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update scholarship: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Deactivate a scholarship
     */
    public function deactivate($id)
    {
        $scholarship = Scholarship::findOrFail($id);

        DB::beginTransaction();
        try {
            $scholarship->update(['is_active' => false]);

            // Restore original amount in pivot
            $student = Student::find($scholarship->student_id);
            $student->particulars()->updateExistingPivot($scholarship->particular_id, [
                'sales' => $scholarship->original_amount,
            ], false);

            DB::commit();

            return response()->json([
                'message' => 'Scholarship deactivated and original fee amount restored',
                'scholarship' => $scholarship->fresh(['student', 'particular']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to deactivate scholarship: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get scholarship summary by particular (fee type)
     */
    public function summaryByParticular(Request $request)
    {
        $academicYearId = $request->get('academic_year_id');

        $query = Scholarship::where('is_active', true)
            ->select('particular_id')
            ->selectRaw('COUNT(*) as student_count')
            ->selectRaw('SUM(forgiven_amount) as total_forgiven')
            ->selectRaw('SUM(remaining_amount) as total_remaining')
            ->groupBy('particular_id');

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $summary = $query->get();

        // Add particular names
        $summary = $summary->map(function ($item) {
            $particular = Particular::find($item->particular_id);
            $item->particular_name = $particular ? $particular->name : 'Unknown';
            return $item;
        });

        return response()->json([
            'summary' => $summary,
            'grand_total_forgiven' => $summary->sum('total_forgiven'),
        ]);
    }
}
