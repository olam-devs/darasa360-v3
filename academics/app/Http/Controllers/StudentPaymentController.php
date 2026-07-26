<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentPaymentController extends Controller
{
    public function paymentHome(Request $request)
    {

        return view('content.dashboard.student.payments.paymentHome');

        // if (!(new HelperController)->isLoggedIn($request)) {
        // return redirect('/login');
        // }
    
        // $toJson = $request->input('toJson', false);
        // $userId = $request->input('user_id');
        // $schoolCode = $request->input('school_code');
    
        // if ($toJson && $userId && $schoolCode) {
        // // Mobile/API request
        // $school = School::where('school_code', $schoolCode)->first();
        // if (!$school) {
        //     return response()->json(['status' => 'error', 'message' => 'Invalid school code'], 400);
        // }
        // $this->configureTenantConnection($school->database_url);
        // } else {
        // // Web/session request
        // if (!session()->has('user_id') || !session()->has('school_db')) {
        //     return redirect()->route('login')->withErrors('Session expired. Please login again.');
        // }
        // $userId = session('user_id');
        // $schoolDb = session('school_db');
        // $this->configureTenantConnection($schoolDb);
        // }s
    
        // $userRole = $toJson ? $request->input('role') : session('role');
    
        // if ($userRole !== 'Student') {
        // return redirect()->route('dashboard')->withErrors('Access denied. Only students can access payments.');
        // }
    
        // // Fetch student details
        // $student = Student::on('tenant')->where('user_id', $userId)->first();
        // if (!$student) {
        // return redirect()->route('dashboard')->withErrors('Student record not found.');
        // }

        // return view('students.payments.index', ['student' => $student]);
    }



      protected function configureTenantConnection($databaseName)
  {
    $school = \App\Models\School::where('database_url', $databaseName)->first();
    if ($school) {
      $school->useAsTenant();
    }
  }
    
        // Here you would typically fetch payment records related to the student
        // For demonstration, we'll just pass the student
}
