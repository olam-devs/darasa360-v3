<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookFeeCategory;
use App\Models\PayrollDeductionType;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryDeduction;
use App\Models\Staff;
use App\Models\StaffDeduction;
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
    // Per-staff Deduction Presets
    // ────────────────────────────────────────────────────────────────────────

    public function indexStaffDeductions($staffId)
    {
        Staff::findOrFail($staffId);
        $deductions = StaffDeduction::where('staff_id', $staffId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        return response()->json(['deductions' => $deductions]);
    }

    public function storeStaffDeduction(Request $request, $staffId)
    {
        Staff::findOrFail($staffId);
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'type'              => 'required|in:fixed,percentage,insurance,penalty,other',
            'default_amount'    => 'required|numeric|min:0',
            'deduction_type_id' => 'nullable|exists:payroll_deduction_types,id',
            'note'              => 'nullable|string|max:255',
        ]);
        $validated['staff_id']  = $staffId;
        $validated['is_active'] = true;
        $ded = StaffDeduction::create($validated);
        return response()->json(['deduction' => $ded], 201);
    }

    public function updateStaffDeduction(Request $request, $staffId, $dedId)
    {
        $ded = StaffDeduction::where('staff_id', $staffId)->findOrFail($dedId);
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:fixed,percentage,insurance,penalty,other',
            'default_amount' => 'required|numeric|min:0',
            'note'           => 'nullable|string|max:255',
        ]);
        $ded->update($validated);
        return response()->json(['deduction' => $ded]);
    }

    public function destroyStaffDeduction($staffId, $dedId)
    {
        $ded = StaffDeduction::where('staff_id', $staffId)->findOrFail($dedId);
        $ded->delete();
        return response()->json(['message' => 'Deduction preset removed.']);
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
            'book_fee_category_id' => 'nullable|exists:book_fee_categories,id',
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
                'voucher_type'          => 'Payment',
                'date'                  => $validated['payment_date'],
                'book_id'               => $validated['book_id'],
                'debit'                 => 0,
                'credit'                => $netSalary,
                'payment_by_receipt_to' => $staff->name,
                'notes'                 => 'Salary — ' . $staff->name . ' (' . $period . ')'
                    . (! empty($validated['reference_number']) ? ' — Ref: ' . $validated['reference_number'] : ''),
                'created_by'            => auth()->id(),
            ]);

            // Optional transaction fee — the accountant explicitly picks one
            // of the book's configured BookFeeCategory options (same
            // mechanism Withdrawals and Expenses use); leaving it unset cuts
            // no fee at all. Fee is based on net_salary, the amount actually
            // leaving the book, matching how Withdrawals base it on the
            // withdrawal amount.
            $feeVoucher = null;
            $feeAmount = null;
            $feeCategoryId = null;

            if (!empty($validated['book_fee_category_id'])) {
                $feeCategory = BookFeeCategory::where('book_id', $validated['book_id'])
                    ->where('is_active', true)
                    ->find($validated['book_fee_category_id']);

                if ($feeCategory) {
                    $fee = $feeCategory->resolveFeeForAmount($netSalary);
                    if ($fee > 0) {
                        $feeVoucher = Voucher::create([
                            'date'                  => $validated['payment_date'],
                            'student_id'             => null,
                            'particular_id'          => $feeCategory->particular_id,
                            'book_id'                => $validated['book_id'],
                            'voucher_type'           => 'Payment',
                            'debit'                  => 0,
                            'credit'                 => $fee,
                            'payment_by_receipt_to'  => 'Bank Transaction Fee',
                            'notes'                  => sprintf(
                                'Transaction fee (%s) for payroll — %s (%s), net TSh %s. Linked voucher #%s.',
                                $feeCategory->name,
                                $staff->name,
                                $period,
                                number_format($netSalary, 2),
                                $voucher->voucher_number
                            ),
                            'created_by' => auth()->id(),
                        ]);
                        $feeAmount = $fee;
                        $feeCategoryId = $feeCategory->id;
                    }
                }
            }

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
                'bank_fee_voucher_id'  => $feeVoucher?->id,
                'bank_fee_amount'      => $feeAmount,
                'bank_fee_category_id' => $feeCategoryId,
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
                'payroll'   => $payroll->load(['staff', 'deductions', 'book', 'bankFeeCategory']),
                'net_salary' => $netSalary,
                'message'   => "Payroll processed. Net salary: TSH " . number_format($netSalary, 0)
                    . ($feeAmount ? " (+ TSH " . number_format($feeAmount, 0) . " transaction fee)" : ""),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function showPayroll($id)
    {
        $payroll = PayrollEntry::with(['staff', 'book', 'deductions.deductionType', 'voucher', 'bankFeeCategory'])->findOrFail($id);
        return response()->json($payroll);
    }

    /**
     * Update an existing payroll entry's payment details (routed as PUT
     * /api/payroll/{id} but never implemented - editing a payroll entry
     * 500'd with a missing-method error). Deductions are left as already
     * recorded; adjust gross salary/notes/reference and keep the linked
     * voucher's amount and description in sync.
     */
    public function updatePayroll(Request $request, $id)
    {
        $payroll = PayrollEntry::with(['deductions', 'staff'])->findOrFail($id);

        $validated = $request->validate([
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

        $gross = (float) $validated['gross_salary'];
        $deductions = $validated['deductions'] ?? [];

        $totalDeductions = 0.0;
        foreach ($deductions as &$ded) {
            if (($ded['type'] ?? 'fixed') === 'percentage') {
                $ded['amount'] = round($gross * ((float) $ded['amount'] / 100), 2);
            }
            $totalDeductions += (float) $ded['amount'];
        }
        unset($ded);

        $netSalary = max(0.0, $gross - $totalDeductions);

        DB::beginTransaction();
        try {
            // Replace deductions entirely
            $payroll->deductions()->delete();
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

            $payroll->update([
                'gross_salary'     => $gross,
                'total_deductions' => $totalDeductions,
                'net_salary'       => $netSalary,
                'payment_date'     => $validated['payment_date'],
                'payment_method'   => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes'            => $validated['notes'] ?? null,
            ]);

            if ($payroll->voucher_id) {
                Voucher::where('id', $payroll->voucher_id)->update([
                    'date'   => $validated['payment_date'],
                    'credit' => $netSalary,
                    'notes'  => 'Salary — '.$payroll->staff->name.' ('.$payroll->period.')'
                        .(! empty($validated['reference_number']) ? ' — Ref: '.$validated['reference_number'] : ''),
                ]);
            }

            if ($payroll->bank_fee_voucher_id && $payroll->bank_fee_category_id) {
                $feeCategory = BookFeeCategory::find($payroll->bank_fee_category_id);
                $newFee = $feeCategory ? $feeCategory->resolveFeeForAmount($netSalary) : 0.0;
                Voucher::where('id', $payroll->bank_fee_voucher_id)->update([
                    'date'   => $validated['payment_date'],
                    'credit' => $newFee,
                ]);
                $payroll->update(['bank_fee_amount' => $newFee]);
            }

            DB::commit();
            return response()->json($payroll->fresh(['staff', 'book', 'deductions', 'voucher']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroyPayroll($id)
    {
        $payroll = PayrollEntry::findOrFail($id);
        DB::beginTransaction();
        try {
            $payroll->deductions()->delete();
            if ($payroll->bank_fee_voucher_id) {
                Voucher::find($payroll->bank_fee_voucher_id)?->delete();
            }
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
