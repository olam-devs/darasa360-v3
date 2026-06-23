<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::withCount('students')
            ->orderBy('display_order')
            ->get();

        return response()->json($classes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:school_classes',
            'code' => 'required|string|unique:school_classes',
            'level' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
        ]);

        $class = SchoolClass::create($validated);

        return response()->json($class, 201);
    }

    public function show($id)
    {
        $class = SchoolClass::withCount('students')->findOrFail($id);
        return response()->json($class);
    }

    public function update(Request $request, $id)
    {
        $class = SchoolClass::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:school_classes,name,' . $id,
            'code' => 'required|string|unique:school_classes,code,' . $id,
            'level' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $class->update($validated);

        return response()->json($class);
    }

    public function destroy($id)
    {
        $class = SchoolClass::findOrFail($id);

        if ($class->students()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete class with existing students'
            ], 400);
        }

        $class->delete();

        return response()->json(['message' => 'Class deleted successfully']);
    }

    public function apiClasses()
    {
        $classes = SchoolClass::where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'name', 'code', 'level']);

        return response()->json($classes);
    }
}
