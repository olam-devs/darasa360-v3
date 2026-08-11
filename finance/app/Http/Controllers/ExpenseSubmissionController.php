<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Book;
use App\Models\BookFeeCategory;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Models\ExpenseLineItem;
use App\Models\ExpenseSubmission;
use App\Models\SchoolSetting;
use App\Services\ExpenseVoucherFactory;
use App\Support\DateBucketer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseSubmissionController extends Controller
{
    public function __construct(protected ExpenseVoucherFactory $voucherFactory)
    {
    }

    public function index(Request $request)
    {
        $query = ExpenseSubmission::with(['category', 'book'])->orderByDesc('transaction_date');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($categoryId = $request->get('category_id')) {
            $query->where('expense_category_id', $categoryId);
        }
        if ($bookId = $request->get('book_id')) {
            $query->where('book_id', $bookId);
        }
        if ($academicYearId = $request->get('academic_year_id')) {
            $query->where('academic_year_id', $academicYearId);
        }
        if ($fromDate = $request->get('from_date')) {
            $query->where('transaction_date', '>=', $fromDate);
        }
        if ($toDate = $request->get('to_date')) {
            $query->where('transaction_date', '<=', $toDate);
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    public function show(ExpenseSubmission $submission)
    {
        return response()->json([
            'submission' => $submission->load(['lineItems.item', 'category', 'book', 'academicYear']),
        ]);
    }

    /**
     * Any accountant composes a submission. Auto-approved (real voucher
     * created immediately) if the submitter is this school's main
     * accountant; otherwise it sits pending for review.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'book_id' => 'nullable|exists:books,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'transaction_date' => 'required|date',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'line_items' => 'required|array|min:1',
            'line_items.*.expense_item_id' => 'nullable|exists:expense_items,id',
            'line_items.*.new_item_name' => 'nullable|string|max:255',
            'line_items.*.new_item_unit_type' => 'nullable|string|max:100',
            'line_items.*.quantity' => 'required|numeric|min:0.001',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'book_fee_category_id' => 'nullable|exists:book_fee_categories,id',
        ]);

        $category = ExpenseCategory::findOrFail($validated['expense_category_id']);
        if ($category->status !== 'approved') {
            return response()->json(['error' => 'This category has not been approved yet.'], 422);
        }

        $user = $request->user();
        $isMain = (bool) ($user->is_main_accountant ?? false);

        if ($isMain && empty($validated['book_id'])) {
            return response()->json(['error' => 'A book is required to record this expense.'], 422);
        }

        try {
            $submission = DB::transaction(function () use ($validated, $user, $isMain) {
                $submission = ExpenseSubmission::create([
                    'expense_category_id' => $validated['expense_category_id'],
                    'book_id' => $validated['book_id'] ?? null,
                    'academic_year_id' => $validated['academic_year_id'],
                    'transaction_date' => $validated['transaction_date'],
                    'title' => $validated['title'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'status' => 'pending',
                    'submitted_by' => $user->id,
                ]);

                foreach ($validated['line_items'] as $lineData) {
                    $item = $this->resolveOrCreateItem($lineData, $user->id);

                    $submission->lineItems()->create([
                        'expense_item_id' => $item->id,
                        'item_name_snapshot' => $item->name,
                        'unit_type_snapshot' => $item->unit_type,
                        'quantity' => $lineData['quantity'],
                        'unit_price' => $lineData['unit_price'],
                        'status' => $isMain ? 'approved' : 'pending',
                    ]);
                }

                $total = $submission->recomputeTotal($isMain ? 'approved' : null);

                if ($isMain) {
                    $book = Book::with(['bankFeeTiers', 'bankFeeParticular'])->findOrFail($submission->book_id);
                    $feeCategory = $this->resolveFeeCategory($book->id, $validated['book_fee_category_id'] ?? null);
                    $this->voucherFactory->assertSufficientBalance($book, $total, $feeCategory);
                    $voucherData = $this->voucherFactory->create(
                        $book,
                        $submission->transaction_date->toDateString(),
                        $total,
                        $submission->title ?: $submission->submission_number,
                        $submission->description,
                        $user->id,
                        $feeCategory
                    );

                    $submission->update(array_merge($voucherData, [
                        'status' => 'approved',
                        'decided_by' => $user->id,
                        'decided_at' => now(),
                    ]));
                }

                return $submission;
            });
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['submission' => $submission->load('lineItems', 'category')], 201);
    }

    /**
     * Only while pending, and only by the submitter or the main accountant -
     * lets a regular accountant fix a typo before review; editing after a
     * decision goes through review() instead.
     */
    public function update(Request $request, ExpenseSubmission $submission)
    {
        $user = $request->user();
        $isMain = (bool) ($user->is_main_accountant ?? false);

        if ($submission->status !== 'pending') {
            return response()->json(['error' => 'Only pending submissions can be edited this way.'], 400);
        }
        if (!$isMain && $submission->submitted_by !== $user->id) {
            return response()->json(['error' => 'You can only edit your own pending submissions.'], 403);
        }

        $validated = $request->validate([
            'expense_category_id' => 'sometimes|exists:expense_categories,id',
            'book_id' => 'nullable|exists:books,id',
            'transaction_date' => 'sometimes|date',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $submission->update($validated);

        return response()->json(['submission' => $submission->fresh(['lineItems', 'category'])]);
    }

    /**
     * The review decision - main-accountant-only (route-gated). Accepts a
     * required decision note, per-line approve/deny + optional edits
     * (quantity/price/item), and an overall category/book reassignment.
     * Recomputes the total from approved lines only, then either denies
     * (no voucher) or creates the voucher(s) for the approved total.
     */
    public function review(Request $request, ExpenseSubmission $submission)
    {
        if ($submission->status !== 'pending') {
            return response()->json(['error' => 'This submission has already been decided.'], 400);
        }

        $validated = $request->validate([
            'decision_note' => 'required|string',
            'overall_decision' => 'required|in:approve,deny',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'book_id' => 'nullable|exists:books,id',
            'line_items' => 'required|array|min:1',
            'line_items.*.id' => 'required|exists:expense_line_items,id',
            'line_items.*.status' => 'required|in:approved,denied',
            'line_items.*.quantity' => 'nullable|numeric|min:0.001',
            'line_items.*.unit_price' => 'nullable|numeric|min:0',
            'line_items.*.expense_item_id' => 'nullable|exists:expense_items,id',
            'line_items.*.denial_reason' => 'nullable|string',
            'book_fee_category_id' => 'nullable|exists:book_fee_categories,id',
        ]);

        $user = $request->user();

        try {
            DB::transaction(function () use ($submission, $validated, $user) {
                $locked = ExpenseSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
                if ($locked->status !== 'pending') {
                    throw new \RuntimeException('This submission was already decided.');
                }

                if (!empty($validated['expense_category_id'])) {
                    $locked->expense_category_id = $validated['expense_category_id'];
                }
                if (!empty($validated['book_id'])) {
                    $locked->book_id = $validated['book_id'];
                }
                $locked->save();

                foreach ($validated['line_items'] as $lineData) {
                    $line = ExpenseLineItem::where('id', $lineData['id'])
                        ->where('expense_submission_id', $locked->id)
                        ->firstOrFail();

                    if (array_key_exists('quantity', $lineData) && $lineData['quantity'] !== null) {
                        $line->quantity = $lineData['quantity'];
                    }
                    if (array_key_exists('unit_price', $lineData) && $lineData['unit_price'] !== null) {
                        $line->unit_price = $lineData['unit_price'];
                    }
                    if (!empty($lineData['expense_item_id'])) {
                        $item = ExpenseItem::findOrFail($lineData['expense_item_id']);
                        $line->expense_item_id = $item->id;
                        $line->item_name_snapshot = $item->name;
                        $line->unit_type_snapshot = $item->unit_type;
                    }
                    $line->status = $lineData['status'];
                    $line->denial_reason = $lineData['status'] === 'denied' ? ($lineData['denial_reason'] ?? null) : null;
                    $line->save();
                }

                $approvedTotal = $locked->recomputeTotal('approved');
                $hasApproved = $locked->lineItems()->where('status', 'approved')->exists();
                $allApproved = !$locked->lineItems()->where('status', '!=', 'approved')->exists();

                if ($validated['overall_decision'] === 'deny' || !$hasApproved) {
                    $locked->update([
                        'status' => 'denied',
                        'decided_by' => $user->id,
                        'decided_at' => now(),
                        'decision_note' => $validated['decision_note'],
                    ]);

                    return;
                }

                if (!$locked->book_id) {
                    throw new \RuntimeException('A book must be selected before approving.');
                }

                $book = Book::with(['bankFeeTiers', 'bankFeeParticular'])->findOrFail($locked->book_id);
                $feeCategory = $this->resolveFeeCategory($book->id, $validated['book_fee_category_id'] ?? null);
                $this->voucherFactory->assertSufficientBalance($book, $approvedTotal, $feeCategory);
                $voucherData = $this->voucherFactory->create(
                    $book,
                    $locked->transaction_date->toDateString(),
                    $approvedTotal,
                    $locked->title ?: $locked->submission_number,
                    $locked->description,
                    $user->id,
                    $feeCategory
                );

                $locked->update(array_merge($voucherData, [
                    'status' => $allApproved ? 'approved' : 'partially_approved',
                    'decided_by' => $user->id,
                    'decided_at' => now(),
                    'decision_note' => $validated['decision_note'],
                ]));
            });
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['submission' => $submission->fresh(['lineItems.item', 'category'])]);
    }

    /**
     * Main-accountant review inbox. Price history is batched per distinct
     * item present in the queue, not queried once per line item.
     */
    public function pendingQueue()
    {
        $submissions = ExpenseSubmission::with(['lineItems.item', 'category', 'book'])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        $itemIds = $submissions->flatMap(fn (ExpenseSubmission $s) => $s->lineItems->pluck('expense_item_id'))->unique();
        $priceHistory = $itemIds->mapWithKeys(function ($id) {
            $item = ExpenseItem::find($id);

            return [$id => $item ? $item->priceHistory() : collect()];
        });

        return response()->json([
            'submissions' => $submissions,
            'price_history' => $priceHistory,
            'pending_categories' => ExpenseCategory::pending()->orderBy('created_at')->get(),
        ]);
    }

    /**
     * Every decided submission + its note - open to ALL accountants, not
     * just the main one (the school-wide transparency requirement).
     */
    public function schoolWideLog(Request $request)
    {
        $submissions = ExpenseSubmission::with(['category', 'lineItems'])
            ->whereNotNull('decided_at')
            ->orderByDesc('decided_at')
            ->paginate($request->get('per_page', 20));

        return response()->json($submissions);
    }

    /**
     * Main-accountant-only - reverses an approved submission's voucher(s),
     * tighter than the old Expenses module's unrestricted cancel since this
     * reverses real recorded money.
     */
    public function cancel(ExpenseSubmission $submission)
    {
        if (!in_array($submission->status, ['approved', 'partially_approved'], true)) {
            return response()->json(['error' => 'Only approved submissions can be cancelled.'], 400);
        }

        DB::transaction(function () use ($submission) {
            $this->voucherFactory->reverse($submission->voucher_id, $submission->bank_fee_voucher_id);
            $submission->update([
                'status' => 'cancelled',
                'voucher_id' => null,
                'bank_fee_voucher_id' => null,
                'bank_fee_amount' => null,
                'bank_fee_category_id' => null,
            ]);
        });

        return response()->json(['submission' => $submission->fresh()]);
    }

    public function analytics(Request $request)
    {
        $fromDate = $request->get('from_date') ?: now()->startOfMonth()->toDateString();
        $toDate = $request->get('to_date') ?: now()->endOfMonth()->toDateString();

        $lineItems = ExpenseLineItem::where('status', 'approved')
            ->whereHas('submission', fn ($q) => $q->whereBetween('transaction_date', [$fromDate, $toDate]))
            ->with('submission.category')
            ->get();

        $trend = DateBucketer::bucket(
            $fromDate,
            $toDate,
            $lineItems,
            fn (ExpenseLineItem $l) => $l->submission?->transaction_date,
            fn (ExpenseLineItem $l) => $l->line_total,
        );

        $categoryDistribution = $lineItems
            ->groupBy(fn (ExpenseLineItem $l) => $l->submission?->category?->name ?? 'Uncategorized')
            ->map(fn ($lines, $name) => ['name' => $name, 'amount' => (float) $lines->sum('line_total')])
            ->values();

        return response()->json([
            'expense_trend' => $trend,
            'category_distribution' => $categoryDistribution,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $validated = $this->validateReportFilters($request);
        $submissions = $this->filteredSubmissionsForReport($validated);
        $school = SchoolSetting::getSettings();
        $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);

        $pdf = Pdf::loadView('expenses.report-pdf', compact('submissions', 'school', 'academicYear', 'validated'));

        return $pdf->download('expense-report.pdf');
    }

    public function exportCsv(Request $request)
    {
        $validated = $this->validateReportFilters($request);
        $submissions = $this->filteredSubmissionsForReport($validated);

        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="expense-report.csv"');

        fputcsv($handle, ['Submission #', 'Date', 'Category', 'Title', 'Total (TSh)', 'Status', 'Decided At']);

        foreach ($submissions as $submission) {
            fputcsv($handle, [
                $submission->submission_number,
                $submission->transaction_date,
                $submission->category?->name,
                $submission->title,
                $submission->total_amount,
                $submission->status,
                $submission->decided_at,
            ]);
        }

        fclose($handle);
        exit;
    }

    protected function validateReportFilters(Request $request): array
    {
        return $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'category_id' => 'nullable|exists:expense_categories,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);
    }

    protected function filteredSubmissionsForReport(array $validated)
    {
        $query = ExpenseSubmission::with('category')
            ->where('academic_year_id', $validated['academic_year_id']);

        if (!empty($validated['category_id'])) {
            $query->where('expense_category_id', $validated['category_id']);
        }
        if (!empty($validated['from_date'])) {
            $query->where('transaction_date', '>=', $validated['from_date']);
        }
        if (!empty($validated['to_date'])) {
            $query->where('transaction_date', '<=', $validated['to_date']);
        }

        return $query->orderBy('transaction_date')->get();
    }

    /**
     * Scopes the main accountant's selected fee category to the book it's
     * actually being applied against - same guard the withdraw flow applies
     * implicitly via its `where('book_id', ...)` query - so a category from
     * a different book can never be used, and a deactivated category is
     * silently ignored rather than accepted.
     */
    protected function resolveFeeCategory(int $bookId, ?int $feeCategoryId): ?BookFeeCategory
    {
        if (!$feeCategoryId) {
            return null;
        }

        return BookFeeCategory::where('book_id', $bookId)
            ->where('is_active', true)
            ->find($feeCategoryId);
    }

    protected function resolveOrCreateItem(array $lineData, int $createdBy): ExpenseItem
    {
        if (!empty($lineData['expense_item_id'])) {
            return ExpenseItem::findOrFail($lineData['expense_item_id']);
        }

        $name = trim((string) ($lineData['new_item_name'] ?? ''));
        $unitType = trim((string) ($lineData['new_item_unit_type'] ?? ''));

        if ($name === '' || $unitType === '') {
            throw new \InvalidArgumentException('Each line item needs an existing item or a name + unit type to create a new one.');
        }

        $existing = ExpenseItem::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($existing) {
            return $existing;
        }

        return ExpenseItem::create([
            'name' => $name,
            'unit_type' => $unitType,
            'created_by' => $createdBy,
        ]);
    }
}
