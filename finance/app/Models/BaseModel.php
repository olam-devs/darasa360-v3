<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Tenant models: JSON/array dates as Y-m-d (no time) for API and UI lists.
 */
abstract class BaseModel extends Model
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return Carbon::instance($date)->format('Y-m-d');
    }
}
