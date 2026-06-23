<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookMonthlyCut;
use App\Models\BookMonthlyCutRun;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookMonthlyCutController extends Controller
{
    public function index($bookId)
    {
        $book = Book::findOrFail($bookId);

        return response()->json($book->monthlyCuts()->get());
    }

    public function store(Request $request, $bookId)
    {
        $book = Book::findOrFail($bookId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'day_of_month' => 'required|integer|min:1|max:28',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);

        $cut = $book->monthlyCuts()->create($validated);

        return response()->json($cut, 201);
    }

    public function update(Request $request, $bookId, $cutId)
    {
        $book = Book::findOrFail($bookId);
        $cut = BookMonthlyCut::where('book_id', $book->id)->findOrFail($cutId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'day_of_month' => 'required|integer|min:1|max:28',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);

        $cut->update($validated);

        return response()->json($cut->fresh());
    }

    public function destroy($bookId, $cutId)
    {
        $book = Book::findOrFail($bookId);
        $cut = BookMonthlyCut::where('book_id', $book->id)->findOrFail($cutId);
        $cut->runs()->delete();
        $cut->delete();

        return response()->json(['message' => 'Monthly cut deleted']);
    }

    /**
     * Apply a monthly cut immediately for a given date (defaults to today).
     */
    public function apply(Request $request, $bookId, $cutId)
    {
        $book = Book::findOrFail($bookId);
        $cut = BookMonthlyCut::where('book_id', $book->id)->where('is_active', true)->findOrFail($cutId);

        $validated = $request->validate([
            'date' => 'nullable|date',
        ]);

        $date = $validated['date'] ?? now()->toDateString();
        $year = (int) Carbon::parse($date)->format('Y');
        $month = (int) Carbon::parse($date)->format('n');

        $existing = BookMonthlyCutRun::where('book_monthly_cut_id', $cut->id)->where('year', $year)->where('month', $month)->first();
        if ($existing) {
            return response()->json(['error' => 'This cut has already been applied for this month.'], 400);
        }

        DB::beginTransaction();
        try {
            $voucher = Voucher::create([
                'date' => $date,
                'student_id' => null,
                'particular_id' => null,
                'book_id' => $book->id,
                'voucher_type' => 'Payment',
                'debit' => 0,
                'credit' => $cut->amount,
                'payment_by_receipt_to' => 'Monthly Bank Cut',
                'notes' => "Monthly cut: {$cut->name}".($cut->notes ? " ({$cut->notes})" : ''),
                'created_by' => auth()->id(),
            ]);

            BookMonthlyCutRun::create([
                'book_monthly_cut_id' => $cut->id,
                'year' => $year,
                'month' => $month,
                'voucher_id' => $voucher->id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Monthly cut applied', 'voucher' => $voucher], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
