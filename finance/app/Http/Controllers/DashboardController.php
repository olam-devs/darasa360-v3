<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Voucher;
use App\Models\Book;
use App\Models\Particular;
use App\Models\Scholarship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'accountant' || $user->role === 'superadmin') {
            return redirect()->route('accountant.dashboard');
        }

        return view('dashboard');
    }

    public function getOverdueAmounts(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $particularId = $request->get('particular_id');
            
            $query = DB::table('particular_student as ps')
                ->join('students as s', 'ps.student_id', '=', 's.id')
                ->join('particulars as p', 'ps.particular_id', '=', 'p.id')
                ->leftJoin('school_classes as sc', 's.class_id', '=', 'sc.id')
                ->select(
                    's.id as student_id',
                    's.name as student_name',
                    's.student_reg_no',
                    'ps.particular_id',
                    DB::raw('COALESCE(sc.name, s.class) as class_name'),
                    'p.name as particular_name',
                    'ps.sales',
                    'ps.credit',
                    'ps.deadline',
                    DB::raw('(ps.sales - ps.credit) as balance'),
                    DB::raw('CEIL(ABS(DATEDIFF(NOW(), ps.deadline))) as days_overdue')
                )
                ->whereRaw('(ps.sales - ps.credit) > 0')
                ->where('ps.deadline', '<', now());

            // Filter by particular if specified
            if ($particularId && $particularId !== 'all') {
                $query->where('ps.particular_id', $particularId);
            }

            $query->orderBy('ps.deadline', 'asc');

            // Get paginated results
            $page = $request->get('page', 1);
            $overdueStudents = $query->get();
            
            // Calculate totals
            $totalOverdueAmount = $overdueStudents->sum('balance');
            $totalStudentsOverdue = $overdueStudents->unique('student_id')->count();
            $totalParticularsOverdue = $overdueStudents->count();

            // Paginate manually
            $paginatedStudents = $overdueStudents->forPage($page, $perPage);

            // Get all active scholarships for quick lookup
            $scholarshipsMap = Scholarship::where('is_active', true)
                ->get()
                ->groupBy(function($s) {
                    return $s->student_id . '_' . $s->particular_id;
                });

            // Group by student for the overdue_by_student structure
            $overdueByStudent = $paginatedStudents->groupBy('student_id')->map(function($items) use ($scholarshipsMap) {
                $student = $items->first();

                // Check if student has any scholarships
                $studentScholarships = Scholarship::where('student_id', $student->student_id)
                    ->where('is_active', true)
                    ->get();
                $hasScholarship = $studentScholarships->count() > 0;
                $totalScholarshipAmount = $studentScholarships->sum('forgiven_amount');

                return [
                    'student' => [
                        'id' => $student->student_id,
                        'name' => $student->student_name,
                        'reg_no' => $student->student_reg_no,
                        'class' => $student->class_name,
                        'has_scholarship' => $hasScholarship,
                        'total_scholarship_amount' => $totalScholarshipAmount,
                    ],
                    'total_overdue' => $items->sum('balance'),
                    'overdue_particulars' => $items->map(function($item) use ($scholarshipsMap) {
                        $key = $item->student_id . '_' . $item->particular_id;
                        $scholarship = $scholarshipsMap->get($key)?->first();

                        return [
                            'particular_id' => $item->particular_id,
                            'particular_name' => $item->particular_name,
                            'sales' => $item->sales,
                            'credit' => $item->credit,
                            'amount_due' => $item->balance,
                            'deadline' => $item->deadline,
                            'days_overdue' => (int)$item->days_overdue,
                            'has_scholarship' => $scholarship !== null,
                            'scholarship_amount' => $scholarship ? $scholarship->forgiven_amount : 0,
                            'original_amount' => $scholarship ? $scholarship->original_amount : $item->sales,
                        ];
                    })->values()->all(),
                ];
            })->values();

            // Group by particular for the overdue_by_particular structure
            $overdueByParticular = $overdueStudents->groupBy('particular_name')->map(function($items) {
                return [
                    'particular' => [
                        'id' => $items->first()->particular_id,
                        'name' => $items->first()->particular_name,
                    ],
                    'total_students_overdue' => $items->unique('student_id')->count(),
                    'total_amount_overdue' => $items->sum('balance'),
                ];
            })->values();

            return response()->json([
                'overdue_by_student' => $overdueByStudent,
                'overdue_by_particular' => $overdueByParticular,
                'all_entries' => $paginatedStudents->values(),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $overdueStudents->count(),
                    'last_page' => ceil($overdueStudents->count() / $perPage),
                ],
                'summary' => [
                    'total_overdue_amount' => $totalOverdueAmount,
                    'total_students_with_overdue' => $totalStudentsOverdue,
                    'total_overdue_particulars' => $totalParticularsOverdue,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
