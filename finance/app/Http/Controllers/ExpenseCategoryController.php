<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $activeYearId = (int) DB::table('academic_years')->where('is_active', 1)->value('id');

        $query = ExpenseCategory::query()
            ->withCount(['submissions as submission_count' => fn ($q) => $q->where('status', 'approved')])
            ->orderByDesc('submission_count')
            ->orderBy('name');

        if ($activeYearId) {
            $query->addSelect(DB::raw(
                "EXISTS (
                    SELECT 1 FROM expense_category_plans ecp
                    WHERE ecp.expense_category_id = expense_categories.id
                    AND ecp.academic_year_id = {$activeYearId}
                    AND ecp.from_date <= '{$today}'
                    AND ecp.to_date >= '{$today}'
                ) as has_active_plan"
            ))->addSelect(DB::raw(
                "(SELECT MAX(ecp2.to_date) FROM expense_category_plans ecp2
                  WHERE ecp2.expense_category_id = expense_categories.id
                  AND ecp2.academic_year_id = {$activeYearId}
                ) as latest_plan_to_date"
            ));
        }

        if ($request->boolean('approved_only')) {
            $query->approved();
        }

        return response()->json(['categories' => $query->get()]);
    }

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

    public function destroy(ExpenseCategory $category)
    {
        $hasApproved = $category->submissions()->where('status', 'approved')->exists();
        if ($hasApproved) {
            return response()->json(['error' => 'Cannot delete: this category has approved expenses recorded against it.'], 422);
        }

        $hasPending = $category->submissions()->whereIn('status', ['pending', 'partially_approved'])->exists();
        if ($hasPending) {
            return response()->json(['error' => 'Cannot delete: there are pending submissions in this category. Decide them first.'], 422);
        }

        $category->plans()->delete();
        $category->submissions()->delete();
        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}
