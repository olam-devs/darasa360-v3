@foreach($pendingPayments as $payment)
<tr>
    <td>{{ $payment->full_name }}</td>
    <td>{{ $payment->class_name }}</td>
    <td>{{ $payment->fee_name }}</td>
    <td>{{ number_format($payment->amount_due, 2) }}</td>
    <td>{{ $payment->due_date }}</td>
     <td>
                                        <div class="d-flex gap-1">
                                            @if($payment->receipt_path)
                                            <button class="btn btn-sm btn-outline-success" 
                                                    onclick="downloadPendingPayment({{ $payment->id }})"
                                                    title="Download Receipt">
                                                <i class="bx bx-download"></i>
                                            </button>


                                            @else
                                                <span class="text-muted small">No receipt</span>
                                            @endif
                                        </div>
                                    </td>

<td>
    <div class="dropdown">
        @php
            $statusColors = [
                'paid' => 'success',
                'pending' => 'warning',
                'partial' => 'info',
                'rejected' => 'danger', // ✅ added rejected
            ];
            $badgeColor = $statusColors[$payment->status] ?? 'secondary';
        @endphp

        <!-- Badge that triggers dropdown -->
        <button class="btn btn-{{ $badgeColor }} btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            {{ strtoupper($payment->status) }}
        </button>

        <!-- Dropdown form -->
        <ul class="dropdown-menu p-3">
            <form action="{{ url('accountant/payments/'.$payment->id.'/status') }}" method="POST">
                @csrf
                @method('PUT')
                <select name="status" class="form-select mb-2">
                    <option value="paid" {{ $payment->status === 'paid' ? 'selected' : '' }}>PAID</option>
                    <option value="pending" {{ $payment->status === 'pending' ? 'selected' : '' }}>PENDING</option>
                    <option value="rejected" {{ $payment->status === 'rejected' ? 'selected' : '' }}>REJECTED</option> <!-- ✅ added -->
                </select>
                <button type="submit" class="btn btn-primary btn-sm w-100">Update</button>
            </form>
        </ul>
    </div>
</td>

</tr>
@endforeach

@if($pendingPayments->isEmpty())
<tr>
    <td colspan="6" class="text-center text-muted">No pending payments found.</td>
</tr>
@endif
