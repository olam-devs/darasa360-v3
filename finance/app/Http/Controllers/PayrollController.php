<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\PayrollDeductionType;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryDeduction;
use App\Models\Staff;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    // Staff Management
    // ────────────────────────────────────────────────────────────────────────

    public function indexStaff()
    {
        $staff = Staff::orderBy('name')->get();
        return response()->json(['staff' => $staff]);
    }

    public function storeStaff(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'staff_id'       => 'required|string|unique:staff,staff_id',
            'position'       => 'required|string|max:255',
            'department'     => 'nullable|string|max:255',
            'monthly_salary' => 'required|numeric|min:0',
            'phone'          => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'bank_name'      => 'nullable|string|max:255',
            'bank_account'   => 'nullable|string|max:255',
            'date_joined'    => 'nullable|date',
            'notes'          => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status']     = 'active';

        $staff = Staff::create($validated);

        return response()->json($staff, 201);
    }

    public function showStaff($id)
    {
        $staff = Staff::with('payrollEntries.deductions')->findOrFail($id);
        return response()->json($staff);
    }

    public function updateStaff(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'staff_id'       => 'required|string|unique:staff,staff_id,' . $id,
            'position'       => 'required|string|max:255',
            'department'     => 'nullable|string|max:255',
            'monthly_salary' => 'required|numeric|min:0',
            'phone'          => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'bank_name'      => 'nullable|string|max:255',
            'bank_account'   => 'nullable|string|max:255',
            'date_joined'    => 'nullable|date',
            'status'         => 'required|in:active,inactive,suspended',
            'notes'          => 'nullable|string',
        ]);

        $staff->update($validated);

        return response()->json($staff);
    }

    public function destroyStaff($id)
    {
        $staff = Staff::findOrFail($id);

        if ($staff->payrollEntries()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete staff with existing payroll entries.',
            ], 400);
        }

        $staff->delete();

        return response()->json(['message' => 'Staff deleted successfully.']);
    }

    public function staffPaymentHistory($id)
    {
        $staff    = Staff::findOrFail($id);
        $payments = $staff->payrollEntries()
            ->with(['book', 'deductions'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'staff'            => $staff,
            'payments'         => $payments,
            'total_gross'      => $payments->sum('gross_salary'),
            'total_deductions' => $payments->sum('total_deductions'),
            'total_net'        => $payments->sum('net_salary'),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Deduction Types (master list)
    // ────────────────────────────────────────────────────────────────────────

    public function indexDeductionTypes()
    {
        $types = PayrollDeductionType::where('is_active', true)->orderBy('name')->get();
        return response()->json($types);
    }

    public function storeDeductionType(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:fixed,percentage,insurance,penalty,other',
            'default_value' => 'required|numeric|min:0',
            'is_percentage' => 'boolean',
            'notes'         => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_active']  = true;
        $validated['is_percentage'] = $validated['is_percentage'] ?? ($validated['type'] === 'percentage');

        $type = PayrollDeductionType::create($validated);
        return response()->json($type, 201);
    }

    public function updateDeductionType(Request $request, $id)
    {
        $type = PayrollDeductionType::findOrFail($id);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:fixed,percentage,insurance,penalty,other',
            'default_value' => 'required|numeric|min:0',
            'is_percentage' => 'boolean',
            'is_active'     => 'boolean',
            'notes'         => 'nullable|string',
        ]);

        $type->update($validated);
        return response()->json($type);
    }

    public function destroyDeductionType($id)
    {
        $type = PayrollDeductionType::findOrFail($id);

        if ($type->entryDeductions()->count() > 0) {
            $type->update(['is_active' => false]);
            return response()->json(['message' => 'Deduction type deactivated (has history).']);
        }

        $type->delete();
        return response()->json(['message' => 'Deduction type deleted.']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Payroll Entry Management
    // ────────────────────────────────────────────────────────────────────────

    public function indexPayroll(Request $request)
    {
        $query = PayrollEntry::with(['staff', 'book', 'deductions']);

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        $payrolls = $query->orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        return response()->json([
            'payrolls'         => $payrolls,
            'total_gross'      => $payrolls->sum('gross_salary'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'total_net'        => $payrolls->sum('net_salary'),
        ]);
    }

    /**
     * Process payroll for one staff member.
     * Accepts:
     *   staff_id, book_id, month, year, gross_salary, payment_date,
     *   payment_method, reference_number, notes,
     *   deductions[] → [{deduction_type_id?, name, type, amount, note}]
     *
     * Creates a Voucher (Payment type) from the book for net_salary.
     */
    public function storePayroll(Request $request)
    {
        $validated = $request->validate([
            'staff_id'         => 'required|exists:staff,id',
            'book_id'          => 'required|exists:books,id',
            'month'            => 'required|integer|min:1|max:12',
            'year'             => 'required|integer|min:2000',
            'gross_salary'     => 'required|numeric|min:0',
            'payment_date'     => 'required|date',
            'payment_method'   => 'required|in:cash,bank_transfer,cheque,mobile_money',
            'reference_number' => 'nullable|string|max:255',
            'notes'            => 'nullable|string',
            'deductions'       => 'nullable|array',
            'deductions.*.deduction_type_id' => 'nullable|exists:payroll_deduction_types,id',
            'deductions.*.name'   => 'required_with:deductions|string|max:255',
            'deductions.*.type'   => 'required_with:deductions|in:fixed,percentage,insurance,penalty,other',
            'deductions.*.amount' => 'required_with:deductions|numeric|min:0',
            'deductions.*.note'   => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $gross     = (float) $validated['gross_salary'];
            $deductions = $validated['deductions'] ?? [];

            // Calculate total deductions
            $totalDeductions = 0.0;
            foreach ($deductions as &$ded) {
                if (($ded['type'] ?? 'fixed') === 'percentage') {
                    // Percentage deductions are % of gross
                    $ded['amount'] = round($gross * ((float) $ded['amount'] / 100), 2);
                }
                $totalDeductions += (float) $ded['amount'];
            }
            unset($ded);

            $netSalary = max(0.0, $gross - $totalDeductions);
            $period    = date('F Y', mktime(0, 0, 0, $validated['month'], 1, $validated['year']));

            $staff = Staff::findOrFail($validated['staff_id']);

            // Create voucher — net salary paid from book
            $voucher = Voucher::create([
                'voucher_type'     => 'Payment',
                'date'             => $validated['payment_date'],
                'book_id'          => $validated['book_id'],
                'debit'            => 0,
                'credit'           => $netSalary,
                'narration'        => 'Salary — ' . $staff->name . ' (' . $period . ')',
                'reference_number' => $validated['reference_number'] ?? null,
                'created_by'       => auth()->id(),
            ]);

            // Create payroll entry
            $payroll = PayrollEntry::create([
                'staff_id'         => $validated['staff_id'],
                'book_id'          => $validated['book_id'],
                'voucher_id'       => $voucher->id,
                'period'           => $period,
                'month'            => $validated['month'],
                'year'             => $validated['year'],
                'gross_salary'     => $gross,
                'total_deductions' => $totalDeductions,
                'net_salary'       => $netSalary,
                'status'           => 'paid',
                'payment_date'     => $validated['payment_date'],
                'payment_method'   => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes'            => $validated['notes'] ?? null,
                'created_by'       => auth()->id(),
            ]);

            // Save individual deductions
            foreach ($deductions as $ded) {
                PayrollEntryDeduction::create([
                    'payroll_entry_id'  => $payroll->id,
                    'deduction_type_id' => $ded['deduction_type_id'] ?? null,
                    'name'              => $ded['name'],
                    'type'              => $ded['type'],
                    'amount'            => $ded['amount'],
                    'note'              => $ded['note'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'payroll'   => $payroll->load(['staff', 'deductions', 'book']),
                'net_salary' => $netSalary,
                'message'   => "Payroll processed. Net salary: TSH " . number_format($netSalary, 0),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function showPayroll($id)
    {
        $payroll = PayrollEntry::with(['staff', 'book', 'deductions.deductionType', 'voucher'])->findOrFail($id);
        return response()->json($payroll);
    }

    public function destroyPayroll($id)
    {
        $payroll = PayrollEntry::findOrFail($id);
        DB::beginTransaction();
        try {
            $payroll->deductions()->delete();
            if ($payroll->voucher_id) {
                Voucher::find($payroll->voucher_id)?->delete();
            }
            $payroll->delete();
            DB::commit();
            return response()->json(['message' => 'Payroll entry deleted.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Reports
    // ────────────────────────────────────────────────────────────────────────

    /** Monthly summary: total gross, deductions, net — per staff */
    public function monthlyReport(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $payrolls = PayrollEntry::with(['staff', 'book', 'deductions'])
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        return response()->json([
            'month'            => $month,
            'year'             => $year,
            'payrolls'         => $payrolls,
            'total_gross'      => $payrolls->sum('gross_salary'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'total_net'        => $payrolls->sum('net_salary'),
            'staff_count'      => $payrolls->count(),
        ]);
    }

    /** Deductions ledger: breakdown by deduction type across all entries */
    public function deductionsLedger(Request $request)
    {
        $query = PayrollEntryDeduction::with(['payrollEntry.staff'])
            ->join('payroll_entries', 'payroll_entry_deductions.payroll_entry_id', '=', 'payroll_entries.id');

        if ($request->filled('month')) {
            $query->where('payroll_entries.month', $request->month);
        }
        if ($request->filled('year')) {
            $query->where('payroll_entries.year', $request->year);
        }
        if ($request->filled('deduction_type_id')) {
            $query->where('payroll_entry_deductions.deduction_type_id', $request->deduction_type_id);
        }
        if ($request->filled('staff_id')) {
            $query->where('payroll_entries.staff_id', $request->staff_id);
        }

        $deductions = $query->select('payroll_entry_deductions.*')->get();

        // Group by deduction name for summary
        $summary = $deductions->groupBy('name')->map(function ($rows, $name) {
            return [
                'name'   => $name,
                'type'   => $rows->first()->type,
                'total'  => $rows->sum('amount'),
                'count'  => $rows->count(),
            ];
        })->values();

        return response()->json([
            'deductions'       => $deductions,
            'summary'          => $summary,
            'total_deducted'   => $deductions->sum('amount'),
        ]);
    }

    /** Staff payroll ledger: all entries for one staff with deductions */
    public function staffLedger($staffId, Request $request)
    {
        $staff   = Staff::findOrFail($staffId);
        $entries = PayrollEntry::with(['deductions', 'book'])
            ->where('staff_id', $staffId)
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->year))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'staff'            => $staff,
            'entries'          => $entries,
            'total_gross'      => $entries->sum('gross_salary'),
            'total_deductions' => $entries->sum('total_deductions'),
            'total_net'        => $entries->sum('net_salary'),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // CSV Import / Export
    // ────────────────────────────────────────────────────────────────────────

    public function downloadStaffTemplate()
    {
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="staff-import-template.csv"');

        fputcsv($handle, ['staff_id', 'name', 'position', 'department', 'monthly_salary', 'phone', 'email', 'bank_name', 'bank_account', 'date_joined']);
        fputcsv($handle, ['STF001', 'John Doe', 'Teacher', 'Science', '800000', '255712345678', 'john@example.com', 'NMB Bank', '1234567890', '2024-01-01']);

        fclose($handle);
        exit;
    }

    public function uploadStaffCsv(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $file    = $request->file('file');
        $data    = array_map('str_getcsv', file($file->getRealPath()));
        $headers = array_shift($data);

        $imported = 0;
        $errors   = [];

        DB::beginTransaction();
        try {
            foreach ($data as $row) {
                $staffData = array_combine($headers, $row);

                Staff::updateOrCreate(
                    ['staff_id' => $staffData['staff_id']],
                    [
                        'name'           => $staffData['name'],
                        'position'       => $staffData['position'],
                        'department'     => $staffData['department'] ?? null,
                        'monthly_salary' => $staffData['monthly_salary'],
                        'phone'          => $staffData['phone'] ?? null,
                        'email'          => $staffData['email'] ?? null,
                        'bank_name'      => $staffData['bank_name'] ?? null,
                        'bank_account'   => $staffData['bank_account'] ?? null,
                        'date_joined'    => $staffData['date_joined'] ?? null,
                        'status'         => 'active',
                        'created_by'     => auth()->id(),
                    ]
                );
                $imported++;
            }

            DB::commit();
            return response()->json([
                'message' => "Successfully imported {$imported} staff members.",
                'errors'  => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
