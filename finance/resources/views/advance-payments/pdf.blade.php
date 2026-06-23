<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Advance Payments</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 6px; }
        .sub { text-align: center; font-size: 12px; color: #444; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #e8fff7; text-align: left; }
        .right { text-align: right; }
        .muted { color: #666; }
    </style>
</head>
<body>
    @include('components.pdf-header', ['school' => $school ?? null])
    <div class="title">Advance Payments</div>
    <div class="sub">
        Generated: {{ $generatedAt }} @if(!empty($q)) • Filter: "{{ $q }}" @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Student</th>
                <th style="width: 15%;">Reg No</th>
                <th style="width: 25%;">Class</th>
                <th style="width: 20%;" class="right">Advance Balance (TSh)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $s)
                <tr>
                    <td>{{ $s->name }}</td>
                    <td>{{ $s->student_reg_no }}</td>
                    <td>{{ $s->schoolClass->name ?? $s->class ?? '-' }}</td>
                    <td class="right">{{ number_format((float) $s->advance_balance, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="muted">No students with advance balance.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="right">TOTAL</th>
                <th class="right">{{ number_format((float) $total, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>

