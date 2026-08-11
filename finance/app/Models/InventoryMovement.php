<?php

namespace App\Models;

class InventoryMovement extends BaseModel
{
    protected $connection = 'tenant';

    protected $fillable = [
        'inventory_item_id',
        'type',
        'quantity',
        'unit_cost',
        'issued_to',
        'note',
        'movement_date',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'movement_date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
