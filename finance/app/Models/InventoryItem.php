<?php

namespace App\Models;

class InventoryItem extends BaseModel
{
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'unit_type',
        'category',
        'reorder_level',
        'created_by',
    ];

    protected $casts = [
        'reorder_level' => 'decimal:3',
    ];

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Always live-summed from movements - never cached, so editing or
     * deleting a movement can never leave a stale quantity behind.
     */
    public function currentQuantity(): float
    {
        $in = (float) $this->movements()->where('type', 'in')->sum('quantity');
        $out = (float) $this->movements()->where('type', 'out')->sum('quantity');

        return $in - $out;
    }

    public function isLowStock(): bool
    {
        if ($this->reorder_level === null) {
            return false;
        }

        return $this->currentQuantity() <= (float) $this->reorder_level;
    }
}
