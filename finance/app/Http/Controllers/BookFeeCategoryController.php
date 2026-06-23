<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookFeeCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookFeeCategoryController extends Controller
{
    public function index($bookId)
    {
        $book = Book::findOrFail($bookId);
        $categories = $book->feeCategories()->with(['tiers'])->get();

        return response()->json($categories);
    }

    public function store(Request $request, $bookId)
    {
        $book = Book::findOrFail($bookId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'tiers' => 'required|array|min:1',
            'tiers.*.amount_from' => 'required|numeric|min:0',
            'tiers.*.amount_to' => 'nullable|numeric|min:0',
            'tiers.*.fee_amount' => 'required|numeric|min:0',
        ]);

        foreach ($validated['tiers'] as $i => $row) {
            $from = (float) $row['amount_from'];
            $to = isset($row['amount_to']) && $row['amount_to'] !== '' && $row['amount_to'] !== null ? (float) $row['amount_to'] : null;
            if ($to !== null && $to < $from) {
                throw ValidationException::withMessages([
                    "tiers.{$i}.amount_to" => 'amount_to must be greater than or equal to amount_from.',
                ]);
            }
        }

        $tiers = $validated['tiers'];
        unset($validated['tiers']);

        $category = $book->feeCategories()->create($validated);
        foreach (array_values($tiers) as $i => $row) {
            $category->tiers()->create([
                'amount_from' => $row['amount_from'],
                'amount_to' => $row['amount_to'] ?? null,
                'fee_amount' => $row['fee_amount'],
                'sort_order' => $i,
            ]);
        }

        return response()->json($category->load(['tiers']), 201);
    }

    public function update(Request $request, $bookId, $categoryId)
    {
        $book = Book::findOrFail($bookId);
        $category = BookFeeCategory::where('book_id', $book->id)->findOrFail($categoryId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'tiers' => 'required|array|min:1',
            'tiers.*.amount_from' => 'required|numeric|min:0',
            'tiers.*.amount_to' => 'nullable|numeric|min:0',
            'tiers.*.fee_amount' => 'required|numeric|min:0',
        ]);

        foreach ($validated['tiers'] as $i => $row) {
            $from = (float) $row['amount_from'];
            $to = isset($row['amount_to']) && $row['amount_to'] !== '' && $row['amount_to'] !== null ? (float) $row['amount_to'] : null;
            if ($to !== null && $to < $from) {
                throw ValidationException::withMessages([
                    "tiers.{$i}.amount_to" => 'amount_to must be greater than or equal to amount_from.',
                ]);
            }
        }

        $tiers = $validated['tiers'];
        unset($validated['tiers']);

        $category->update($validated);
        $category->tiers()->delete();
        foreach (array_values($tiers) as $i => $row) {
            $category->tiers()->create([
                'amount_from' => $row['amount_from'],
                'amount_to' => $row['amount_to'] ?? null,
                'fee_amount' => $row['fee_amount'],
                'sort_order' => $i,
            ]);
        }

        return response()->json($category->fresh(['tiers']));
    }

    public function destroy($bookId, $categoryId)
    {
        $book = Book::findOrFail($bookId);
        $category = BookFeeCategory::where('book_id', $book->id)->findOrFail($categoryId);
        $category->tiers()->delete();
        $category->delete();

        return response()->json(['message' => 'Fee category deleted']);
    }
}
