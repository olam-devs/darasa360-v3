<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\BankAccount;
use App\Models\Book;
use App\Models\Particular;
use App\Models\Scholarship;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\SuspenseAccount;
use App\Models\Voucher;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class LedgerController extends Controller
{
    /**
     * @return array{particularDisplay: string, is_advance_used: bool, is_advance_deposit: bool}
     */
    protected function resolveLedgerParticularMeta(Voucher $voucher): array
    {
        if ($voucher->payment_by_receipt_to === 'Advance Used') {
            return [
                'particularDisplay' => ($voucher->particular?->name ?? 'Fee').' — paid from advance',
                'is_advance_used' => true,
                'is_advance_deposit' => false,
            ];
        }

        $particularDisplay = $voucher->particular?->name ?? 'N/A';
        $isAdvanceDeposit = false;

        if ($voucher->particular_id === null) {
            if ($voucher->voucher_type === 'Payment' && $voucher->payment_by_receipt_to && $voucher->payment_by_receipt_to !== 'Suspense Reversal') {
                $particularDisplay = 'Expense';
            } elseif ($voucher->payment_by_receipt_to === 'Suspense Account') {
                $particularDisplay = 'Suspense Account';
            } elseif ($voucher->payment_by_receipt_to === 'Suspense Reversal') {
                $particularDisplay = 'Suspense Reversal';
            } elseif ($voucher->payment_by_receipt_to === 'Suspense Resolution') {
                $particularDisplay = 'Suspense Resolution';
            } elseif ($voucher->payment_by_receipt_to === 'Bank Deposit') {
                $particularDisplay = 'Bank Deposit';
            } elseif ($voucher->payment_by_receipt_to === 'Bank Withdrawal') {
                $particularDisplay = 'Bank Withdrawal';
            } elseif ($voucher->payment_by_receipt_to === 'Monthly Bank Cut') {
                $particularDisplay = 'Monthly Bank Cut';
            } elseif ($voucher->payment_by_receipt_to === 'Bank Transaction Fee') {
                $particularDisplay = 'Bank Transaction Fee';
            } elseif ($voucher->payment_by_receipt_to === 'Reconciliation Adjustment') {
                $particularDisplay = 'Reconciliation Adjustment';
            } elseif ($voucher->payment_by_receipt_to === 'Advance Payment') {
                $particularDisplay = 'Advance Payment (held for student)';
                $isAdvanceDeposit = true;
            }
        }

        return [
            'particularDisplay' => $particularDisplay,
            'is_advance_used' => false,
            'is_advance_deposit' => $isAdvanceDeposit,
        ];
    }

    public function studentLedger($studentId, Request $request)
    {
        $student = Student::with('schoolClass')->findOrFail($studentId);

        $dateFrom = $request->get('from_date');
        $dateTo = $request->get('to_date');

        // Calculate opening balance if date filter is applied
        $openingBalance = 0;
        if ($dateFrom) {
            // Get all vouchers before the start date
            $openingBalance = Voucher::where('student_id', $studentId)
                ->where('date', '<', $dateFrom)
                ->selectRaw('SUM(debit) - SUM(credit) as balance')
                ->value('balance') ?? 0;
        }

        // Get voucher entries
        $query = Voucher::where('student_id', $studentId)
            ->with(['particular', 'book']);

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $vouchers = $query->orderBy('date')->get();

        // Separate sales entries from other entries
        $sales = [];
        $entries = [];
        $runningBalance = $openingBalance;

        foreach ($vouchers as $voucher) {
            $runningBalance += $voucher->debit - $voucher->credit;

            $entry = [
                'date' => $voucher->date,
                'particular' => $voucher->particular?->name ?? 'N/A',
                'voucher_type' => $voucher->voucher_type,
                'voucher_number' => $voucher->voucher_number,
                'debit' => $voucher->debit,
                'credit' => $voucher->credit,
                'balance' => $runningBalance,
                'notes' => $voucher->notes,
            ];

            if ($voucher->voucher_type === 'Sales') {
                $sales[] = $entry;
            } else {
                $entries[] = $entry;
            }
        }

        $totalDebit = $vouchers->sum('debit');
        $totalCredit = $vouchers->sum('credit');
        $closingBalance = $openingBalance + $totalDebit - $totalCredit;

        return response()->json([
            'student' => [
                'name' => $student->name,
                'student_reg_no' => $student->student_reg_no,
                'class' => $student->schoolClass->name ?? $student->class,
            ],
            'sales' => $sales,
            'entries' => $entries,
            'summary' => [
                'opening_balance' => $openingBalance,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'closing_balance' => $closingBalance,
            ],
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }

    public function classLedger($classId, Request $request)
    {
        $class = SchoolClass::findOrFail($classId);
        $students = $class->students()->with('particulars')->get();

        $dateFrom = $request->get('from_date');
        $dateTo = $request->get('to_date');
        $perPage = $request->get('per_page', 15); // Default 15 entries per page

        $totalOpeningBalance = 0;
        $studentsData = $students->map(function ($student) use ($dateFrom, $dateTo, &$totalOpeningBalance) {
            // Calculate opening balance if date filter is applied
            $openingBalance = 0;
            if ($dateFrom) {
                $openingBalance = Voucher::where('student_id', $student->id)
                    ->where('date', '<', $dateFrom)
                    ->selectRaw('SUM(debit) - SUM(credit) as balance')
                    ->value('balance') ?? 0;
            }
            $totalOpeningBalance += $openingBalance;

            $query = Voucher::where('student_id', $student->id);

            if ($dateFrom) {
                $query->where('date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('date', '<=', $dateTo);
            }

            $totalDebit = $query->sum('debit');
            $totalCredit = $query->sum('credit');

            $particularsSales = $student->particulars->sum('pivot.sales');
            $balance = $openingBalance + $totalDebit - $totalCredit;

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_reg_no' => $student->student_reg_no,
                ],
                'opening_balance' => $openingBalance,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'balance' => $balance,
            ];
        });

        $totalSales = $studentsData->sum('total_debit');
        $totalReceipts = $studentsData->sum('total_credit');
        $totalBalance = $studentsData->sum('balance');

        // Paginate the students data
        $page = $request->get('page', 1);
        $studentsCollection = collect($studentsData);
        $paginatedStudents = new LengthAwarePaginator(
            $studentsCollection->forPage($page, $perPage),
            $studentsCollection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'class' => $class->name,
            'students' => $paginatedStudents->items(),
            'pagination' => [
                'current_page' => $paginatedStudents->currentPage(),
                'last_page' => $paginatedStudents->lastPage(),
                'per_page' => $paginatedStudents->perPage(),
                'total' => $paginatedStudents->total(),
                'from' => $paginatedStudents->firstItem(),
                'to' => $paginatedStudents->lastItem(),
            ],
            'summary' => [
                'opening_balance' => $dateFrom ? $totalOpeningBalance : null,
                'total_sales' => $totalSales,
                'total_receipts' => $totalReceipts,
                'total_balance' => $totalBalance,
                'student_count' => $studentsData->count(),
            ],
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }

    public function bookLedger($bookId, Request $request)
    {
        $book = Book::findOrFail($bookId);

        $dateFrom = $request->get('from_date');
        $dateTo = $request->get('to_date');
        $viewType = $request->get('view_type', 'bank'); // 'bank' or 'cash' (cash = accountant view)

        // Calculate opening balance based on filter
        // If date filter is applied, calculate balance up to that date
        $openingBalance = $book->opening_balance;
        if ($dateFrom) {
            if ($viewType === 'cash') {
                $openingBalance = $book->getCashViewBalanceUpToDate($dateFrom);
            } else {
                $openingBalance = $book->getBalanceUpToDate($dateFrom);
            }
        }

        $query = Voucher::where('book_id', $bookId)
            ->with(['student', 'particular']);

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $vouchersCollection = $query->orderBy('date')->get();

        // Process entries with month boundaries for web view
        $ledger = [];
        $runningBalance = $openingBalance;
        $previousMonth = null;
        $monthlyDebit = 0;
        $monthlyCredit = 0;

        foreach ($vouchersCollection as $voucher) {
            $currentMonth = Carbon::parse($voucher->date)->format('Y-m');

            // Check if we've moved to a new month
            if ($previousMonth !== null && $currentMonth !== $previousMonth) {
                // Add month-end closing balance
                $ledger[] = [
                    'is_month_end' => true,
                    'month' => Carbon::parse($previousMonth.'-01')->format('F Y'),
                    'closing_balance' => $runningBalance,
                    'monthly_debit' => $monthlyDebit,
                    'monthly_credit' => $monthlyCredit,
                ];

                // Add month-start opening balance
                $ledger[] = [
                    'is_month_start' => true,
                    'month' => Carbon::parse($currentMonth.'-01')->format('F Y'),
                    'opening_balance' => $runningBalance,
                ];

                // Reset monthly totals
                $monthlyDebit = 0;
                $monthlyCredit = 0;
            }

            // If this is the first entry, add month opening
            if ($previousMonth === null) {
                $ledger[] = [
                    'is_month_start' => true,
                    'month' => Carbon::parse($currentMonth.'-01')->format('F Y'),
                    'opening_balance' => $openingBalance,
                ];
            }

            // Canonical storage is accountant view (cash):
            // - cash: show as stored (DR=debit, CR=credit)
            // - bank: invert for bank statement comparison (money in as CR, money out as DR)
            if ($viewType === 'bank') {
                $displayDebit = $voucher->credit;
                $displayCredit = $voucher->debit;
                $runningBalance += $displayDebit - $displayCredit;
            } else {
                $displayDebit = $voucher->debit;
                $displayCredit = $voucher->credit;
                $runningBalance += $voucher->debit - $voucher->credit;
            }

            $monthlyDebit += $displayDebit;
            $monthlyCredit += $displayCredit;

            $meta = $this->resolveLedgerParticularMeta($voucher);
            $particularDisplay = $meta['particularDisplay'];

            $forReconciliation = $request->boolean('for_reconciliation');
            $canEditHistory = (bool) (auth()->user()?->can_edit_history ?? false);
            $isVoided = $voucher->voided_at !== null;

            if ($forReconciliation && $canEditHistory && ! $isVoided) {
                $adjustable = true;
            } else {
                $adjustable = in_array($voucher->payment_by_receipt_to, [
                    'Bank Transaction Fee',
                    'Monthly Bank Cut',
                    'Reconciliation Adjustment',
                ], true);
            }

            $ledger[] = [
                'id' => $voucher->id,
                'date' => $voucher->date,
                'student' => $voucher->student?->name ?? $voucher->payment_by_receipt_to ?? 'N/A',
                'particular' => $particularDisplay,
                'voucher_type' => $voucher->voucher_type,
                'voucher_number' => $voucher->voucher_number,
                'debit' => $displayDebit,
                'credit' => $displayCredit,
                'balance' => $runningBalance,
                'notes' => $voucher->notes,
                'payment_by_receipt_to' => $voucher->payment_by_receipt_to,
                'storage_debit' => (float) $voucher->debit,
                'storage_credit' => (float) $voucher->credit,
                'adjustable' => $adjustable,
                'is_advance_used' => $meta['is_advance_used'],
                'is_voided' => $isVoided,
            ];

            $previousMonth = $currentMonth;
        }

        // Add final month-end closing balance
        if ($previousMonth !== null && count($vouchersCollection) > 0) {
            $ledger[] = [
                'is_month_end' => true,
                'month' => Carbon::parse($previousMonth.'-01')->format('F Y'),
                'closing_balance' => $runningBalance,
                'monthly_debit' => $monthlyDebit,
                'monthly_credit' => $monthlyCredit,
            ];
        }

        $totalDebit = $viewType === 'bank' ? $vouchersCollection->sum('credit') : $vouchersCollection->sum('debit');
        $totalCredit = $viewType === 'bank' ? $vouchersCollection->sum('debit') : $vouchersCollection->sum('credit');
        $closingBalance = $openingBalance + $totalDebit - $totalCredit;

        // Get suspense accounts for this book
        $suspenseAccounts = SuspenseAccount::where('book_id', $bookId)
            ->orderBy('resolved')
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($suspense) {
                $resolvedAmount = $suspense->resolved_amount ?? 0;
                $remainingAmount = $suspense->amount - $resolvedAmount;

                $status = 'Unresolved';
                if ($suspense->resolved) {
                    $status = 'Fully Resolved';
                } elseif ($resolvedAmount > 0) {
                    $status = 'Partially Resolved';
                }

                return [
                    'id' => $suspense->id,
                    'date' => $suspense->date,
                    'reference_number' => $suspense->reference_number,
                    'description' => $suspense->description,
                    'amount' => $suspense->amount,
                    'resolved_amount' => $resolvedAmount,
                    'remaining_amount' => $remainingAmount,
                    'status' => $status,
                    'resolved' => $suspense->resolved,
                    'resolved_at' => $suspense->resolved_at,
                ];
            });

        $totalSuspenseUnresolved = $suspenseAccounts->where('resolved', false)->sum('remaining_amount');

        return response()->json([
            'book' => $book,
            'ledger' => $ledger,
            'suspense_accounts' => $suspenseAccounts,
            'summary' => [
                'opening_balance' => $openingBalance,
                'book_opening_balance' => $book->opening_balance,
                'total_receipts' => $totalDebit,
                'total_payments' => $totalCredit,
                'closing_balance' => $closingBalance,
                'transaction_count' => $vouchersCollection->count(),
                'total_suspense_unresolved' => $totalSuspenseUnresolved,
            ],
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'view_type' => $viewType,
        ]);
    }

    /**
     * Debit/credit shown on the particular ledger (one fee item, all students).
     * Fee assignments are stored as Sales (DR). Payments are Receipt vouchers with amount in debit (canonical);
     * for this sub-ledger they are shown as credit so balance = fees billed − receipts − other credits.
     */
    private function particularLedgerDisplayedAmounts(Voucher $voucher): array
    {
        return match ($voucher->voucher_type) {
            'Sales' => ['debit' => (float) $voucher->debit, 'credit' => 0.0],
            'Receipt' => ['debit' => 0.0, 'credit' => (float) $voucher->debit],
            'Payment' => [
                'debit' => 0.0,
                'credit' => (float) ($voucher->credit > 0 ? $voucher->credit : $voucher->debit),
            ],
            default => ['debit' => (float) $voucher->debit, 'credit' => (float) $voucher->credit],
        };
    }

    public function particularLedger($particularId, Request $request)
    {
        $particular = Particular::findOrFail($particularId);

        $dateFrom = $request->get('from_date');
        $dateTo = $request->get('to_date');
        $perPage = $request->get('per_page', 15); // Default 15 entries per page

        // Create date range text
        $dateRangeText = 'All Transactions';
        if ($dateFrom && $dateTo) {
            $dateRangeText = "From: {$dateFrom} To: {$dateTo}";
        }

        // Get all entries for this particular
        $entriesQuery = Voucher::where('particular_id', $particularId)
            ->with(['student.schoolClass', 'book']);

        if ($dateFrom) {
            $entriesQuery->where('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $entriesQuery->where('date', '<=', $dateTo);
        }

        // Get paginated vouchers
        $vouchers = $entriesQuery->orderBy('date')->get();

        // Get scholarships for this particular
        $scholarshipsQuery = Scholarship::where('particular_id', $particularId)
            ->where('is_active', true)
            ->with(['student.schoolClass', 'academicYear']);

        if ($dateFrom) {
            $scholarshipsQuery->where('applied_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $scholarshipsQuery->where('applied_date', '<=', $dateTo);
        }

        $scholarships = $scholarshipsQuery->get();

        // Calculate scholarship totals for this particular
        $totalScholarshipAmount = $scholarships->sum('forgiven_amount');
        $scholarshipStudentCount = $scholarships->count();

        // Process entries with month-end/start highlights
        $entries = [];
        $runningBalance = 0;
        $previousMonth = null;
        $monthlyDebit = 0;
        $monthlyCredit = 0;

        foreach ($vouchers as $voucher) {
            $currentMonth = Carbon::parse($voucher->date)->format('Y-m');

            // Check if we've moved to a new month
            if ($previousMonth !== null && $currentMonth !== $previousMonth) {
                // Add month-end closing balance
                $entries[] = [
                    'is_month_end' => true,
                    'month' => Carbon::parse($previousMonth.'-01')->format('F Y'),
                    'closing_balance' => $runningBalance,
                    'monthly_debit' => $monthlyDebit,
                    'monthly_credit' => $monthlyCredit,
                ];

                // Add month-start opening balance
                $entries[] = [
                    'is_month_start' => true,
                    'month' => Carbon::parse($currentMonth.'-01')->format('F Y'),
                    'opening_balance' => $runningBalance,
                ];

                // Reset monthly totals
                $monthlyDebit = 0;
                $monthlyCredit = 0;
            }

            // If this is the first entry, add month opening
            if ($previousMonth === null) {
                $entries[] = [
                    'is_month_start' => true,
                    'month' => Carbon::parse($currentMonth.'-01')->format('F Y'),
                    'opening_balance' => 0,
                ];
            }

            $shown = $this->particularLedgerDisplayedAmounts($voucher);
            $displayDebit = $shown['debit'];
            $displayCredit = $shown['credit'];

            // Balance: fees billed (debit) minus payments and other credits
            $runningBalance += $displayDebit - $displayCredit;
            $monthlyDebit += $displayDebit;
            $monthlyCredit += $displayCredit;

            $entries[] = [
                'date' => $voucher->date,
                'student' => $voucher->student?->name ?? 'N/A',
                'class' => $voucher->student?->schoolClass?->name ?? $voucher->student?->class ?? 'N/A',
                'book' => $voucher->book?->name ?? 'N/A',
                'voucher_type' => $voucher->voucher_type,
                'debit' => $displayDebit,
                'credit' => $displayCredit,
                'balance' => $runningBalance,
            ];

            $previousMonth = $currentMonth;
        }

        // Add final month-end closing balance
        if ($previousMonth !== null && count($vouchers) > 0) {
            $entries[] = [
                'is_month_end' => true,
                'month' => Carbon::parse($previousMonth.'-01')->format('F Y'),
                'closing_balance' => $runningBalance,
                'monthly_debit' => $monthlyDebit,
                'monthly_credit' => $monthlyCredit,
            ];
        }

        // Calculate totals from displayed values (not original voucher values)
        $totalDebit = collect($entries)->where('debit', '>', 0)->sum('debit');
        $totalCredit = collect($entries)->where('credit', '>', 0)->sum('credit');
        $balance = $totalDebit - $totalCredit;

        // Paginate the entries array
        $page = (int) $request->get('page', 1);
        $entriesCollection = collect($entries);
        $total = $entriesCollection->count();

        // Properly slice the collection for the current page
        $offset = ($page - 1) * $perPage;
        $paginatedItems = $entriesCollection->slice($offset, $perPage)->values()->all();

        // Add opening balance sheet at start of page (if not first page)
        if ($page > 1 && count($paginatedItems) > 0) {
            // Find the balance before this page starts
            $previousPageLastEntry = $entriesCollection->slice(0, $offset)->last();
            $openingBalance = $previousPageLastEntry['balance'] ?? 0;

            array_unshift($paginatedItems, [
                'is_page_opening' => true,
                'opening_balance' => $openingBalance,
            ]);
        }

        // Add closing balance sheet at end of page (if not last page)
        if ($page < ceil($total / $perPage) && count($paginatedItems) > 0) {
            // Get the last entry's balance (excluding any page opening balance we just added)
            $regularEntries = array_filter($paginatedItems, function ($item) {
                return ! isset($item['is_page_opening']);
            });
            $lastEntry = end($regularEntries);
            reset($regularEntries); // Reset array pointer
            $closingBalance = $lastEntry['balance'] ?? 0;

            $paginatedItems[] = [
                'is_page_closing' => true,
                'closing_balance' => $closingBalance,
            ];
        }

        $paginatedEntries = new LengthAwarePaginator(
            $paginatedItems,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Build scholarship entries list
        $scholarshipEntries = $scholarships->map(function ($scholarship) {
            return [
                'id' => $scholarship->id,
                'date' => $scholarship->applied_date,
                'student' => $scholarship->student->name ?? 'N/A',
                'student_id' => $scholarship->student_id,
                'class' => $scholarship->student->schoolClass->name ?? 'N/A',
                'academic_year' => $scholarship->academicYear->name ?? 'N/A',
                'scholarship_type' => $scholarship->scholarship_type,
                'scholarship_name' => $scholarship->scholarship_name,
                'original_amount' => $scholarship->original_amount,
                'forgiven_amount' => $scholarship->forgiven_amount,
                'remaining_amount' => $scholarship->remaining_amount,
                'notes' => $scholarship->notes,
            ];
        });

        return response()->json([
            'particular' => [
                'id' => $particular->id,
                'name' => $particular->name,
                'description' => $particular->description,
                'is_active' => $particular->is_active,
            ],
            'entries' => $paginatedEntries->items(),
            'pagination' => [
                'current_page' => $paginatedEntries->currentPage(),
                'last_page' => $paginatedEntries->lastPage(),
                'per_page' => $paginatedEntries->perPage(),
                'total' => $paginatedEntries->total(),
                'from' => $paginatedEntries->firstItem(),
                'to' => $paginatedEntries->lastItem(),
            ],
            'date_range' => $dateRangeText,
            'summary' => [
                'opening_balance' => 0,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'balance' => $balance,
            ],
            'scholarships' => [
                'entries' => $scholarshipEntries,
                'total_forgiven' => $totalScholarshipAmount,
                'student_count' => $scholarshipStudentCount,
            ],
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }

    // PDF Export Methods
    public function exportStudentLedgerPdf($studentId, Request $request)
    {
        $student = Student::with('schoolClass')->findOrFail($studentId);
        $school = SchoolSetting::getSettings();

        $dateFrom = $request->get('from_date');
        $dateTo = $request->get('to_date');

        // Calculate opening balance if date filter is applied
        $openingBalance = 0;
        if ($dateFrom) {
            $openingBalance = Voucher::where('student_id', $studentId)
                ->where('date', '<', $dateFrom)
                ->selectRaw('SUM(debit) - SUM(credit) as balance')
                ->value('balance') ?? 0;
        }

        // Get voucher entries
        $query = Voucher::where('student_id', $studentId)
            ->with(['particular', 'book']);

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $vouchers = $query->orderBy('date')->get();

        // Separate sales entries from other entries
        $salesData = [];
        $entryData = [];
        $runningBalance = $openingBalance;

        foreach ($vouchers as $voucher) {
            $runningBalance += $voucher->debit - $voucher->credit;

            $entry = [
                'date' => $voucher->date,
                'particular' => $voucher->particular?->name ?? 'N/A',
                'voucher_type' => $voucher->voucher_type,
                'voucher_number' => $voucher->voucher_number,
                'debit' => $voucher->debit,
                'credit' => $voucher->credit,
                'balance' => $runningBalance,
                'notes' => $voucher->notes,
            ];

            if ($voucher->voucher_type === 'Sales') {
                $salesData[] = $entry;
            } else {
                $entryData[] = $entry;
            }
        }

        $totalDebit = $vouchers->sum('debit');
        $totalCredit = $vouchers->sum('credit');
        $balance = $openingBalance + $totalDebit - $totalCredit;

        $dateRange = 'All Transactions';
        if ($dateFrom && $dateTo) {
            $dateRange = "From: {$dateFrom} To: {$dateTo}";
        }

        $pdf = Pdf::loadView('ledgers.student-pdf', compact('student', 'school', 'salesData', 'entryData', 'totalDebit', 'totalCredit', 'balance', 'dateRange', 'openingBalance'));

        return $pdf->download("student-ledger-{$studentId}.pdf");
    }

    public function exportClassLedgerPdf($classId, Request $request)
    {
        $class = SchoolClass::findOrFail($classId);
        $students = $class->students()->with('particulars')->get();
        $school = SchoolSetting::getSettings();

        $dateFrom = $request->get('from_date');
        $dateTo = $request->get('to_date');

        $totalOpeningBalance = 0;
        $classLedger = $students->map(function ($student) use ($dateFrom, $dateTo, &$totalOpeningBalance) {
            $openingBalance = 0;
            if ($dateFrom) {
                $openingBalance = Voucher::where('student_id', $student->id)
                    ->where('date', '<', $dateFrom)
                    ->selectRaw('SUM(debit) - SUM(credit) as balance')
                    ->value('balance') ?? 0;
            }
            $totalOpeningBalance += $openingBalance;

            $query = Voucher::where('student_id', $student->id)
                ->with(['particular', 'book']);

            if ($dateFrom) {
                $query->where('date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('date', '<=', $dateTo);
            }

            $vouchers = $query->orderBy('date')->get();
            $totalDebit = $vouchers->sum('debit');
            $totalCredit = $vouchers->sum('credit');

            $balance = $openingBalance + $totalDebit - $totalCredit;

            // Build particulars array
            $particulars = $vouchers->map(function ($voucher) {
                return [
                    'date' => $voucher->date,
                    'particular' => $voucher->particular?->name ?? 'N/A',
                    'voucher_type' => $voucher->voucher_type,
                    'voucher_number' => $voucher->voucher_number,
                    'debit' => $voucher->debit,
                    'credit' => $voucher->credit,
                ];
            })->toArray();

            return [
                'student' => $student,
                'opening_balance' => $openingBalance,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'balance' => $balance,
                'particulars' => $particulars,
            ];
        });

        $totalSales = $classLedger->sum('total_debit');
        $totalReceipts = $classLedger->sum('total_credit');
        $totalBalance = $classLedger->sum('balance');

        $classSummary = [
            'total_sales' => $totalSales,
            'total_receipts' => $totalReceipts,
            'total_balance' => $totalBalance,
        ];

        $dateRange = 'All Transactions';
        if ($dateFrom && $dateTo) {
            $dateRange = "From: {$dateFrom} To: {$dateTo}";
        }

        $className = $class->name;

        $pdf = Pdf::loadView('ledgers.class-pdf', compact('className', 'school', 'classLedger', 'classSummary', 'dateRange'));

        return $pdf->download("class-ledger-{$classId}.pdf");
    }

    public function exportBookLedgerPdf($bookId, Request $request)
    {
        $book = Book::findOrFail($bookId);
        $school = SchoolSetting::getSettings();

        $dateFrom = $request->get('from_date');
        $dateTo = $request->get('to_date');
        $viewType = $request->get('view_type', 'bank');

        // Calculate opening balance based on filter
        $openingBalance = $book->opening_balance;
        if ($dateFrom) {
            if ($viewType === 'cash') {
                $openingBalance = $book->getCashViewBalanceUpToDate($dateFrom);
            } else {
                $openingBalance = $book->getBalanceUpToDate($dateFrom);
            }
        }

        $query = Voucher::where('book_id', $bookId)
            ->with(['student', 'particular']);

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $vouchersCollection = $query->orderBy('date')->get();

        // Process entries with month boundaries for PDF
        $ledgerData = [];
        $runningBalance = $openingBalance;
        $previousMonth = null;
        $monthlyDebit = 0;
        $monthlyCredit = 0;

        foreach ($vouchersCollection as $voucher) {
            $currentMonth = Carbon::parse($voucher->date)->format('Y-m');

            // Check if we've moved to a new month
            if ($previousMonth !== null && $currentMonth !== $previousMonth) {
                // Add month-end closing balance
                $ledgerData[] = [
                    'is_month_end' => true,
                    'month' => Carbon::parse($previousMonth.'-01')->format('F Y'),
                    'closing_balance' => $runningBalance,
                    'monthly_debit' => $monthlyDebit,
                    'monthly_credit' => $monthlyCredit,
                ];

                // Add month-start opening balance
                $ledgerData[] = [
                    'is_month_start' => true,
                    'month' => Carbon::parse($currentMonth.'-01')->format('F Y'),
                    'opening_balance' => $runningBalance,
                ];

                // Reset monthly totals
                $monthlyDebit = 0;
                $monthlyCredit = 0;
            }

            // If this is the first entry, add month opening
            if ($previousMonth === null) {
                $ledgerData[] = [
                    'is_month_start' => true,
                    'month' => Carbon::parse($currentMonth.'-01')->format('F Y'),
                    'opening_balance' => $openingBalance,
                ];
            }

            // Canonical storage is accountant view (cash).
            // For bank view PDF, invert debit/credit for statement comparison.
            if ($viewType === 'bank') {
                $displayDebit = $voucher->credit;
                $displayCredit = $voucher->debit;
                $runningBalance += $displayDebit - $displayCredit;
            } else {
                $displayDebit = $voucher->debit;
                $displayCredit = $voucher->credit;
                $runningBalance += $voucher->debit - $voucher->credit;
            }

            $monthlyDebit += $displayDebit;
            $monthlyCredit += $displayCredit;

            $meta = $this->resolveLedgerParticularMeta($voucher);
            $particularDisplay = $meta['particularDisplay'];

            $ledgerData[] = [
                'date' => $voucher->date,
                'student' => $voucher->student?->name ?? $voucher->payment_by_receipt_to ?? 'N/A',
                'particular' => $particularDisplay,
                'voucher_type' => $voucher->voucher_type,
                'voucher_number' => $voucher->voucher_number,
                'debit' => $displayDebit,
                'credit' => $displayCredit,
                'balance' => $runningBalance,
                'notes' => $voucher->notes,
            ];

            $previousMonth = $currentMonth;
        }

        // Add final month-end closing balance
        if ($previousMonth !== null && count($vouchersCollection) > 0) {
            $ledgerData[] = [
                'is_month_end' => true,
                'month' => Carbon::parse($previousMonth.'-01')->format('F Y'),
                'closing_balance' => $runningBalance,
                'monthly_debit' => $monthlyDebit,
                'monthly_credit' => $monthlyCredit,
            ];
        }

        $totalReceipts = $viewType === 'bank' ? $vouchersCollection->sum('credit') : $vouchersCollection->sum('debit');
        $totalPayments = $viewType === 'bank' ? $vouchersCollection->sum('debit') : $vouchersCollection->sum('credit');
        $balance = $openingBalance + $totalReceipts - $totalPayments;

        $dateRange = 'All Transactions';
        if ($dateFrom && $dateTo) {
            $dateRange = "From: {$dateFrom} To: {$dateTo}";
        }

        $pdf = Pdf::loadView('ledgers.book-pdf', compact('book', 'school', 'ledgerData', 'totalReceipts', 'totalPayments', 'balance', 'openingBalance', 'dateRange', 'viewType'));

        return $pdf->download("book-ledger-{$bookId}.pdf");
    }

    public function exportParticularLedgerPdf($particularId, Request $request)
    {
        $particular = Particular::findOrFail($particularId);
        $school = SchoolSetting::getSettings();

        $dateFrom = $request->get('from_date');
        $dateTo = $request->get('to_date');

        // Get all entries for this particular
        $entriesQuery = Voucher::where('particular_id', $particularId)
            ->with(['student.schoolClass', 'book']);

        if ($dateFrom) {
            $entriesQuery->where('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $entriesQuery->where('date', '<=', $dateTo);
        }

        $vouchersCollection = $entriesQuery->orderBy('date')->get();

        // Process entries with month-end/start highlights for PDF
        $vouchers = [];
        $runningBalance = 0;
        $previousMonth = null;
        $monthlyDebit = 0;
        $monthlyCredit = 0;

        foreach ($vouchersCollection as $voucher) {
            $currentMonth = Carbon::parse($voucher->date)->format('Y-m');

            // Check if we've moved to a new month
            if ($previousMonth !== null && $currentMonth !== $previousMonth) {
                // Add month-end closing balance
                $vouchers[] = (object) [
                    'is_month_end' => true,
                    'month' => Carbon::parse($previousMonth.'-01')->format('F Y'),
                    'closing_balance' => $runningBalance,
                    'monthly_debit' => $monthlyDebit,
                    'monthly_credit' => $monthlyCredit,
                ];

                // Add month-start opening balance
                $vouchers[] = (object) [
                    'is_month_start' => true,
                    'month' => Carbon::parse($currentMonth.'-01')->format('F Y'),
                    'opening_balance' => $runningBalance,
                ];

                // Reset monthly totals
                $monthlyDebit = 0;
                $monthlyCredit = 0;
            }

            // If this is the first entry, add month opening
            if ($previousMonth === null) {
                $vouchers[] = (object) [
                    'is_month_start' => true,
                    'month' => Carbon::parse($currentMonth.'-01')->format('F Y'),
                    'opening_balance' => 0,
                ];
            }

            $shown = $this->particularLedgerDisplayedAmounts($voucher);
            $displayDebit = $shown['debit'];
            $displayCredit = $shown['credit'];

            $runningBalance += $displayDebit - $displayCredit;
            $monthlyDebit += $displayDebit;
            $monthlyCredit += $displayCredit;

            // Store the display values in the voucher object for PDF rendering
            $voucher->display_debit = $displayDebit;
            $voucher->display_credit = $displayCredit;

            $vouchers[] = $voucher;
            $previousMonth = $currentMonth;
        }

        // Add final month-end closing balance
        if ($previousMonth !== null && count($vouchersCollection) > 0) {
            $vouchers[] = (object) [
                'is_month_end' => true,
                'month' => Carbon::parse($previousMonth.'-01')->format('F Y'),
                'closing_balance' => $runningBalance,
                'monthly_debit' => $monthlyDebit,
                'monthly_credit' => $monthlyCredit,
            ];
        }

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($vouchersCollection as $v) {
            $shown = $this->particularLedgerDisplayedAmounts($v);
            $totalDebit += $shown['debit'];
            $totalCredit += $shown['credit'];
        }
        $balance = $totalDebit - $totalCredit;
        $openingBalance = 0;

        $dateRange = 'All Transactions';
        if ($dateFrom && $dateTo) {
            $dateRange = "From: {$dateFrom} To: {$dateTo}";
        }

        $pdf = Pdf::loadView('ledgers.particular-pdf', compact('particular', 'school', 'vouchers', 'totalDebit', 'totalCredit', 'balance', 'openingBalance', 'dateRange'));

        return $pdf->download("particular-ledger-{$particularId}.pdf");
    }

    // CSV Export Methods
    public function exportStudentLedgerCsv($studentId, Request $request)
    {
        $student = Student::with('schoolClass')->findOrFail($studentId);

        $dateFrom = $request->get('from_date');
        $dateTo = $request->get('to_date');

        // Get voucher entries
        $query = Voucher::where('student_id', $studentId)
            ->with(['particular', 'book']);

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $vouchers = $query->orderBy('date')->get();

        $filename = "student-ledger-{$studentId}.csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        // Add student info header
        fputcsv($handle, ['Student Name:', $student->name]);
        fputcsv($handle, ['Registration Number:', $student->student_reg_no]);
        fputcsv($handle, ['Class:', $student->schoolClass->name ?? 'N/A']);
        fputcsv($handle, []); // Empty row

        fputcsv($handle, ['Date', 'Particular', 'Voucher Type', 'Voucher #', 'Debit', 'Credit', 'Notes']);

        foreach ($vouchers as $voucher) {
            fputcsv($handle, [
                $voucher->date,
                $voucher->particular?->name ?? '',
                $voucher->voucher_type,
                $voucher->voucher_number,
                $voucher->debit,
                $voucher->credit,
                $voucher->notes,
            ]);
        }

        fclose($handle);
        exit;
    }

    public function exportClassLedgerCsv($classId, Request $request)
    {
        $class = SchoolClass::findOrFail($classId);
        $students = $class->students()->with('particulars')->get();

        $dateFrom = $request->get('from_date');
        $dateTo = $request->get('to_date');

        $filename = "class-ledger-{$classId}.csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        // Add class info header
        fputcsv($handle, ['Class:', $class->name]);
        fputcsv($handle, []); // Empty row

        fputcsv($handle, ['Student Name', 'Reg No', 'Opening Balance', 'Debit', 'Credit', 'Balance']);

        foreach ($students as $student) {
            $openingBalance = 0;
            if ($dateFrom) {
                $openingBalance = Voucher::where('student_id', $student->id)
                    ->where('date', '<', $dateFrom)
                    ->selectRaw('SUM(debit) - SUM(credit) as balance')
                    ->value('balance') ?? 0;
            }

            $query = Voucher::where('student_id', $student->id);

            if ($dateFrom) {
                $query->where('date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('date', '<=', $dateTo);
            }

            $totalDebit = $query->sum('debit');
            $totalCredit = $query->sum('credit');
            $balance = $openingBalance + $totalDebit - $totalCredit;

            fputcsv($handle, [
                $student->name,
                $student->student_reg_no,
                $openingBalance,
                $totalDebit,
                $totalCredit,
                $balance,
            ]);
        }

        fclose($handle);
        exit;
    }

    public function exportBookLedgerCsv($bookId, Request $request)
    {
        $book = Book::findOrFail($bookId);

        $dateFrom = $request->get('from_date');
        $dateTo = $request->get('to_date');
        $viewType = $request->get('view_type', 'bank');

        // Calculate opening balance based on filter
        $openingBalance = $book->opening_balance;
        if ($dateFrom) {
            if ($viewType === 'cash') {
                $openingBalance = $book->getCashViewBalanceUpToDate($dateFrom);
            } else {
                $openingBalance = $book->getBalanceUpToDate($dateFrom);
            }
        }

        $query = Voucher::where('book_id', $bookId)
            ->with(['student', 'particular']);

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $vouchers = $query->orderBy('date')->get();

        $filename = "book-ledger-{$bookId}-{$viewType}.csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        // Add book info header
        fputcsv($handle, ['Book:', $book->name]);
        fputcsv($handle, ['View Type:', ucfirst($viewType).' View']);
        fputcsv($handle, ['Opening Balance:', $openingBalance]);
        if ($dateFrom) {
            fputcsv($handle, ['Period From:', $dateFrom]);
        }
        if ($dateTo) {
            fputcsv($handle, ['Period To:', $dateTo]);
        }
        fputcsv($handle, []); // Empty row

        $drLabel = $viewType === 'cash' ? 'DR (In)' : 'DR (Out)';
        $crLabel = $viewType === 'cash' ? 'CR (Out)' : 'CR (In)';
        fputcsv($handle, ['Date', 'Student/Name', 'Particular', 'Entry Type', 'Voucher Type', 'Voucher #', $drLabel, $crLabel, 'Balance', 'Notes']);

        $runningBalance = $openingBalance;
        $previousMonth = null;

        foreach ($vouchers as $voucher) {
            $currentMonth = Carbon::parse($voucher->date)->format('Y-m');

            // Add month boundary markers
            if ($previousMonth !== null && $currentMonth !== $previousMonth) {
                fputcsv($handle, ['--- End of '.Carbon::parse($previousMonth.'-01')->format('F Y').' ---', '', '', 'MONTH_END', '', '', '', '', $runningBalance, '']);
                fputcsv($handle, ['--- Start of '.Carbon::parse($currentMonth.'-01')->format('F Y').' ---', '', '', 'MONTH_START', '', '', '', '', $runningBalance, '']);
            }

            if ($previousMonth === null) {
                fputcsv($handle, ['--- Start of '.Carbon::parse($currentMonth.'-01')->format('F Y').' ---', '', '', 'MONTH_START', '', '', '', '', $openingBalance, '']);
            }

            // Canonical storage is accountant view (cash).
            // For bank view CSV, invert debit/credit for statement comparison.
            if ($viewType === 'bank') {
                $displayDebit = $voucher->credit;
                $displayCredit = $voucher->debit;
                $runningBalance += $displayDebit - $displayCredit;
            } else {
                $displayDebit = $voucher->debit;
                $displayCredit = $voucher->credit;
                $runningBalance += $voucher->debit - $voucher->credit;
            }

            $meta = $this->resolveLedgerParticularMeta($voucher);
            $particularDisplay = $meta['particularDisplay'];
            $entryType = $meta['is_advance_used'] ? 'ADVANCE_USED' : ($meta['is_advance_deposit'] ? 'ADVANCE' : 'NORMAL');
            if ($meta['is_advance_used'] || $meta['is_advance_deposit']) {
                // entryType set above
            } elseif ($voucher->particular_id === null) {
                if ($voucher->voucher_type === 'Payment' && $voucher->payment_by_receipt_to &&
                    ! in_array($voucher->payment_by_receipt_to, ['Suspense Reversal', 'Suspense Account', 'Suspense Resolution', 'Bank Deposit', 'Bank Withdrawal'])) {
                    $particularDisplay = 'Expense';
                    $entryType = 'EXPENSE';
                } elseif ($voucher->payment_by_receipt_to === 'Suspense Account') {
                    $particularDisplay = 'Suspense Account';
                    $entryType = 'SUSPENSE';
                } elseif ($voucher->payment_by_receipt_to === 'Suspense Reversal') {
                    $particularDisplay = 'Suspense Reversal';
                    $entryType = 'SUSPENSE_REVERSAL';
                } elseif ($voucher->payment_by_receipt_to === 'Suspense Resolution') {
                    $particularDisplay = 'Suspense Resolution';
                    $entryType = 'SUSPENSE_RESOLUTION';
                } elseif ($voucher->payment_by_receipt_to === 'Bank Deposit') {
                    $particularDisplay = 'Bank Deposit';
                    $entryType = 'DEPOSIT';
                } elseif ($voucher->payment_by_receipt_to === 'Bank Withdrawal') {
                    $particularDisplay = 'Bank Withdrawal';
                    $entryType = 'WITHDRAWAL';
                } elseif ($voucher->payment_by_receipt_to === 'Advance Payment') {
                    $particularDisplay = 'Advance Payment';
                    $entryType = 'ADVANCE';
                } elseif ($voucher->payment_by_receipt_to === 'Monthly Bank Cut') {
                    $particularDisplay = 'Monthly Bank Cut';
                    $entryType = 'MONTHLY_CUT';
                } elseif ($voucher->payment_by_receipt_to === 'Bank Transaction Fee') {
                    $particularDisplay = 'Bank Transaction Fee';
                    $entryType = 'BANK_FEE';
                } elseif ($voucher->payment_by_receipt_to === 'Reconciliation Adjustment') {
                    $particularDisplay = 'Reconciliation Adjustment';
                    $entryType = 'RECON_ADJ';
                }
            }

            fputcsv($handle, [
                $voucher->date,
                $voucher->student?->name ?? $voucher->payment_by_receipt_to ?? 'N/A',
                $particularDisplay,
                $entryType,
                $voucher->voucher_type,
                $voucher->voucher_number,
                $displayDebit,
                $displayCredit,
                $runningBalance,
                $voucher->notes,
            ]);

            $previousMonth = $currentMonth;
        }

        // Add final month end marker
        if ($previousMonth !== null) {
            fputcsv($handle, ['--- End of '.Carbon::parse($previousMonth.'-01')->format('F Y').' ---', '', '', 'MONTH_END', '', '', '', '', $runningBalance, '']);
        }

        fputcsv($handle, []); // Empty row
        fputcsv($handle, ['Closing Balance:', '', '', '', '', '', '', '', $runningBalance, '']);

        fclose($handle);
        exit;
    }

    // Invoice pages
    public function invoicesPage()
    {
        $settings = SchoolSetting::getSettings();
        $classes = SchoolClass::where('is_active', true)->orderBy('display_order')->get();

        return view('admin.accountant.modules.invoices', compact('settings', 'classes'));
    }

    /**
     * Build fee statement data for a student (single or bulk PDF).
     */
    protected function buildStudentInvoiceData(Student $student): array
    {
        $student->loadMissing(['schoolClass', 'particulars', 'scholarships']);

        $academicYears = AcademicYear::orderBy('start_date', 'asc')->get();

        $scholarshipMap = [];
        $totalScholarshipForgiven = 0;
        if ($student->scholarships) {
            foreach ($student->scholarships->where('is_active', true) as $scholarship) {
                $key = $scholarship->particular_id.'_'.($scholarship->academic_year_id ?? 'none');
                $scholarshipMap[$key] = $scholarship;
                $totalScholarshipForgiven += $scholarship->forgiven_amount;
            }
        }

        $itemsByYear = [];
        $totalFees = 0;
        $totalPaid = 0;
        $totalOriginalFees = 0;

        foreach ($student->particulars as $particular) {
            $sales = $particular->pivot->sales ?? 0;
            $credit = $particular->pivot->credit ?? 0;
            $balance = $sales - $credit;
            $deadline = $particular->pivot->deadline;
            $academicYearId = $particular->pivot->academic_year_id;
            $isOverdue = $deadline && Carbon::parse($deadline)->isPast() && $balance > 0;

            $scholarshipKey = $particular->id.'_'.($academicYearId ?? 'none');
            $scholarship = $scholarshipMap[$scholarshipKey] ?? null;
            $originalAmount = $scholarship ? $scholarship->original_amount : $sales;
            $scholarshipAmount = $scholarship ? $scholarship->forgiven_amount : 0;

            $academicYear = $academicYears->firstWhere('id', $academicYearId);
            $yearName = $academicYear ? $academicYear->name : 'Unassigned';

            if (! isset($itemsByYear[$yearName])) {
                $itemsByYear[$yearName] = [
                    'year_name' => $yearName,
                    'year_id' => $academicYearId,
                    'start_date' => $academicYear ? $academicYear->start_date : null,
                    'items' => [],
                    'subtotal_fees' => 0,
                    'subtotal_paid' => 0,
                    'subtotal_balance' => 0,
                    'subtotal_original' => 0,
                    'subtotal_scholarship' => 0,
                ];
            }

            $itemsByYear[$yearName]['items'][] = [
                'name' => $particular->name,
                'original_amount' => $originalAmount,
                'scholarship_amount' => $scholarshipAmount,
                'has_scholarship' => $scholarship !== null,
                'scholarship_type' => $scholarship ? $scholarship->scholarship_type : null,
                'scholarship_name' => $scholarship ? $scholarship->scholarship_name : null,
                'amount' => $sales,
                'paid' => $credit,
                'balance' => $balance,
                'deadline' => $deadline,
                'is_overdue' => $isOverdue,
            ];

            $itemsByYear[$yearName]['subtotal_fees'] += $sales;
            $itemsByYear[$yearName]['subtotal_paid'] += $credit;
            $itemsByYear[$yearName]['subtotal_balance'] += $balance;
            $itemsByYear[$yearName]['subtotal_original'] += $originalAmount;
            $itemsByYear[$yearName]['subtotal_scholarship'] += $scholarshipAmount;

            $totalOriginalFees += $originalAmount;
            $totalFees += $sales;
            $totalPaid += $credit;
        }

        uasort($itemsByYear, function ($a, $b) {
            if ($a['start_date'] === null) {
                return 1;
            }
            if ($b['start_date'] === null) {
                return -1;
            }

            return strtotime($a['start_date']) - strtotime($b['start_date']);
        });

        $items = [];
        foreach ($itemsByYear as $yearData) {
            foreach ($yearData['items'] as $item) {
                $item['academic_year'] = $yearData['year_name'];
                $items[] = $item;
            }
        }

        return [
            'items' => $items,
            'items_by_year' => array_values($itemsByYear),
            'total_fees' => $totalFees,
            'total_paid' => $totalPaid,
            'balance_remaining' => $totalFees - $totalPaid,
            'total_original_fees' => $totalOriginalFees,
            'total_scholarship_amount' => $totalScholarshipForgiven,
            'has_scholarships' => $totalScholarshipForgiven > 0,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Student>
     */
    protected function studentsForInvoiceExport(Request $request)
    {
        $query = Student::with(['schoolClass', 'particulars', 'scholarships'])
            ->where('status', 'active');

        $classId = $request->query('class');
        $classIds = $request->query('classes', []);

        if ($classId !== null && $classId !== '') {
            $query->where('class_id', $classId);
        } elseif (! empty($classIds)) {
            $query->whereIn('class_id', (array) $classIds);
        }

        return $query->orderBy('name')->get();
    }

    public function exportAllStudentsInvoicesPdf(Request $request)
    {
        $students = $this->studentsForInvoiceExport($request);
        $school = SchoolSetting::getSettings();
        $bankAccounts = BankAccount::all();

        $allInvoices = [];
        foreach ($students as $student) {
            $allInvoices[] = [
                'student' => $student,
                'invoiceData' => $this->buildStudentInvoiceData($student),
            ];
        }

        $filename = 'all-students-invoices.pdf';
        if ($request->filled('class')) {
            $class = SchoolClass::find($request->query('class'));
            if ($class) {
                $filename = 'class-'.preg_replace('/[^a-zA-Z0-9_-]+/', '-', $class->name).'-invoices.pdf';
            }
        }

        $pdf = Pdf::loadView('invoices.all-students-pdf', compact('allInvoices', 'school', 'bankAccounts'));

        return $pdf->download($filename);
    }

    public function exportClassInvoicesPdf($className, Request $request)
    {
        $class = SchoolClass::where('name', $className)->firstOrFail();
        $request->merge(['class' => $class->id]);

        return $this->exportAllStudentsInvoicesPdf($request);
    }

    public function exportStudentInvoicePdf($studentId, Request $request)
    {
        $student = Student::with(['schoolClass', 'particulars', 'scholarships'])->findOrFail($studentId);
        $school = SchoolSetting::getSettings();
        $invoiceData = $this->buildStudentInvoiceData($student);
        $bankAccounts = BankAccount::all();

        $pdf = Pdf::loadView('invoices.student-pdf', compact('student', 'school', 'invoiceData', 'bankAccounts'));

        return $pdf->download("student-{$studentId}-invoice.pdf");
    }

    public function exportAllStudentsLedgersPdf(Request $request)
    {
        $school = SchoolSetting::getSettings();
        $dateFrom = $request->get('from_date');
        $dateTo = $request->get('to_date');

        // Get all active students (schema uses status, not is_active)
        $students = Student::with('schoolClass')->where('status', 'active')->get();

        $allLedgers = [];

        foreach ($students as $student) {
            // Calculate opening balance
            $openingBalance = 0;
            if ($dateFrom) {
                $openingBalance = Voucher::where('student_id', $student->id)
                    ->where('date', '<', $dateFrom)
                    ->selectRaw('SUM(debit) - SUM(credit) as balance')
                    ->value('balance') ?? 0;
            }

            // Get vouchers
            $query = Voucher::where('student_id', $student->id)
                ->with(['particular', 'book']);

            if ($dateFrom) {
                $query->where('date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('date', '<=', $dateTo);
            }

            $vouchers = $query->orderBy('date')->get();

            $totalDebit = $vouchers->sum('debit');
            $totalCredit = $vouchers->sum('credit');
            $balance = $openingBalance + $totalDebit - $totalCredit;

            $ledgerData = $vouchers->map(function ($voucher) use (&$runningBalance) {
                return [
                    'date' => $voucher->date,
                    'particular' => $voucher->particular?->name ?? 'N/A',
                    'voucher_type' => $voucher->voucher_type,
                    'voucher_number' => $voucher->voucher_number,
                    'debit' => $voucher->debit,
                    'credit' => $voucher->credit,
                    'balance' => 0, // Calculated in view or we can calculate here if needed better
                ];
            });

            // Re-calculate running balance for the array
            $running = $openingBalance;
            $ledgerData = $ledgerData->map(function ($item) use (&$running) {
                $running += $item['debit'] - $item['credit'];
                $item['balance'] = $running;

                return $item;
            });

            // Only add students with transactions or balances?
            // User likely wants all students or at least those with activity.
            // For now, let's include all to be safe, or maybe filter?
            // "Download all students ledger" implies all.

            $allLedgers[] = [
                'student' => $student,
                'ledgerData' => $ledgerData,
                'totalDebit' => $totalDebit,
                'totalCredit' => $totalCredit,
                'balance' => $balance,
                'openingBalance' => $openingBalance,
            ];
        }

        $pdf = Pdf::loadView('ledgers.all-students-pdf', compact('allLedgers', 'school'));

        return $pdf->download('all-students-ledgers.pdf');
    }
}
