    {{-- Staff management: register, edit and move --}}
    <section class="management-panel" id="staff">
        <h3>Staff · register, edit or move</h3>
        <p class="management-muted" style="margin:6px 0 14px;">Register several people at once (rows without a name are ignored), edit anyone's details and standing deductions, or move someone to another school. Moving copies the person and their deductions to the new school and marks them inactive in the old one; salaries already paid stay in the old school's books.</p>

        <form method="post" action="{{ route('accountant.management.staff.register') }}" id="register-form" style="margin-bottom:24px;">
            @csrf
            @if($selected)<input type="hidden" name="return_school" value="{{ $selected->id }}">@endif
            <div class="management-scroll"><table class="management-table" style="min-width:980px;">
                <thead><tr><th>School</th><th>Name</th><th>Job title</th><th class="number">Monthly salary</th><th>Phone</th><th>Bank / mobile</th><th>Account no.</th><th>NSSF 10%</th><th class="number">PAYE (TSh)</th><th></th></tr></thead>
                <tbody id="register-rows">
                @foreach(range(0, 2) as $i)
                    <tr data-reg-row>
                        <td><select name="staff[{{ $i }}][school_id]" class="reg-input">@foreach($allSchools as $sc)<option value="{{ $sc->id }}" @selected($selected?->id === $sc->id)>{{ $short($sc->name) }}</option>@endforeach</select></td>
                        <td><input type="text" name="staff[{{ $i }}][name]" maxlength="255" class="reg-input" style="width:170px;"></td>
                        <td><input type="text" name="staff[{{ $i }}][position]" maxlength="255" class="reg-input" style="width:140px;"></td>
                        <td class="number"><input type="number" name="staff[{{ $i }}][monthly_salary]" min="0" step="1" class="reg-input" style="width:110px;text-align:right;"></td>
                        <td><input type="text" name="staff[{{ $i }}][phone]" maxlength="255" class="reg-input" style="width:110px;"></td>
                        <td><input type="text" name="staff[{{ $i }}][bank_name]" maxlength="255" class="reg-input" style="width:90px;" placeholder="CRDB"></td>
                        <td><input type="text" name="staff[{{ $i }}][bank_account]" maxlength="255" class="reg-input" style="width:120px;"></td>
                        <td style="text-align:center;"><input type="checkbox" name="staff[{{ $i }}][nssf]" value="1"></td>
                        <td class="number"><input type="number" name="staff[{{ $i }}][paye]" min="0" step="1" class="reg-input" style="width:90px;text-align:right;"></td>
                        <td><button type="button" data-remove-row title="Remove row" style="color:#b91c1c;font-weight:700;">✕</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div style="display:flex;gap:10px;margin-top:12px;">
                <button type="button" id="add-register-row" style="border:1px solid #cbd5e1;border-radius:8px;padding:9px 14px;background:#fff;font-size:13px;font-weight:600;">+ Add another row</button>
                <button class="management-primary" type="submit">Register staff</button>
            </div>
        </form>

        <div class="management-scroll"><table class="management-table" style="min-width:760px;">
            <thead><tr><th>Staff</th><th>School</th><th class="number">Salary</th><th>Standing deductions</th><th>Edit</th><th>Move to</th></tr></thead>
            <tbody>
            @forelse($staff as $s)
                @php $standing = $deductions[$s->key] ?? collect(); @endphp
                <tr><td><strong>{{ $s->name }}</strong><div class="management-muted">{{ $s->position }} · code {{ $s->staff_id }}</div></td>
                    <td style="white-space:nowrap;"><span style="display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px;background:{{ $dotOf($s->school_id) }};"></span>{{ $s->school }}</td>
                    <td class="number">{{ $money($s->monthly_salary) }}</td>
                    <td>@forelse($standing as $d)<span class="management-badge" style="background:#f1f5f9;color:#334155;margin:0 3px 3px 0;">{{ $d->name }} {{ $d->type === 'percentage' ? (float)$d->default_amount.'%' : $money($d->default_amount) }}</span>@empty<span class="management-muted">None</span>@endforelse</td>
                    <td><button type="button" data-edit-toggle="edit-{{ $s->key }}" style="border:1px solid #cbd5e1;border-radius:6px;padding:5px 10px;background:#f8fafc;font-size:12px;font-weight:600;">Edit</button></td>
                    <td><form method="post" action="{{ route('accountant.management.staff.move') }}" style="display:flex;gap:6px;" data-move-form>
                        @csrf<input type="hidden" name="key" value="{{ $s->key }}">@if($selected)<input type="hidden" name="return_school" value="{{ $selected->id }}">@endif
                        <select name="to_school_id" style="border:1px solid #cbd5e1;border-radius:6px;padding:5px;">@foreach($allSchools as $sc)@if($sc->id !== $s->school_id)<option value="{{ $sc->id }}">{{ $short($sc->name) }}</option>@endif @endforeach</select>
                        <button type="submit" style="border:1px solid #cbd5e1;border-radius:6px;padding:5px 10px;background:#f8fafc;font-size:12px;font-weight:600;" data-name="{{ $s->name }}">Move</button>
                    </form></td></tr>
                <tr id="edit-{{ $s->key }}" hidden><td colspan="6" style="background:#f8fafc;">
                    <form method="post" action="{{ route('accountant.management.staff.update') }}">
                        @csrf<input type="hidden" name="key" value="{{ $s->key }}">@if($selected)<input type="hidden" name="return_school" value="{{ $selected->id }}">@endif
                        <div class="management-target" style="margin-top:4px;">
                            <label>Name<input type="text" name="name" value="{{ $s->name }}" required maxlength="255"></label>
                            <label>Job title<input type="text" name="position" value="{{ $s->position }}" required maxlength="255"></label>
                            <label>Monthly salary (TSh)<input type="number" name="monthly_salary" value="{{ (float)$s->monthly_salary }}" required min="0" step="1"></label>
                            <label>Phone<input type="text" name="phone" value="{{ $s->phone }}" maxlength="255"></label>
                            <label>Bank / mobile<input type="text" name="bank_name" value="{{ $s->bank_name }}" maxlength="255"></label>
                            <label>Account number<input type="text" name="bank_account" value="{{ $s->bank_account }}" maxlength="255"></label>
                        </div>
                        <p style="font-size:13px;font-weight:700;margin:14px 0 6px;">Standing deductions</p>
                        @forelse($standing as $d)
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:13px;">
                                <span style="min-width:70px;font-weight:600;">{{ $d->name }}</span>
                                <input type="number" name="deductions[{{ $d->id }}][amount]" value="{{ (float)$d->default_amount }}" min="0" step="any" style="width:110px;border:1px solid #cbd5e1;border-radius:6px;padding:4px 6px;text-align:right;">
                                <span class="management-muted">{{ $d->type === 'percentage' ? '% of gross' : 'TSh' }}</span>
                                <label style="display:flex;align-items:center;gap:5px;color:#b91c1c;font-weight:600;margin-left:10px;"><input type="checkbox" name="deductions[{{ $d->id }}][remove]" value="1"> Remove</label>
                            </div>
                        @empty
                            <p class="management-muted">No standing deductions.</p>
                        @endforelse
                        @foreach(range(0, 1) as $n)
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:13px;">
                                <span class="management-muted" style="min-width:70px;">Add</span>
                                <input type="text" name="new_deductions[{{ $n }}][name]" placeholder="e.g. NSSF, PAYEE, loan" maxlength="255" style="width:150px;border:1px solid #cbd5e1;border-radius:6px;padding:4px 6px;">
                                <select name="new_deductions[{{ $n }}][type]" style="border:1px solid #cbd5e1;border-radius:6px;padding:4px;"><option value="fixed">TSh</option><option value="percentage">% of gross</option></select>
                                <input type="number" name="new_deductions[{{ $n }}][amount]" min="0" step="any" placeholder="amount" style="width:110px;border:1px solid #cbd5e1;border-radius:6px;padding:4px 6px;text-align:right;">
                            </div>
                        @endforeach
                        <div style="display:flex;gap:10px;margin-top:12px;"><button class="management-primary" type="submit">Save changes</button><button type="button" data-edit-toggle="edit-{{ $s->key }}" style="font-size:13px;color:#475569;">Cancel</button></div>
                    </form>
                </td></tr>
            @empty
                <tr><td colspan="6">No active staff yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
@push('scripts')
<script>
(() => {
    document.querySelectorAll('[data-move-form]').forEach(f => f.addEventListener('submit', e => {
        const to = f.querySelector('select').selectedOptions[0].text;
        if (!window.confirm(`Move ${f.querySelector('button').dataset.name} to ${to}? Salaries already paid stay in the current school.`)) e.preventDefault();
    }));
    document.querySelectorAll('[data-edit-toggle]').forEach(b => b.addEventListener('click', () => {
        const row = document.getElementById(b.dataset.editToggle); row.hidden = !row.hidden;
    }));
    // Register form: add rows (copies of the first, emptied) and remove rows; names are renumbered on submit.
    const regBody = document.getElementById('register-rows');
    document.getElementById('add-register-row')?.addEventListener('click', () => {
        const row = regBody.querySelector('[data-reg-row]').cloneNode(true);
        row.querySelectorAll('input').forEach(i => { if (i.type === 'checkbox') i.checked = false; else i.value = ''; });
        regBody.appendChild(row); row.querySelector('input[type=text]').focus();
    });
    regBody?.addEventListener('click', e => {
        if (!e.target.matches('[data-remove-row]')) return;
        const rows = regBody.querySelectorAll('[data-reg-row]');
        if (rows.length > 1) e.target.closest('tr').remove();
        else e.target.closest('tr').querySelectorAll('input').forEach(i => { if (i.type === 'checkbox') i.checked = false; else i.value = ''; });
    });
    document.getElementById('register-form')?.addEventListener('submit', () => {
        regBody.querySelectorAll('[data-reg-row]').forEach((row, n) => row.querySelectorAll('[name^="staff["]').forEach(i => i.name = i.name.replace(/^staff\[\d+\]/, `staff[${n}]`)));
    });
})();
</script>
@endpush
