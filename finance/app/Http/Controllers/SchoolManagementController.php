<?php

namespace App\Http\Controllers;

use App\Models\Central\ActivityLog;
use App\Models\Central\SchoolAccountant;
use App\Models\SchoolSetting;
use App\Services\AccountantKpiService;
use App\Services\ManagementQuarterAnalytics;
use App\Services\SchoolManagementAccess;
use App\Services\TenantDatabaseManager;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SchoolManagementController extends Controller
{
    public function __construct(protected SchoolManagementAccess $access, protected AccountantKpiService $kpis) {}

    private function period(Request $request): array
    {
        $request->validate(['from'=>'nullable|date_format:Y-m-d', 'to'=>'nullable|date_format:Y-m-d',
            'year'=>'nullable|integer|between:2000,2100','quarter'=>'nullable|integer|between:1,4',
            'period_mode'=>'nullable|in:quarter,custom']);
        $today = CarbonImmutable::now('Africa/Dar_es_Salaam');
        $year = (int)($request->input('year') ?: $today->year);
        $quarter = (int)($request->input('quarter') ?: $today->quarter);
        $periodMode = $request->input('period_mode') ?: ($request->filled('from') || $request->filled('to') ? 'custom' : 'quarter');
        $start = CarbonImmutable::create($year,($quarter-1)*3+1,1,0,0,0,'Africa/Dar_es_Salaam');
        $from = $periodMode === 'quarter' ? $start->toDateString() : ($request->input('from') ?: $start->toDateString());
        $to = $periodMode === 'quarter' ? $start->endOfQuarter()->toDateString() : ($request->input('to') ?: $start->endOfQuarter()->toDateString());
        validator(['from'=>$from,'to'=>$to], ['from'=>'required|date','to'=>'required|date|after_or_equal:from'])->validate();
        abort_if(CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > 730, 422, 'Choose a period of two years or less.');
        $periodLabel = $periodMode === 'quarter' ? "Q{$quarter} {$year}" : "{$from} to {$to}";
        return compact('from','to','year','quarter','periodMode','periodLabel');
    }

    public function index(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $settings = (object)['school_name'=>'School management · all schools','logo_path'=>null];
        ['from'=>$from,'to'=>$to,'year'=>$year,'quarter'=>$quarter,'periodMode'=>$periodMode,'periodLabel'=>$periodLabel] = $this->period($request);
        $schools = $this->access->schools($request->user());
        $yearOptions = collect([$year,now('Africa/Dar_es_Salaam')->year]);
        $snapshots = []; $kpiRows = [];
        foreach ($schools as $school) {
            try {
                $snapshots[] = app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($school, $from, $to, $year, $quarter, $periodMode, &$yearOptions) {
                    $db = DB::connection('tenant');
                    $yearOptions = $yearOptions->merge($db->table('academic_years')->pluck('name')->filter(fn($name)=>preg_match('/^20\d{2}$|^2100$/',(string)$name))->map(fn($name)=>(int)$name));
                    $quarterReport = app(ManagementQuarterAnalytics::class)->report($year,$quarter);
                    $receipts = $this->kpis->receipts()->whereBetween('date', [$from, $to]);
                    $days = (int)CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) + 1;
                    $previousTo = CarbonImmutable::parse($from)->subDay();
                    $previousFrom = $periodMode === 'quarter' ? $previousTo->startOfQuarter() : $previousTo->subDays($days-1);
                    $previous = (float)$this->kpis->receipts()->whereBetween('date', [$previousFrom->toDateString(), $previousTo->toDateString()])->sum('debit');
                    $expenses = (float)$db->table('expense_submissions')->whereIn('status',['approved','partially_approved'])
                        ->whereBetween('transaction_date',[$from,$to])->sum('total_amount');
                    $owed = $db->table('particular_student')->selectRaw('COALESCE(SUM(GREATEST(0,sales-credit)),0) as outstanding, COALESCE(SUM(sales),0) as billed, COALESCE(SUM(credit),0) as paid')->first();
                    $pending = $db->table('expense_submissions')->where('status','pending');
                    $books = $db->table('books')->where('is_active',true)->get(['id','opening_balance']);
                    $bookMovement = $db->table('vouchers')->whereIn('book_id',$books->pluck('id'))->whereNull('voided_at')->selectRaw('COALESCE(SUM(debit-credit),0) as net')->value('net');
                    $trend = (clone $receipts)->selectRaw('DATE(date) as day, SUM(debit) as amount')->groupBy('day')->orderBy('day')->get();
                    $topDebtors = $db->table('particular_student as ps')->join('students as s','s.id','=','ps.student_id')
                        ->select('s.name')->selectRaw('SUM(GREATEST(0,ps.sales-ps.credit)) as balance')->groupBy('s.id','s.name')->having('balance','>',0)->orderByDesc('balance')->limit(5)->get();
                    return ['id'=>$school->id,'name'=>$school->name,'error'=>false,
                        'collections'=>(float)(clone $receipts)->sum('debit'),'previous'=>$previous,'expenses'=>$expenses,
                        'students'=>$db->table('students')->where('status','active')->count(),
                        'outstanding'=>(float)$owed->outstanding, 'billed'=>(float)$owed->billed,'paid'=>(float)$owed->paid,
                        'overdue'=>(float)$db->table('particular_student')->whereNotNull('deadline')->whereDate('deadline','<',now('Africa/Dar_es_Salaam')->toDateString())->selectRaw('COALESCE(SUM(GREATEST(0,sales-credit)),0) as total')->value('total'),
                        'pending'=>(clone $pending)->count(), 'oldest_pending'=>(clone $pending)->min('created_at'),
                        'pending_items'=>$db->table('expense_submissions as e')->leftJoin('expense_categories as c','c.id','=','e.expense_category_id')
                            ->where('e.status','pending')->orderBy('e.created_at')->limit(100)
                            ->get(['e.id','e.submission_number','e.title','e.total_amount','e.transaction_date','e.created_at','c.name as category_name']),
                        'book_balance'=>(float)$books->sum('opening_balance')+(float)$bookMovement,
                        'advance'=>(float)$db->table('students')->sum('advance_balance'),
                        'trend'=>$trend,'debtors'=>$topDebtors,'quarter_report'=>$quarterReport];
                });
                $kpiRows = array_merge($kpiRows, $this->kpis->rows($school, $from, $to));
            } catch (\Throwable $e) {
                report($e);
                // Do not present unavailable school data as zero or leak database errors.
                $snapshots = array_values(array_filter($snapshots, fn($s) => $s['id'] !== $school->id));
                $snapshots[] = ['id'=>$school->id,'name'=>$school->name,'error'=>true];
            }
        }
        $yearOptions = $yearOptions->unique()->sortDesc()->values();
        $quarterLabels = ManagementQuarterAnalytics::LABELS;
        $metrics = AccountantKpiService::METRICS;
        return view('admin.accountant.management.overview', compact('settings','schools','snapshots','kpiRows','from','to','metrics','year','quarter','periodMode','periodLabel','yearOptions','quarterLabels'));
    }

    public function mine(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(),403);
        return redirect()->to(route('accountant.management',$request->only('from','to','year','quarter','period_mode')).'#targets');
    }

    public function saveTarget(Request $request)
    {
        $data=$request->validate([
            'school_id'=>'required|integer','accountant_id'=>'required|integer',
            'metric'=>'required|in:'.implode(',',array_keys(AccountantKpiService::METRICS)),
            'from_date'=>'required|date_format:Y-m-d','to_date'=>'required|date_format:Y-m-d|after_or_equal:from_date',
            'target_value'=>'required|numeric|gt:0|max:999999999999.99','recording_days'=>'required|integer|between:0,30',
            'year'=>'nullable|integer|between:2000,2100','quarter'=>'nullable|integer|between:1,4','period_mode'=>'nullable|in:quarter,custom',
        ]);
        $school=$this->access->school($request->user(), (int)$data['school_id']);
        abort_unless(SchoolAccountant::whereKey($data['accountant_id'])->where('school_id',$school->id)->where('is_active',true)->exists(),422,'Choose an active accountant in this school.');
        validator($data, ['target_value'=>$data['metric']==='recording_timeliness'?'numeric|max:100':($data['metric']==='receipt_count'?'integer':'numeric')])->validate();
        abort_if(CarbonImmutable::parse($data['from_date'])->diffInDays(CarbonImmutable::parse($data['to_date'])) > 730,422,'Choose a period of two years or less.');
        $key = array_intersect_key($data,array_flip(['school_id','accountant_id','metric','from_date','to_date']));
        DB::connection('central')->transaction(function () use ($request,$key,$data) {
            $existing=DB::connection('central')->table('accountant_kpi_targets')->where($key)->first();
            DB::connection('central')->table('accountant_kpi_targets')->updateOrInsert($key,[
                'target_value'=>$data['target_value'],'recording_days'=>$data['recording_days'],
                'updated_by'=>$this->access->manager($request->user())->id,'created_at'=>$existing?->created_at ?? now(),'updated_at'=>now()]);
            ActivityLog::create(['school_id'=>$data['school_id'],'user_type'=>'accountant','user_id'=>$this->access->manager($request->user())->id,
                'user_name'=>$this->access->manager($request->user())->name,'action'=>'kpi_target_updated',
                'description'=>json_encode(['target'=>$data,'previous'=>$existing?->target_value]),'ip_address'=>$request->ip()]);
        });
        return redirect()->to(route('accountant.management',array_merge(['from'=>$data['from_date'],'to'=>$data['to_date']],$request->only('year','quarter','period_mode'))).'#targets')->with('success','Accountant target saved.');
    }

    public function enter(Request $request, int $schoolId)
    {
        $this->access->school($request->user(),$schoolId);
        $grant=$this->access->grants($request->user())->firstWhere('school_id',$schoolId);
        $account=SchoolAccountant::findOrFail($grant->school_accountant_id);
        $routes=['dashboard'=>'accountant.dashboard','expenses'=>'accountant.expenses','fee-entry'=>'accountant.fee-entry','overdue'=>'accountant.overdue','ledgers'=>'accountant.ledgers','invoices'=>'accountant.invoices-page'];
        $request->validate(['page'=>'required|in:'.implode(',',array_keys($routes)),'submission_id'=>'nullable|integer|min:1']);
        $manager=$this->access->manager($request->user());
        ActivityLog::create(['school_id'=>$schoolId,'user_type'=>'accountant','user_id'=>$manager->id,'user_name'=>$manager->name,
            'action'=>'management_school_opened','description'=>'Opened school through management overview.','ip_address'=>$request->ip()]);
        Auth::guard('web')->login($account);
        $request->session()->regenerate();
        $request->session()->put('school_management_manager_id',$manager->id);
        $request->session()->forget(['impersonating','impersonating_super_admin_id','current_school_slug','current_school_id']);
        $params=$request->input('page')==='expenses' ? ['tab'=>'review','submission'=>$request->input('submission_id')] : [];
        return redirect()->route($routes[$request->input('page')],$params);
    }

    public function accountSettings(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(),403);
        $manager=$this->access->manager($request->user());
        Auth::guard('web')->login($manager);
        $request->session()->regenerate();
        $request->session()->forget('school_management_manager_id');
        return redirect()->route('profile.edit');
    }
}
