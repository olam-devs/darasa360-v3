<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Fee Statement - {{ $student->name }}</title>
    <style>
        @include('invoices.partials.student-invoice-styles')
        .student-invoice-page { page-break-after: auto; page-break-inside: avoid; }
    </style>
</head>
<body>
    <div class="student-invoice-page">
        @include('components.pdf-header', ['school' => $school ?? null])
        @include('invoices.partials.student-invoice-body', compact('student', 'invoiceData', 'bankAccounts'))
    </div>
</body>
</html>
