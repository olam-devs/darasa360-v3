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

        $isMain = (bool) ($request->user()->is_main_accountant ?? false);
        $items = $query->limit(200)->get(['id', 'name', 'unit_type', 'item_type']);

        // For the main accountant, include last approved price per item so the
        // compose form can auto-fill unit price when a known item is selected.
        if ($isMain) {
            $items = $items->map(function (ExpenseItem $item) {
                $last = $item->priceHistory(1)->first();
                $item->last_price = $last ? (float) $last['unit_price'] : null;
                return $item;
            });
        }

        return response()->json(['items' => $items]);
    }

    /**
     * No approval needed - any accountant can add a catalog item. Simple
     * case-insensitive duplicate guard so "Diesel"/"diesel" don't both exist.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'unit_type' => 'nullable|string|max:100',
            'item_type' => 'nullable|in:goods,service',
        ]);
        if (empty($validated['item_type'])) {
            $validated['item_type'] = 'goods';
        }

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
