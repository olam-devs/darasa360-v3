<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentTemplate extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'comment_templates';

    protected $fillable = [
        'stakeholder_type',
        'name',
        'content',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Stakeholder types constant
    const STAKEHOLDER_ACCOUNTANT = 'accountant';
    const STAKEHOLDER_DISCIPLINE = 'discipline';
    const STAKEHOLDER_CLASS_TEACHER = 'class_teacher';
    const STAKEHOLDER_SUBJECT_TEACHER = 'subject_teacher';
    const STAKEHOLDER_ACADEMIC = 'academic';

    // Get default template for a stakeholder type
    public static function getDefaultTemplate($stakeholderType)
    {
        return self::where('stakeholder_type', $stakeholderType)
            ->where('is_default', true)
            ->first();
    }

    // Get all templates for a stakeholder type
    public static function getTemplatesForStakeholder($stakeholderType)
    {
        return self::where('stakeholder_type', $stakeholderType)->get();
    }
}
