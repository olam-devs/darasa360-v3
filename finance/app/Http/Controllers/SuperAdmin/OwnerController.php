<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LedgerController;
use App\Models\Central\School;
use App\Services\TenantDatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerController extends Controller
{
    public function __construct(protected TenantDatabaseManager $tenantManager) {}

    // ── Live stats ───────────────────────────────────────────────────────────

    public function liveStats()
    {
        $schools  = School::active()->orderBy('name')->get();
        $results  = [];

        foreach ($schools as $school) {
            try {
                $this->tenantManager->switchToSchool($school);
                $conn = DB::connection('tenant');

                $results[] = [
                    'id'               => $school->id,
                    'name'             => $school->name,
                    'logo'             => $school->logo ? asset('storage/' . $school->logo) : null,
                    'students'         => $conn->table('students')->where('is_active', 1)->count(),
                    'today_collection' => (float) $conn->table('vouchers')->where('voucher_type', 'Receipt')->whereDate('date', today())->sum('debit'),
                    'month_collection' => (float) $conn->table('vouchers')->where('voucher_type', 'Receipt')->whereYear('date', now()->year)->whereMonth('date', now()->month)->sum('debit'),
                    'year_collection'  => (float) $conn->table('vouchers')->where('voucher_type', 'Receipt')->whereYear('date', now()->year)->sum('debit'),
                    'outstanding'      => (float) ($conn->table('particular_student')->selectRaw('SUM(GREATEST(0, sales - credit)) as t')->value('t') ?? 0),
                    'advance_total'    => (float) $conn->table('students')->where('is_active', 1)->sum('advance_balance'),
                    'recent_receipts'  => $conn->table('vouchers')
                        ->join('students', 'vouchers.student_id', '=', 'students.id')
                        ->select('vouchers.id', 'vouchers.date', 'vouchers.debit as amount', 'students.id as student_id', 'students.name as student_name', 'vouchers.created_at')
                        ->where('vouchers.voucher_type', 'Receipt')
                        ->whereNull('vouchers.voided_at')
                        ->orderByDesc('vouchers.created_at')
                        ->limit(5)->get(),
                    'error'            => null,
                ];
            } catch (\Exception $e) {
                $results[] = ['id' => $school->id, 'name' => $school->name, 'logo' => null, 'error' => $e->getMessage()];
            }
        }

        return response()->json($results);
    }

    // ── Cross-school student search ──────────────────────────────────────────

    public function search(Request $request)
    {
        $q       = trim($request->get('q', ''));
        $results = [];

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        foreach (School::active()->orderBy('name')->get() as $school) {
            try {
                $this->tenantManager->switchToSchool($school);

                $rows = DB::connection('tenant')
                    ->table('students as s')
                    ->leftJoin('school_classes as c', 'c.id', '=', 's.school_class_id')
                    ->select('s.id', 's.name', 's.advance_balance',
                             DB::raw('c.name as class_name'),
                             DB::raw('(SELECT SUM(GREATEST(0, ps.sales - ps.credit)) FROM particular_student ps WHERE ps.student_id = s.id) as outstanding'))
                    ->where('s.is_active', 1)
                    ->where('s.name', 'like', "%{$q}%")
                    ->orderBy('s.name')
                    ->limit(10)
                    ->get();

                foreach ($rows as $row) {
                    $results[] = array_merge((array) $row, [
                        'school_id'   => $school->id,
                        'school_name' => $school->name,
                    ]);
                }
            } catch (\Exception) {}
        }

        usort($results, fn($a, $b) => strcmp($a['name'], $b['name']));
        return response()->json(array_slice($results, 0, 30));
    }

    // ── Student detail (fees + recent receipts + books) ──────────────────────

    public function studentDetail(School $school, $studentId)
    {
        $this->tenantManager->switchToSchool($school);
        $conn = DB::connection('tenant');

        $student = $conn->table('students as s')
            ->leftJoin('school_classes as c', 'c.id', '=', 's.school_class_id')
            ->select('s.id', 's.name', 's.advance_balance', DB::raw('c.name as class_name'))
            ->where('s.id', $studentId)->first();

        if (!$student) return response()->json(['error' => 'Student not found'], 404);

        $particulars = $conn->table('particular_student as ps')
            ->join('particulars as p', 'p.id', '=', 'ps.particular_id')
            ->select('p.id', 'p.name', 'ps.sales', 'ps.credit',
                     DB::raw('GREATEST(0, ps.sales - ps.credit) as outstanding'))
            ->where('ps.student_id', $studentId)
            ->orderBy('p.name')
            ->get();

        $receipts = $conn->table('vouchers as v')
            ->leftJoin('books as b', 'b.id', '=', 'v.book_id')
            ->select('v.id', 'v.date', 'v.debit as amount', 'v.notes', DB::raw('b.name as book_name'), 'v.created_at')
            ->where('v.student_id', $studentId)
            ->where('v.voucher_type', 'Receipt')
            ->whereNull('v.voided_at')
            ->orderByDesc('v.created_at')
            ->limit(10)->get();

        $books = $conn->table('books')->select('id', 'name')->orderBy('name')->get();

        $schoolSettings = $conn->table('school_settings')->first();

        return response()->json([
            'student'          => $student,
            'particulars'      => $particulars,
            'receipts'         => $receipts,
            'books'            => $books,
            'school_name'      => $school->name,
            'school_id'        => $school->id,
            'school_logo'      => $school->logo ? asset('storage/' . $school->logo) : null,
            'school_settings'  => $schoolSettings,
        ]);
    }

    // ── Invoice PDF download ─────────────────────────────────────────────────

    public function downloadInvoice(School $school, $studentId, Request $request)
    {
        $this->tenantManager->switchToSchool($school);
        return app(LedgerController::class)->exportStudentInvoicePdf($studentId, $request);
    }

    // ── Record receipt ───────────────────────────────────────────────────────

    public function recordReceipt(School $school, Request $request)
    {
        $validated = $request->validate([
            'date'           => 'required|date',
            'student_id'     => 'required|integer',
            'book_id'        => 'required|integer',
            'notes'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.particular_id' => 'required|integer',
            'items.*.amount'        => 'required|numeric|min:0.01',
            'advance_amount' => 'nullable|numeric|min:0',
        ]);

        $this->tenantManager->switchToSchool($school);
        $conn         = DB::connection('tenant');
        $totalAmount  = 0.0;
        $particularsApplied = [];
        $advanceAmount = (float) ($validated['advance_amount'] ?? 0);

        DB::connection('tenant')->beginTransaction();
        try {
            foreach ($validated['items'] as $item) {
                $particular  = $conn->table('particulars')->where('id', $item['particular_id'])->first();
                $amount      = (float) $item['amount'];
                $totalAmount += $amount;

                $pivot = $conn->table('particular_student')
                    ->where('student_id', $validated['student_id'])
                    ->where('particular_id', $item['particular_id'])
                    ->first();

                if (!$pivot) {
                    $conn->table('particular_student')->insert([
                        'student_id'     => $validated['student_id'],
                        'particular_id'  => $item['particular_id'],
                        'sales'          => 0, 'debit' => 0, 'credit' => 0, 'overpayment' => 0,
                        'created_at'     => now(), 'updated_at' => now(),
                    ]);
                    $pivot = $conn->table('particular_student')
                        ->where('student_id', $validated['student_id'])
                        ->where('particular_id', $item['particular_id'])
                        ->first();
                }

                $conn->table('particular_student')
                    ->where('student_id', $validated['student_id'])
                    ->where('particular_id', $item['particular_id'])
                    ->update(['credit' => (float) $pivot->credit + $amount, 'updated_at' => now()]);

                $particularsApplied[] = $particular->name ?? "Particular #{$item['particular_id']}";
            }

            if ($advanceAmount > 0) {
                $conn->table('students')->where('id', $validated['student_id'])
                    ->increment('advance_balance', $advanceAmount);
                $totalAmount += $advanceAmount;
            }

            $notes = trim($validated['notes'] ?? '') ?: sprintf(
                'Receipt — %s (%s)',
                implode(', ', $particularsApplied),
                $conn->table('students')->where('id', $validated['student_id'])->value('name')
            );

            $voucherId = $conn->table('vouchers')->insertGetId([
                'date'                  => $validated['date'],
                'student_id'            => $validated['student_id'],
                'particular_id'         => null,
                'book_id'               => $validated['book_id'],
                'voucher_type'          => 'Receipt',
                'debit'                 => $totalAmount,
                'credit'                => 0,
                'payment_by_receipt_to' => 'Receipt',
                'notes'                 => $notes,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            DB::connection('tenant')->commit();

            return response()->json(['voucher_id' => $voucherId, 'total' => $totalAmount], 201);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
