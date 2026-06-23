<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FeeItemController extends Controller
{
    // Fee items are now managed as Particulars
    // This controller redirects to Particular operations

    public function index()
    {
        return redirect()->route('api.particulars.index');
    }

    public function store(Request $request)
    {
        return app(ParticularController::class)->store($request);
    }

    public function show($id)
    {
        return app(ParticularController::class)->show($id);
    }

    public function update(Request $request, $id)
    {
        return app(ParticularController::class)->update($request, $id);
    }

    public function destroy($id)
    {
        return app(ParticularController::class)->destroy($id);
    }
}
