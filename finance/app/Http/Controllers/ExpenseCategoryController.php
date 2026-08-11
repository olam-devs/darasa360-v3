<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpenseCategory::query()->orderBy('name');

        if ($request->boolean('approved_only')) {
            $query->approved();
        }

        return response()->json(['categories' => $query->get()]);
    }

    /**
     * Any accountant may propose a category. Auto-approved if the proposer
     * is this school's main accountant, otherwise sits pending until one
     * reviews it.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name',
        ]);

        $user = $request->user();
        $isMain = (bool) ($user->is_main_accountant ?? false);

        $category = ExpenseCategory::create([
            'name' => $validated['name'],
            'status' => $isMain ? 'approved' : 'pending',
            'proposed_by' => $user->id,
            'decided_by' => $isMain ? $user->id : null,
            'decided_at' => $isMain ? now() : null,
        ]);

        return response()->json(['category' => $category], 201);
    }

    public function pendingList()
    {
        return response()->json([
            'categories' => ExpenseCategory::pending()->orderBy('created_at')->get(),
        ]);
    }

    public function approve(Request $request, ExpenseCategory $category)
    {
        if ($category->status !== 'pending') {
            return response()->json(['error' => 'This category is not pending.'], 400);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255|unique:expense_categories,name,'.$category->id,
        ]);

        $category->update([
            'name' => $validated['name'] ?? $category->name,
            'status' => 'approved',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        return response()->json(['category' => $category]);
    }

    public function deny(Request $request, ExpenseCategory $category)
    {
        if ($category->status !== 'pending') {
            return response()->json(['error' => 'This category is not pending.'], 400);
        }

        $validated = $request->validate([
            'decision_note' => 'nullable|string',
        ]);

        $category->update([
            'status' => 'denied',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' => $validated['decision_note'] ?? null,
        ]);

        return response()->json(['category' => $category]);
    }

    public function update(Request $request, ExpenseCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,'.$category->id,
        ]);

        $category->update(['name' => $validated['name']]);

        return response()->json(['category' => $category]);
    }
}
