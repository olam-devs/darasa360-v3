<?php

namespace App\Http\Controllers;

use App\Models\ExpenseItem;
use Illuminate\Http\Request;

class ExpenseItemController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpenseItem::query()->orderBy('name');

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return response()->json([
            'items' => $query->limit(50)->get(['id', 'name', 'unit_type']),
        ]);
    }

    /**
     * No approval needed - any accountant can add a catalog item. Simple
     * case-insensitive duplicate guard so "Diesel"/"diesel" don't both exist.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit_type' => 'required|string|max:100',
        ]);

        $existing = ExpenseItem::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])->first();
        if ($existing) {
            return response()->json(['item' => $existing]);
        }

        $item = ExpenseItem::create($validated + ['created_by' => $request->user()->id]);

        return response()->json(['item' => $item], 201);
    }

    public function priceHistory(ExpenseItem $item)
    {
        return response()->json(['history' => $item->priceHistory()]);
    }
}
