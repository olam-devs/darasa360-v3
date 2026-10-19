@extends('layouts.management')
@section('title', 'Review '.$submission->submission_number.' — School management')
@section('page_title', 'Expense review · '.\App\Http\Controllers\SchoolManagementController::shortName($school->name))
@section('content')
@include('admin.accountant.management.styles')
@php
    $pending = $submission->status === 'pending';
    $remaining = $budget > 0 ? $budget - $spent : null;
@endphp
<div class="management-page" style="max-width:1100px;">
    <p class="management-muted"><a href="{{ url()->previous() !== url()->current() && str_contains(url()->previous(), 'school-management') ? url()->previous().'#approvals' : route('accountant.management', ['school' => $school->id]).'#approvals' }}" style="color:#1d4ed8;font-weight:600;">← Back to approvals</a></p>
    <h2 style="margin-top:6px;">{{ $submission->title ?: $submission->submission_number }}</h2>
    <p class="management-muted">{{ $school->name }} · {{ $submission->submission_number }} · submitted by {{ $submitter }} on {{ $submission->created_at?->format('d M Y H:i') }}</p>

    @if(session('error'))<div class="management-notice" id="review-error">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="management-notice">{{ $errors->first() }}</div>@endif

    <div class="management-grid" style="margin-top:18px;">
        <div class="management-card"><p class="management-muted">Requested</p><div class="value">TSh {{ number_format($submission->total_amount, 0) }}</div><p class="management-muted">Spending date {{ $submission->transaction_date?->format('d M Y') }}</p></div>
        <div class="management-card"><p class="management-muted">Category</p><div class="value" style="font-size:18px;">{{ $submission->category?->name ?? '—' }}</div>
            <p class="management-muted">@if($remaining !== null)Budget left this year: TSh {{ number_format($remaining, 0) }} of {{ number_format($budget, 0) }}@else No budget set for this category @endif</p></div>
        <div class="management-card"><p class="management-muted">Paid from</p><div class="value" style="font-size:18px;">{{ $submission->book?->name ?? 'No book chosen' }}</div>
            @php $bookRow = collect($books)->firstWhere('id', $submission->book_id); @endphp
            <p class="management-muted">@if($bookRow)Book balance: TSh {{ number_format($bookRow['balance'], 0) }}@endif</p></div>
    </div>
    @if($submission->description)<div class="management-panel"><h3>Description</h3><p class="management-muted" style="white-space:pre-wrap;">{{ $submission->description }}</p></div>@endif

    @if(!$pending)
        <div class="management-panel">
            <h3>Already decided</h3>
            <p class="management-muted">Status: <strong>{{ str_replace('_', ' ', $submission->status) }}</strong>@if($decider) by {{ $decider }}@endif @if($submission->decided_at) on {{ $submission->decided_at->format('d M Y H:i') }}@endif.</p>
            @if($submission->decision_note)<p class="management-muted">Note: {{ $submission->decision_note }}</p>@endif
            @if($submission->voucher_id)<p class="management-muted">Paid from {{ $submission->book?->name ?? 'book #'.$submission->book_id }}@if($submission->bank_fee_amount) · transaction fee TSh {{ number_format($submission->bank_fee_amount, 0) }}@endif.</p>@endif
            <div class="management-scroll" style="margin-top:14px;"><table class="management-table" style="min-width:640px;">
                <thead><tr><th>Item</th><th class="number">Quantity</th><th class="number">Unit price (TSh)</th><th class="number">Line total</th><th>Decision</th></tr></thead>
                <tbody>@foreach($submission->lineItems as $line)
                    <tr><td><strong>{{ $line->item_name_snapshot }}</strong><div class="management-muted">{{ $line->unit_type_snapshot }}</div></td>
                        <td class="number">{{ rtrim(rtrim(number_format($line->quantity, 3, '.', ','), '0'), '.') }}</td>
                        <td class="number">{{ number_format($line->unit_price, 0) }}</td>
                        <td class="number">{{ number_format($line->line_total, 0) }}</td>
                        <td>{{ ucfirst($line->status) }}@if($line->denial_reason)<div class="management-muted">{{ $line->denial_reason }}</div>@endif</td></tr>
                @endforeach</tbody>
                <tfoot><tr><td colspan="3" style="text-align:right;font-weight:700;">{{ in_array($submission->status, ['approved', 'partially_approved']) ? 'Amount paid' : 'Amount' }}</td><td class="number" style="font-weight:750;">{{ number_format($submission->total_amount, 0) }}</td><td></td></tr></tfoot>
            </table></div>
        </div>
    @else
    <form method="post" action="{{ route('accountant.management.expense.decide', [$school->id, $submission->id]) }}" id="review-form" class="management-panel">
        @csrf
        <h3>Items</h3>
        <p class="management-muted" style="margin:6px 0 14px;">Approve or deny each item. You can correct the quantity or price before approving. Only approved items are paid.</p>
        <div class="management-scroll"><table class="management-table" id="review-lines" style="min-width:720px;">
            <thead><tr><th>Item</th><th class="number">Quantity</th><th class="number">Unit price (TSh)</th><th class="number">Line total</th><th>Decision</th></tr></thead>
            <tbody>
            @foreach($submission->lineItems as $i => $line)
                @php $old = old("line_items.$i", []); $status = $old['status'] ?? 'approved'; @endphp
                <tr data-line>
                    <td><strong>{{ $line->item_name_snapshot }}</strong><div class="management-muted">{{ $line->unit_type_snapshot }}</div>
                        <input type="hidden" name="line_items[{{ $i }}][id]" value="{{ $line->id }}"></td>
                    <td class="number"><input type="number" step="0.001" min="0.001" name="line_items[{{ $i }}][quantity]" value="{{ $old['quantity'] ?? rtrim(rtrim(number_format($line->quantity, 3, '.', ''), '0'), '.') }}" data-qty style="width:90px;border:1px solid #cbd5e1;border-radius:6px;padding:5px;text-align:right;"></td>
                    <td class="number"><input type="number" step="0.01" min="0" name="line_items[{{ $i }}][unit_price]" value="{{ $old['unit_price'] ?? (float)$line->unit_price }}" data-price style="width:120px;border:1px solid #cbd5e1;border-radius:6px;padding:5px;text-align:right;"></td>
                    <td class="number" data-total>{{ number_format($line->line_total, 0) }}</td>
                    <td>
                        <label style="margin-right:10px;"><input type="radio" name="line_items[{{ $i }}][status]" value="approved" @checked($status==='approved') data-status> Approve</label>
                        <label><input type="radio" name="line_items[{{ $i }}][status]" value="denied" @checked($status==='denied') data-status> Deny</label>
                        <input type="text" name="line_items[{{ $i }}][denial_reason]" value="{{ $old['denial_reason'] ?? '' }}" placeholder="Reason for denying" data-reason style="display:none;margin-top:6px;width:100%;border:1px solid #cbd5e1;border-radius:6px;padding:5px;">
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot><tr><td colspan="3" style="text-align:right;font-weight:700;">Amount to pay</td><td class="number" style="font-weight:750;" id="approved-total">0</td><td></td></tr></tfoot>
        </table></div>

        <div class="management-target" style="margin-top:20px;">
            <label>Pay from book
                <select name="book_id" id="review-book">
                    @foreach($books as $b)<option value="{{ $b['id'] }}" data-balance="{{ $b['balance'] }}" @selected((int)old('book_id', $submission->book_id) === $b['id'])>{{ $b['name'] }} · TSh {{ number_format($b['balance'], 0) }}</option>@endforeach
                </select>
            </label>
            <label>Transaction fee
                <select name="book_fee_category_id" id="review-fee"><option value="">None / book default</option></select>
            </label>
            <label>Category
                <select name="expense_category_id">
                    @foreach($categories as $c)<option value="{{ $c->id }}" @selected((int)old('expense_category_id', $submission->expense_category_id) === $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </label>
        </div>
        <p class="management-notice" id="balance-warning" hidden>The amount to pay is more than this book's balance. Approval will be refused until the book has enough money or you choose another book.</p>
        <label style="display:flex;flex-direction:column;gap:5px;font-size:13px;font-weight:600;margin-top:16px;">Decision note (optional, visible to the school)
            <textarea name="decision_note" rows="3" maxlength="2000" style="border:1px solid #cbd5e1;border-radius:7px;padding:9px 12px;">{{ old('decision_note') }}</textarea>
        </label>
        <input type="hidden" name="overall_decision" id="overall-decision" value="approve">
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;">
            <button type="submit" class="management-primary" data-decision="approve" id="approve-button">Approve selected items</button>
            <button type="submit" class="management-primary" data-decision="deny" style="background:#b91c1c;" id="deny-button">Deny whole request</button>
        </div>
    </form>
    @endif
</div>
@endsection
@if($pending)
@push('scripts')
<script>
(() => {
    const books = @json($books);
    const oldFee = @json(old('book_fee_category_id'));
    const form = document.getElementById('review-form'), book = document.getElementById('review-book'), fee = document.getElementById('review-fee');
    const fmt = n => Math.round(n).toLocaleString('en-US');
    function fillFees() {
        const b = books.find(x => String(x.id) === book.value);
        fee.innerHTML = '<option value="">None / book default</option>' + (b ? b.fees.map(f => `<option value="${f.id}">${f.name.replace(/</g,'&lt;')}</option>`).join('') : '');
        if (oldFee) fee.value = String(oldFee);
    }
    function recalc() {
        let total = 0;
        document.querySelectorAll('[data-line]').forEach(row => {
            const qty = parseFloat(row.querySelector('[data-qty]').value) || 0, price = parseFloat(row.querySelector('[data-price]').value) || 0;
            const approved = row.querySelector('[data-status][value="approved"]').checked;
            row.querySelector('[data-total]').textContent = fmt(qty * price);
            row.querySelector('[data-reason]').style.display = approved ? 'none' : 'block';
            row.style.opacity = approved ? '1' : '.55';
            if (approved) total += qty * price;
        });
        document.getElementById('approved-total').textContent = 'TSh ' + fmt(total);
        const balance = parseFloat(book.selectedOptions[0]?.dataset.balance || '0');
        document.getElementById('balance-warning').hidden = total <= balance;
        return total;
    }
    form.addEventListener('input', recalc); form.addEventListener('change', recalc);
    book.addEventListener('change', fillFees);
    let confirmed = false;
    form.querySelectorAll('[data-decision]').forEach(btn => btn.addEventListener('click', () => { document.getElementById('overall-decision').value = btn.dataset.decision; }));
    form.addEventListener('submit', e => {
        if (confirmed) return;
        e.preventDefault();
        if (!form.reportValidity()) return;
        const deny = document.getElementById('overall-decision').value === 'deny';
        const msg = deny ? 'Deny this whole request? Nothing will be paid.' : `Approve and record a payment of TSh ${fmt(recalc())} from ${book.selectedOptions[0].text.split(' · ')[0]}?`;
        if (window.confirm(msg)) { confirmed = true; form.querySelectorAll('button').forEach(b => b.disabled = true); form.submit(); }
    });
    fillFees(); recalc();
})();
</script>
@endpush
@endif
