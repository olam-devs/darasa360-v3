<form method="get" class="management-period" id="management-period-form">
    @if(!empty($selectedSchool))<input type="hidden" name="school" value="{{ $selectedSchool->id }}">@endif
    <label>Year<select name="year" id="management-year">@foreach($yearOptions as $option)<option value="{{ $option }}" @selected($year===$option)>{{ $option }}</option>@endforeach</select></label>
    <div class="management-quarter-picker" role="group" aria-label="Quarters">
        <span>Quarters</span>
        <div>
            @foreach($quarterLabels as $number=>$label)
                <label class="management-month"><input type="checkbox" name="quarters[]" value="{{ $number }}" data-quarter-box @checked($number>=$quarter && $number<=$quarterTo)><span>{{ $label }}</span></label>
            @endforeach
            <button type="button" class="management-chip-button" data-quarter-all>All quarters</button>
        </div>
    </div>
    <label>Cash flow / KPI period<select name="period_mode" id="management-period-mode"><option value="quarter" @selected($periodMode==='quarter')>Selected quarters</option><option value="custom" @selected($periodMode==='custom')>Custom dates</option></select></label>
    <label data-custom-dates @if($periodMode!=='custom') hidden @endif>From <input type="date" name="from" value="{{ $from }}" @disabled($periodMode!=='custom') required></label>
    <label data-custom-dates @if($periodMode!=='custom') hidden @endif>To <input type="date" name="to" value="{{ $to }}" @disabled($periodMode!=='custom') required></label>
    <button class="management-primary" type="submit">Apply</button>
    @php([$nowYear, $nowQuarter] = \App\Services\ManagementQuarterAnalytics::quarterOf(now('Africa/Dar_es_Salaam')))
    <a href="{{ url()->current().'?'.http_build_query(array_filter(['year'=>$nowYear,'quarter'=>$nowQuarter,'school'=>$selectedSchool?->id ?? null, 'account'=>request('account'), 'student'=>request('student')])) }}">Current quarter</a>
</form>
<p class="management-muted">Showing <strong>{{ $periodLabel }}</strong> ({{ $from }} – {{ $to }}) for cash flow, books and targets; fee figures cover <strong>{{ $qLabel }} {{ $year }}</strong> by each fee's assigned quarter. Ticked quarters form one span: ticking Q1 and Q3 also includes Q2.</p>
<script>
(() => {
    // Keep the ticked quarters continuous (Q1 + Q3 ticks Q2 too) and never empty.
    const boxes = [...document.querySelectorAll('[data-quarter-box]')];
    function fill(changed) {
        const on = boxes.filter(b => b.checked).map(b => +b.value);
        if (on.length === 0) { changed.checked = true; return; }
        const lo = Math.min(...on), hi = Math.max(...on);
        boxes.forEach(b => b.checked = +b.value >= lo && +b.value <= hi);
    }
    boxes.forEach(b => b.addEventListener('change', () => fill(b)));
    document.querySelector('[data-quarter-all]')?.addEventListener('click', () => boxes.forEach(b => b.checked = true));
})();
</script>
