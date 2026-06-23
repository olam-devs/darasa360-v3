<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SchoolClass extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'code',
        'level',
        'capacity',
        'description',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    // Helper methods
    public function getStudentCount()
    {
        return $this->students()->where('status', 'active')->count();
    }
}
