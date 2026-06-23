<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyAttendanceEntry extends Model
{
  use HasFactory;

  protected $fillable = [
    'attendance_id',
    'student_id',
    'date',
    'status',
    'remarks',
  ];

  protected $table = 'attendance_entries';

  /**
   * The attendance session this entry belongs to.
   */
  public function attendance()
  {
    return $this->belongsTo(Attendance::class);
  }

  /**
   * The student for this attendance entry.
   */
  public function student()
  {
    return $this->belongsTo(SchoolUser::class, 'student_id');
  }
}
