<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Stream;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassSubjectController extends Controller
{
    private function switchTenantDB()
    {
        $tenantDb = session('school_db');
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    // Show all classes + subjects + streams for the page
    public function showAssignmentForm(Request $request)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
        $this->switchTenantDB();
        $classes = Classroom::on('tenant')->with(['streams', 'subjects'])->get();
        $allSubjects = Subject::on('tenant')->orderBy('name')->get();

        return view('content.dashboard.owner.classes.index', compact('classes', 'allSubjects'));
    }

    // Save assigned subjects to class
    public function saveAssignedSubjects(Request $request, $classroomId)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
        $this->switchTenantDB();

        $request->validate([
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:tenant.subjects,id',
        ]);

        $classroom = Classroom::on('tenant')->findOrFail($classroomId);
        $classroom->subjects()->sync($request->input('subjects', []));

        return redirect()->route('classes.index')->with('success', 'Subjects successfully assigned.');
    }




    // Delete a class
    public function destroy(Request $request, $id)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
        return 'here';
        // $this->switchTenantDB();

        // $classroom = Classroom::on('tenant')->findOrFail($id);
        // $classroom->delete();

        // return redirect()->route('classes.index')->with('success', 'Class deleted.');
    }
}
