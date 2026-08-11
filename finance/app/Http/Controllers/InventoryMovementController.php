<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
    /**
     * Movement log - filterable by item/type/date range. No approval
     * status to filter by; every row here already happened.
     */
    public function index(Request $request)
    {
        $query = InventoryMovement::with('item')->orderByDesc('movement_date')->orderByDesc('id');

        if ($itemId = $request->get('inventory_item_id')) {
            $query->where('inventory_item_id', $itemId);
        }
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($fromDate = $request->get('from_date')) {
            $query->where('movement_date', '>=', $fromDate);
        }
        if ($toDate = $request->get('to_date')) {
            $query->where('movement_date', '<=', $toDate);
        }

        return response()->json($query->paginate($request->get('per_page', 30)));
    }

    /**
     * Record a stock-in or stock-out. No approval workflow - independent
     * of Expense Planning by explicit design. A stock-out that would take
     * the item negative is rejected (can't issue more than is on hand).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0.001',
            'unit_cost' => 'nullable|numeric|min:0',
            'issued_to' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'movement_date' => 'required|date',
        ]);

        $item = InventoryItem::findOrFail($validated['inventory_item_id']);

        if ($validated['type'] === 'out') {
            $available = $item->currentQuantity();
            if ($validated['quantity'] > $available) {
                return response()->json([
                    'error' => "Cannot issue {$validated['quantity']} {$item->unit_type} - only {$available} {$item->unit_type} available.",
                ], 422);
            }
        }

        $movement = InventoryMovement::create($validated + ['created_by' => $request->user()->id]);

        return response()->json([
            'movement' => $movement->load('item'),
            'current_quantity' => $item->currentQuantity(),
        ], 201);
    }

    /**
     * Deletable to correct a mistake - no approval workflow means no
     * separate "reverse" flow either, a plain delete is the correction.
     */
    public function destroy(InventoryMovement $movement)
    {
        $movement->delete();

        return response()->json(['message' => 'Movement deleted.']);
    }

    public function exportCsv(Request $request)
    {
        $items = InventoryItem::orderBy('name')->get();

        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="inventory-stock-levels.csv"');

        fputcsv($handle, ['Item', 'Category', 'Unit', 'Current Quantity', 'Reorder Level', 'Low Stock']);

        foreach ($items as $item) {
            fputcsv($handle, [
                $item->name,
                $item->category,
                $item->unit_type,
                $item->currentQuantity(),
                $item->reorder_level,
                $item->isLowStock() ? 'Yes' : 'No',
            ]);
        }

        fclose($handle);
        exit;
    }
}
