<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\SchoolSetting;
use App\Services\ActivityLogger;
use App\Traits\HasSchoolContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class ParentAuthController extends Controller
{
    use HasSchoolContext;

    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public function showLogin()
    {
        if (Session::has('parent_student_id')) {
            return redirect()->route('parent.dashboard');
        }

        $school = SchoolSetting::getSettings();
        return view('parent.login', compact('school'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'student_reg_no' => 'required|string',
            'portal_password' => 'required|string',
        ]);

        $student = Student::where('student_reg_no', trim($request->student_reg_no))->first();

        if (!$student) {
            return back()->withInput()->with('error', 'Student registration number not found. Please check and try again.');
        }

        // If no portal password has been set yet, block access and prompt admin
        if (empty($student->portal_password)) {
            return back()->withInput()->with('error', 'Portal access not yet activated for this student. Please contact the school office.');
        }

        // Verify password
        if (!Hash::check($request->portal_password, $student->portal_password)) {
            return back()->withInput()->with('error', 'Incorrect password. Please contact the school office if you have forgotten your password.');
        }

        // Success — start session
        Session::put('parent_student_id',   $student->id);
        Session::put('parent_student_name', $student->name);
        Session::put('parent_language',     $request->input('language', 'en'));

        $schoolId = $this->getSchoolId();
        if ($schoolId) {
            $this->activityLogger->logParentAction(
                $schoolId,
                $student->id,
                $student->name,
                'login',
                "Parent portal login for student: {$student->name} ({$student->student_reg_no})"
            );
        }

        return redirect()->route('parent.dashboard');
    }

    public function logout()
    {
        $schoolId  = $this->getSchoolId();
        $studentId = Session::get('parent_student_id');
        $studentName = Session::get('parent_student_name');

        if ($schoolId && $studentId) {
            $this->activityLogger->logParentAction(
                $schoolId,
                $studentId,
                $studentName ?? 'Unknown',
                'logout',
                "Parent portal logout for student: {$studentName}"
            );
        }

        Session::forget(['parent_student_id', 'parent_student_name', 'parent_language']);
        return redirect()->route('parent.login');
    }
}
