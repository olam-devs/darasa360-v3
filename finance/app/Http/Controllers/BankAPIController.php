<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use App\Models\BankApiSetting;
use App\Models\SuspenseAccount;
use App\Models\Voucher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankAPIController extends Controller
{
    public function index(Request $request)
    {
        $query = BankTransaction::with(['student', 'book', 'suspenseAccount']);

        if ($request->has('status')) {
            $query->where('processing_status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        return response()->json($transactions);
    }

    public function show($id)
    {
        $transaction = BankTransaction::with(['student', 'book', 'voucher', 'suspenseAccount'])
            ->findOrFail($id);

        return response()->json($transaction);
    }

    public function webhook(Request $request)
    {
        try {
            Log::info('Bank webhook received', ['payload' => $request->all()]);

            $validated = $request->validate([
                'transaction_id' => 'required|string',
                'control_number' => 'nullable|string',
                'transaction_date' => 'required|date',
                'bank_name' => 'required|string',
                'account_number' => 'required|string',
                'amount' => 'required|numeric',
                'type' => 'required|in:debit,credit',
                'reference' => 'nullable|string',
                'description' => 'nullable|string',
                'payer_name' => 'nullable|string',
                'payer_phone' => 'nullable|string',
            ]);

            DB::beginTransaction();
            try {
                $transaction = BankTransaction::create(array_merge($validated, [
                    'is_reconciled' => false,
                    'processing_status' => 'pending',
                    'sms_sent' => false,
                ]));

                // Try to auto-match with student
                $this->attemptAutoMatch($transaction);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Transaction received',
                    'transaction_id' => $transaction->id,
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Bank webhook processing failed', ['error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Bank webhook validation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Invalid payload',
            ], 400);
        }
    }

    protected function attemptAutoMatch($transaction)
    {
        // Try to find student by phone number or name
        $student = null;

        if ($transaction->payer_phone) {
            $student = Student::where('parent_phone_1', $transaction->payer_phone)
                ->orWhere('parent_phone_2', $transaction->payer_phone)
                ->first();
        }

        if (!$student && $transaction->payer_name) {
            $student = Student::where('name', 'LIKE', "%{$transaction->payer_name}%")
                ->first();
        }

        if ($student) {
            $transaction->update([
                'student_id' => $student->id,
                'processing_status' => 'matched',
            ]);
        } else {
            $transaction->update([
                'processing_status' => 'pending',
            ]);
        }
    }

    public function retry(Request $request, $id)
    {
        $transaction = BankTransaction::findOrFail($id);

        DB::beginTransaction();
        try {
            $this->attemptAutoMatch($transaction);

            DB::commit();

            return response()->json([
                'message' => 'Retry completed',
                'transaction' => $transaction->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSettings()
    {
        $settings = BankApiSetting::all();
        return response()->json($settings);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string',
            'api_url' => 'nullable|string',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'is_active' => 'boolean',
            'use_simulation' => 'boolean',
            'webhook_secret' => 'nullable|string',
        ]);

        $settings = BankApiSetting::updateOrCreate(
            ['bank_name' => $validated['bank_name']],
            $validated
        );

        return response()->json($settings);
    }

    public function simulate(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payer_name' => 'required|string',
            'payer_phone' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        $simulatedPayload = [
            'transaction_id' => 'SIM-' . uniqid(),
            'control_number' => null,
            'transaction_date' => now()->toDateString(),
            'bank_name' => 'Simulated Bank',
            'account_number' => '1234567890',
            'amount' => $validated['amount'],
            'type' => 'credit',
            'reference' => $validated['reference'] ?? '',
            'description' => 'Simulated transaction for testing',
            'payer_name' => $validated['payer_name'],
            'payer_phone' => $validated['payer_phone'] ?? '',
        ];

        return $this->webhook(new Request($simulatedPayload));
    }
}
