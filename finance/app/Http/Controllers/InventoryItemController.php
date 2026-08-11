<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    /**
     * Current stock list - quantity and low-stock flag always computed
     * live, never a stale cached column.
     */
    public function index(Request $request)
    {
        $query = InventoryItem::query()->orderBy('name');

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where('name', 'like', '%'.$search.'%');
        }
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        $items = $query->get()->map(function (InventoryItem $item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'unit_type' => $item->unit_type,
                'category' => $item->category,
                'reorder_level' => $item->reorder_level,
                'current_quantity' => $item->currentQuantity(),
                'is_low_stock' => $item->isLowStock(),
            ];
        });

        return response()->json(['items' => $items]);
    }

    /**
     * No approval needed - independent of Expense Planning by design. Any
     * accountant can add a catalog item, matching ExpenseItem's convention.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit_type' => 'required|string|max:100',
            'category' => 'nullable|string|max:255',
            'reorder_level' => 'nullable|numeric|min:0',
        ]);

        $existing = InventoryItem::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])->first();
        if ($existing) {
            return response()->json(['error' => 'An item with this name already exists.'], 422);
        }

        $item = InventoryItem::create($validated + ['created_by' => $request->user()->id]);

        return response()->json(['item' => $item], 201);
    }

    public function update(Request $request, InventoryItem $item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit_type' => 'required|string|max:100',
            'category' => 'nullable|string|max:255',
            'reorder_level' => 'nullable|numeric|min:0',
        ]);

        $duplicate = InventoryItem::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->where('id', '!=', $item->id)
            ->first();
        if ($duplicate) {
            return response()->json(['error' => 'Another item already has this name.'], 422);
        }

        $item->update($validated);

        return response()->json(['item' => $item]);
    }

    /**
     * Only deletable with no movement history - protects the audit trail
     * once an item has real stock activity recorded against it.
     */
    public function destroy(InventoryItem $item)
    {
        if ($item->movements()->exists()) {
            return response()->json(['error' => 'Cannot delete an item with recorded stock movements.'], 400);
        }

        $item->delete();

        return response()->json(['message' => 'Item deleted.']);
    }

    public function categories()
    {
        $categories = InventoryItem::whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json(['categories' => $categories]);
    }
}
