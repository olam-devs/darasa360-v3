<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AdvancePaymentController extends Controller
{
    public function page()
    {
        $settings = SchoolSetting::getSettings();

        return view('admin.accountant.modules.advance-payments', compact('settings'));
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = Student::with('schoolClass')
            ->where('advance_balance', '>', 0);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('student_reg_no', 'like', "%{$q}%");
            });
        }

        $students = $query->orderBy('advance_balance', 'desc')->orderBy('name')->get();

        return response()->json([
            'students' => $students,
            'summary' => [
                'count' => $students->count(),
                'total_advance' => (float) $students->sum('advance_balance'),
            ],
        ]);
    }

    public function pdf(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = Student::with('schoolClass')
            ->where('advance_balance', '>', 0);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('student_reg_no', 'like', "%{$q}%");
            });
        }

        $students = $query->orderBy('advance_balance', 'desc')->orderBy('name')->get();
        $school = SchoolSetting::getSettings();
        $total = (float) $students->sum('advance_balance');

        $pdf = Pdf::loadView('advance-payments.pdf', [
            'school' => $school,
            'students' => $students,
            'total' => $total,
            'q' => $q,
            'generatedAt' => now(),
        ]);

        return $pdf->download('advance-payments.pdf');
    }

    public function csv(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = Student::with('schoolClass')
            ->where('advance_balance', '>', 0);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('student_reg_no', 'like', "%{$q}%");
            });
        }

        $students = $query->orderBy('advance_balance', 'desc')->orderBy('name')->get();

        $filename = 'advance-payments.csv';

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Reg No', 'Class', 'Advance Balance (TSH)']);
            foreach ($students as $s) {
                fputcsv($out, [
                    $s->name,
                    $s->student_reg_no,
                    $s->schoolClass->name ?? $s->class ?? '',
                    number_format((float) $s->advance_balance, 2, '.', ''),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
