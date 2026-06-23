<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    // Invoices are now managed through Ledgers
    // This controller redirects to Ledger operations

    public function index()
    {
        return redirect()->route('accountant.invoices-page');
    }

    public function show($id)
    {
        return redirect()->route('api.ledgers.student', $id);
    }

    public function downloadPDF($id)
    {
        return redirect()->route('accountant.invoices.student.pdf', $id);
    }
}
