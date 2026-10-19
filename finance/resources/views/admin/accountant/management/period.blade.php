<form method="get" class="management-period" id="management-period-form">
    @if(!empty($selectedSchool))<input type="hidden" name="school" value="{{ $selectedSchool->id }}">@endif
    <label>Year<select name="year" id="management-year">@foreach($yearOptions as $option)<option value="{{ $option }}" @selected($year===$option)>{{ $option }}</option>@endforeach</select></label>
    <label>Quarter<select name="quarter" id="management-quarter">@foreach($quarterLabels as $number=>$label)<option value="{{ $number }}" @selected($quarter===$number)>{{ $label }}</option>@endforeach</select></label>
    <label>Cash flow / KPI period<select name="period_mode" id="management-period-mode"><option value="quarter" @selected($periodMode==='quarter')>Selected quarter</option><option value="custom" @selected($periodMode==='custom')>Custom dates</option></select></label>
    <label data-custom-dates @if($periodMode!=='custom') hidden @endif>From <input type="date" name="from" value="{{ $from }}" @disabled($periodMode!=='custom') required></label>
    <label data-custom-dates @if($periodMode!=='custom') hidden @endif>To <input type="date" name="to" value="{{ $to }}" @disabled($periodMode!=='custom') required></label>
    <button class="management-primary" type="submit">Apply</button>
    @php([$nowYear, $nowQuarter] = \App\Services\ManagementQuarterAnalytics::quarterOf(now('Africa/Dar_es_Salaam')))
    <a href="{{ route('accountant.management',array_filter(['year'=>$nowYear,'quarter'=>$nowQuarter,'school'=>$selectedSchool?->id ?? null])) }}">Current quarter</a>
</form>
<p class="management-muted">Cash flow and accountant targets: <strong>{{ $periodLabel }}</strong> ({{ $from }} – {{ $to }}). Fee analytics: <strong>Q{{ $quarter }} {{ $year }}</strong>, by assigned quarter.</p>
