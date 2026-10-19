@php
    $short = fn($name) => \App\Http\Controllers\SchoolManagementController::shortName($name);
    $pendingCount = $available->sum('pending');
    $decidedCount = $available->sum(fn($s) => count($s['decided_items']));
    $statusStyles = ['approved' => ['Approved', '#dcfce7', '#166534'], 'partially_approved' => ['Partly approved', '#fef9c3', '#854d0e'],
                     'denied' => ['Denied', '#fee2e2', '#991b1b'], 'cancelled' => ['Cancelled', '#f1f5f9', '#475569']];
@endphp

{{-- Waiting for approval --}}
<section class="management-panel" data-select-group="pending">
    <h3>Waiting for your approval ({{ $pendingCount }})</h3>
    <p class="management-muted" style="margin:8px 0 14px;">{{ $selectedSchool ? 'Pending expenses for this school.' : 'All schools in one list.' }} Tick expenses to see their total and approve them together, or use Review to check items, change amounts or add a note. This list shows everything pending, whatever period is selected above.</p>
    @if($pendingCount > 0)
    <form method="post" action="{{ route('accountant.management.expenses.bulk-approve') }}" id="bulk-approve-form" class="management-bulk">
        @csrf
        @if($selectedSchool)<input type="hidden" name="school" value="{{ $selectedSchool->id }}">@endif
        <div class="management-bulk-total"><span class="management-muted">Selected</span><strong><span data-sel-count>0</span> expense(s) · TSh <span data-sel-total>0</span></strong></div>
        <label>Pay from
            <select name="pay_from" id="bulk-pay-from">
                <option value="submitted">Book chosen by the accountant</option>
                @foreach(collect($bookGroups)->where('cash', false) as $g)<option value="account:{{ $g['account'] }}">{{ $g['name'] }} · {{ $g['account'] }}</option>@endforeach
                <option value="cash">Cash book (each school's own)</option>
            </select>
        </label>
        <button type="submit" class="management-primary" id="bulk-approve-button" disabled>Approve selected</button>
    </form>
    @endif
    <div class="management-scroll"><table class="management-table" id="management-approval-table">
        <thead><tr><th style="width:36px;"><input type="checkbox" data-select-all aria-label="Select all pending expenses" @disabled($pendingCount===0)></th><th>School / submitted</th><th>Expense</th><th>Book</th><th class="number">Requested (TSh)</th><th>Action</th></tr></thead>
        <tbody>
        @foreach($available as $school)
            @foreach($school['pending_items'] as $expense)
            <tr>
                <td><input type="checkbox" data-select-item form="bulk-approve-form" name="items[]" value="{{ $school['id'] }}:{{ $expense->id }}" data-amount="{{ (float)$expense->total_amount }}" data-has-book="{{ $expense->book_name ? 1 : 0 }}" aria-label="Select {{ $expense->submission_number }}"></td>
                <td>{{ $short($school['name']) }}<div class="management-muted">{{ \Carbon\Carbon::parse($expense->created_at)->format('d M Y') }}</div></td>
                <td><strong>{{ $expense->title ?: $expense->submission_number }}</strong><div class="management-muted">{{ $expense->category_name }} · {{ $expense->submission_number }}</div></td>
                <td>@if($expense->book_name){{ $expense->book_name }}@else<span class="management-muted">Not chosen</span>@endif</td>
                <td class="number">{{ number_format($expense->total_amount, 2) }}</td>
                <td><a class="management-primary" style="display:inline-block;" href="{{ route('accountant.management.expense', [$school['id'], $expense->id]) }}">Review</a></td>
            </tr>
            @endforeach
            @if($school['pending'] > 100)<tr><td colspan="6">{{ $school['name'] }}: showing the oldest 100.</td></tr>@endif
        @endforeach
        @if($pendingCount === 0)<tr><td colspan="6">Nothing is waiting for your approval.</td></tr>@endif
        </tbody>
    </table></div>
    @if($pendingCount > 0)<p class="management-muted" style="margin-top:10px;">Approve selected approves every item of each ticked expense as submitted, without a note, paying from the chosen account in that expense's own school. If an account does not have enough money, that expense is skipped and listed; the others are still approved.</p>@endif
</section>

{{-- History --}}
<section class="management-panel" data-select-group="history">
    <h3>History · {{ $periodLabel }} ({{ $decidedCount }})</h3>
    <p class="management-muted" style="margin:8px 0 14px;">Expenses approved or denied between {{ $from }} and {{ $to }} (by decision date). Change the period above to see other quarters. Tick rows to see their total; click an expense to open it.</p>
    <div class="management-bulk">
        <div class="management-bulk-total"><span class="management-muted">Selected</span><strong><span data-sel-count>0</span> expense(s) · TSh <span data-sel-total>0</span></strong></div>
        <label>Show
            <select id="history-status">
                <option value="">All decisions</option>
                @foreach($statusStyles as $key => [$label])<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select>
        </label>
    </div>
    <div class="management-scroll"><table class="management-table" id="management-history-table">
        <thead><tr><th style="width:36px;"><input type="checkbox" data-select-all aria-label="Select all shown history rows" @disabled($decidedCount===0)></th><th>School / decided</th><th>Expense</th><th>Book</th><th>Decision</th><th class="number">Amount (TSh)</th></tr></thead>
        <tbody>
        @foreach($available as $school)
            @foreach($school['decided_items'] as $expense)
            @php [$label, $bg, $fg] = $statusStyles[$expense->status] ?? [$expense->status, '#f1f5f9', '#475569']; @endphp
            <tr data-status="{{ $expense->status }}">
                <td><input type="checkbox" data-select-item data-amount="{{ (float)$expense->total_amount }}" aria-label="Select {{ $expense->submission_number }}"></td>
                <td>{{ $short($school['name']) }}<div class="management-muted">{{ \Carbon\Carbon::parse($expense->decided_at)->format('d M Y H:i') }}</div></td>
                <td><a href="{{ route('accountant.management.expense', [$school['id'], $expense->id]) }}" style="color:#1d4ed8;font-weight:700;">{{ $expense->title ?: $expense->submission_number }}</a><div class="management-muted">{{ $expense->category_name }} · {{ $expense->submission_number }} · spent {{ \Carbon\Carbon::parse($expense->transaction_date)->format('d M Y') }}</div></td>
                <td>{{ $expense->book_name ?: '—' }}</td>
                <td><span class="management-badge" style="background:{{ $bg }};color:{{ $fg }};">{{ $label }}</span><div class="management-muted">by {{ $deciderNames[$expense->decided_by] ?? 'Unknown' }}</div></td>
                <td class="number">{{ number_format($expense->total_amount, 2) }}</td>
            </tr>
            @endforeach
            @if(count($school['decided_items']) >= 300)<tr><td colspan="6">{{ $school['name'] }}: showing the latest 300 in this period. Choose a shorter period to see the rest.</td></tr>@endif
        @endforeach
        @if($decidedCount === 0)<tr><td colspan="6">No expenses were approved or denied in this period.</td></tr>@endif
        </tbody>
        @if($decidedCount > 0)
        <tfoot><tr><td></td><td colspan="4"><strong>Approved in this period</strong> <span class="management-muted">(approved + partly approved)</span></td>
            <td class="number"><strong>{{ number_format($available->sum(fn($s) => collect($s['decided_items'])->whereIn('status', ['approved', 'partially_approved'])->sum('total_amount')), 2) }}</strong></td></tr></tfoot>
        @endif
    </table></div>
</section>

<script>
(() => {
    const fmt = n => Math.round(n).toLocaleString('en-US');
    document.querySelectorAll('[data-select-group]').forEach(group => {
        const visible = () => [...group.querySelectorAll('[data-select-item]')].filter(cb => !cb.closest('tr').hidden);
        const all = group.querySelector('[data-select-all]');
        const button = group.querySelector('#bulk-approve-button');
        function update() {
            const picked = [...group.querySelectorAll('[data-select-item]:checked')];
            group.querySelector('[data-sel-count]').textContent = picked.length;
            group.querySelector('[data-sel-total]').textContent = fmt(picked.reduce((s, cb) => s + parseFloat(cb.dataset.amount || 0), 0));
            if (all) { const v = visible(); all.checked = v.length > 0 && v.every(cb => cb.checked); all.indeterminate = !all.checked && v.some(cb => cb.checked); }
            if (button) button.disabled = picked.length === 0;
        }
        group.addEventListener('change', e => {
            if (e.target.matches('[data-select-all]')) visible().forEach(cb => cb.checked = e.target.checked);
            update();
        });
        group.querySelector('#history-status')?.addEventListener('change', e => {
            group.querySelectorAll('tbody tr[data-status]').forEach(tr => {
                tr.hidden = e.target.value !== '' && tr.dataset.status !== e.target.value;
                if (tr.hidden) tr.querySelector('[data-select-item]').checked = false;
            });
            update();
        });
        update();
    });
    const form = document.getElementById('bulk-approve-form');
    form?.addEventListener('submit', e => {
        const picked = [...document.querySelectorAll('#management-approval-table [data-select-item]:checked')];
        const total = picked.reduce((s, cb) => s + parseFloat(cb.dataset.amount || 0), 0);
        const payFrom = document.getElementById('bulk-pay-from');
        const noBook = payFrom.value === 'submitted' ? picked.filter(cb => cb.dataset.hasBook !== '1').length : 0;
        let msg = `Approve ${picked.length} expense(s) for a total of TSh ${fmt(total)}, paid from: ${payFrom.selectedOptions[0].text}?`;
        if (noBook) msg += `\n\n${noBook} of them have no book chosen and will be skipped. Pick an account under "Pay from" to approve them.`;
        if (!window.confirm(msg)) { e.preventDefault(); return; }
        form.querySelector('button[type=submit]').disabled = true;
    });
})();
</script>
