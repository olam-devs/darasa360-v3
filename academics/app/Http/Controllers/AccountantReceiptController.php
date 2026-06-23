<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountantReceiptController extends Controller
{
//     public function index()
// {
//     $receipts = Receipt::with(['payment.student', 'parent'])
//         ->orderBy('created_at', 'desc')
//         ->paginate(15);

//     $pendingCount = Receipt::where('status', 'pending')->count();
//     $verifiedCount = Receipt::where('status', 'verified')->count();
//     $rejectedCount = Receipt::where('status', 'rejected')->count();
//     $totalReceipts = Receipt::count();

//     return view('accountant.receipts', compact(
//         'receipts', 'pendingCount', 'verifiedCount', 
//         'rejectedCount', 'totalReceipts'
//     ));
// }

// public function verify(Request $request)
// {
//     $receipt = Receipt::findOrFail($request->receipt_id);
    
//     $receipt->update([
//         'status' => 'verified',
//         'verified_by' => Auth::id(),
//         'verified_at' => now()
//     ]);

//     // Update payment status
//     $receipt->payment->update(['status' => 'paid']);

//     return response()->json(['success' => true]);
// }

// public function reject(Request $request)
// {
//     $request->validate([
//         'receipt_id' => 'required|exists:receipts,id',
//         'rejection_reason' => 'required|string'
//     ]);

//     $receipt = Receipt::findOrFail($request->receipt_id);
    
//     $receipt->update([
//         'status' => 'rejected',
//         'rejection_reason' => $request->rejection_reason,
//         'verified_by' => Auth::id(),
//         'verified_at' => now()
//     ]);

//     return redirect()->back()->with('success', 'Receipt rejected successfully.');
// }
}
