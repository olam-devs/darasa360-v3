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
            'code' => 'nullable|string|unique:school_classes',
            'level' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = $this->generateUniqueCode($validated['name']);
        }

        $class = SchoolClass::create($validated);

        return response()->json($class, 201);
    }

    /**
     * The class create/edit form has no Code input (code is an internal reference
     * only shown in the list), so derive one from the name when not supplied.
     */
    private function generateUniqueCode(string $name): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
        $base = $base !== '' ? substr($base, 0, 10) : 'CLASS';

        $code = $base;
        $suffix = 1;
        while (SchoolClass::where('code', $code)->exists()) {
            $code = $base.$suffix;
            $suffix++;
        }

        return $code;
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
            'code' => 'nullable|string|unique:school_classes,code,' . $id,
            'level' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['code'])) {
            unset($validated['code']);
        }

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
