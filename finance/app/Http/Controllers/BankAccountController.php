<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $bankAccounts = BankAccount::orderBy('bank_name')->get();

        return response()->json(['bank_accounts' => $bankAccounts]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
        ]);

        // The Add Bank Account form only collects bank name + account number;
        // account_name has no dedicated field anywhere in the UI, so derive it
        // from bank_name (same pattern as school_classes.code auto-derivation).
        $validated['account_name'] = $validated['bank_name'];

        $bankAccount = BankAccount::create($validated);

        return response()->json($bankAccount, 201);
    }

    public function update(Request $request, $id)
    {
        $bankAccount = BankAccount::findOrFail($id);

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
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
