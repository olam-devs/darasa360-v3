<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Stream;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassController extends Controller
{
    private function switchTenantDB()
    {
        $tenantDb = session('school_db');
        if (!$tenantDb) {
            abort(404, 'Tenant DB not found in session.');
        }
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    public function index(Request $request)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
        $this->switchTenantDB();

        // Eager load streams and subjects for each class
        $classes = Classroom::on('tenant')->with(['streams', 'subjects'])->get();

        // Load all subjects for use in modals
        $allSubjects = Subject::on('tenant')->orderBy('name')->get();

        return view('content.dashboard.owner.classes.index', compact('classes', 'allSubjects'));
    }



    public function store(Request $request)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
        $this->switchTenantDB();

        $request->validate([
            'name' => 'required|string',
        ]);

        try {
            // Check uniqueness manually on tenant DB
            $exists = DB::connection('tenant')->table('classes')
                ->where('name', $request->name)
                ->exists();

            if ($exists) {
                return back()->withErrors(['name' => 'Class name already exists.'])->withInput();
            }

            Classroom::on('tenant')->create(['name' => $request->name]);

            return back()->with('success', 'Class created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create class: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
        $this->switchTenantDB(); // IMPORTANT!

        $class = Classroom::on('tenant')->findOrFail($id);

        if ($request->has('add_stream') && $request->filled('new_stream')) {
            $stream = new Stream();
            $stream->name = $request->input('new_stream');
            $stream->class_id = $class->id;
            $stream->save();

            return redirect()->route('classes.index')
                ->with('success', "Stream '{$stream->name}' added successfully.");
        }

        $class->name = $request->input('name');
        $class->description = $request->input('description');
        $class->save();

        return redirect()->route('classes.index')
            ->with('success', 'Class updated successfully.');
    }

    public function delete(Request $request, $id)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
        $this->switchTenantDB();

        try {
            Classroom::on('tenant')->findOrFail($id)->delete();

            return back()->with('success', 'Class deleted.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete class: ' . $e->getMessage());
        }
    }

    // Stream management
    public function storeStream(Request $request)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
        $this->switchTenantDB();

        $request->validate([
            'name' => 'required|string',
            'class_id' => 'required|exists:tenant.classes,id',
        ]);

        try {
            Stream::on('tenant')->create([
                'name' => $request->name,
                'class_id' => $request->class_id,
            ]);

            return back()->with('success', 'Stream added.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to add stream: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStream(Request $request, $id)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
        $this->switchTenantDB();

        $request->validate([
            'name' => 'required|string',
        ]);

        try {
            $stream = Stream::on('tenant')->findOrFail($id);
            $stream->update(['name' => $request->name]);

            return back()->with('success', 'Stream updated.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update stream: ' . $e->getMessage())->withInput();
        }
    }

    public function deleteStream(Request $request, $streamId)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
        $this->switchTenantDB(); // IMPORTANT!

        $stream = Stream::on('tenant')->findOrFail($streamId);
        $streamName = $stream->name;
        $stream->delete();

        return redirect()->route('classes.index')
            ->with('success', "Stream '{$streamName}' deleted successfully.");
    }
}
