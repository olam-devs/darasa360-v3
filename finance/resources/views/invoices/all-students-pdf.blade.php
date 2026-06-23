<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Fee Statements</title>
    <style>
        @include('invoices.partials.student-invoice-styles')
    </style>
</head>
<body>
    @foreach($allInvoices as $invoice)
        <div class="student-invoice-page">
            @include('components.pdf-header', ['school' => $school ?? null])
            @include('invoices.partials.student-invoice-body', [
                'student' => $invoice['student'],
                'invoiceData' => $invoice['invoiceData'],
                'bankAccounts' => $bankAccounts ?? collect(),
            ])
        </div>
    @endforeach
</body>
</html>
