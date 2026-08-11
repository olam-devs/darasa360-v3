<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expense Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        .report-title { font-size: 16px; font-weight: bold; text-align: center; margin-bottom: 4px; }
        .report-sub { text-align: center; color: #555; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f0f0f0; border: 1px solid #333; padding: 6px; font-size: 9px; font-weight: bold; text-align: left; }
        td { border: 1px solid #999; padding: 5px; font-size: 9px; }
        .amount { text-align: right; }
        .status-approved { color: #5cb85c; font-weight: bold; }
        .status-partially_approved { color: #f0ad4e; font-weight: bold; }
        .status-denied { color: #d9534f; font-weight: bold; }
        .status-cancelled { color: #999; }
        tfoot td { font-weight: bold; background-color: #f7f7f7; }
    </style>
</head>
<body>
    <div class="report-title">{{ $school->school_name ?? 'School' }} — Expense Report</div>
    <div class="report-sub">
        Academic Year: {{ $academicYear->name }}
        @if(!empty($validated['from_date']) || !empty($validated['to_date']))
            &nbsp;|&nbsp; {{ $validated['from_date'] ?? '...' }} to {{ $validated['to_date'] ?? '...' }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Submission #</th>
                <th>Date</th>
                <th>Category</th>
                <th>Title</th>
                <th>Total (TSh)</th>
                <th>Status</th>
                <th>Decided At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($submissions as $submission)
                <tr>
                    <td>{{ $submission->submission_number }}</td>
                    <td>{{ $submission->transaction_date?->format('Y-m-d') }}</td>
                    <td>{{ $submission->category?->name }}</td>
                    <td>{{ $submission->title }}</td>
                    <td class="amount">{{ number_format($submission->total_amount, 2) }}</td>
                    <td class="status-{{ $submission->status }}">{{ ucfirst(str_replace('_', ' ', $submission->status)) }}</td>
                    <td>{{ $submission->decided_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;">No expenses found for this filter.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total</td>
                <td class="amount">{{ number_format($submissions->sum('total_amount'), 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
