<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $bankAccounts = BankAccount::orderBy('display_order')->get();
        return response()->json($bankAccounts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'display_order' => 'nullable|integer',
        ]);

        $settings = SchoolSetting::getSettings();
        $validated['school_setting_id'] = $settings->id;

        $bankAccount = BankAccount::create($validated);

        return response()->json($bankAccount, 201);
    }

    public function update(Request $request, $id)
    {
        $bankAccount = BankAccount::findOrFail($id);

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'display_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $bankAccount->update($validated);

        return response()->json($bankAccount);
    }

    public function destroy($id)
    {
        $bankAccount = BankAccount::findOrFail($id);
        $bankAccount->delete();

        return response()->json([
            'message' => 'Bank account deleted successfully'
        ]);
    }
}
