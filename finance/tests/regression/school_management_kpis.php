<?php
// Isolated SQLite fixtures only; never reads or writes real school financial records.
$root=getenv('DARASA_FINANCE_ROOT') ?: dirname(__DIR__,2);
$loader=require $root.'/vendor/autoload.php';
$source=getenv('DARASA_MANAGEMENT_SOURCE') ?: dirname(__DIR__,2);
$loader->addPsr4('App\\',$source.'/app',true);
$classMap=[];
foreach(['App\\Http\\Controllers\\SchoolManagementController'=>'app/Http/Controllers/SchoolManagementController.php',
 'App\\Services\\SchoolManagementAccess'=>'app/Services/SchoolManagementAccess.php',
 'App\\Services\\AccountantKpiService'=>'app/Services/AccountantKpiService.php'] as $class=>$file)
 $classMap[$class]=(is_file($source.'/'.$file)?$source:$root).'/'.$file;
$loader->addClassMap($classMap);
$app=require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Central\School;
use App\Models\Central\SchoolAccountant;
use App\Services\SchoolManagementAccess;
use App\Services\AccountantKpiService;
function verifyManagement($ok,$message) {if(!$ok)throw new RuntimeException($message);}
$controller=app(App\Http\Controllers\SchoolManagementController::class);
$periodMethod=new ReflectionMethod($controller,'period');
foreach([1=>['2024-01-01','2024-03-31'],2=>['2024-04-01','2024-06-30'],3=>['2024-07-01','2024-09-30'],4=>['2024-10-01','2024-12-31']] as $quarter=>$dates) {
 $period=$periodMethod->invoke($controller,new Illuminate\Http\Request(['year'=>2024,'quarter'=>$quarter]));
 verifyManagement([$period['from'],$period['to']]===$dates && $period['periodMode']==='quarter','Quarter date boundaries failed');
}
$period=$periodMethod->invoke($controller,new Illuminate\Http\Request(['from'=>'2026-08-01','to'=>'2026-08-31']));
verifyManagement($period['from']==='2026-08-01' && $period['to']==='2026-08-31' && $period['periodMode']==='custom','Existing custom-date links changed meaning');
try {$periodMethod->invoke($controller,new Illuminate\Http\Request(['quarter'=>5]));throw new RuntimeException('Invalid quarter accepted');}
catch(Illuminate\Validation\ValidationException $expected){}
config(['database.default'=>'central','database.connections.central'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],
    'database.connections.tenant'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
DB::purge('central'); DB::purge('tenant');
$central=DB::connection('central');
$central->statement('CREATE TABLE schools (id INTEGER PRIMARY KEY, name TEXT, slug TEXT, database_name TEXT, is_active INTEGER, db_host TEXT, db_port TEXT, db_username TEXT, db_password TEXT)');
$central->statement('CREATE TABLE school_accountants (id INTEGER PRIMARY KEY, school_id INTEGER, name TEXT, is_main_accountant INTEGER, is_active INTEGER)');
$central->statement('CREATE TABLE activity_logs (id INTEGER PRIMARY KEY, school_id INTEGER, user_type TEXT, user_id INTEGER, user_name TEXT, action TEXT, description TEXT, ip_address TEXT, created_at TEXT, updated_at TEXT)');
$migrationPath='/database/migrations/central/2026_10_17_000001_create_school_management_and_kpi_tables.php';
$migration=require (is_file($source.$migrationPath)?$source:$root).$migrationPath;
$migration->up(); $migration->up(); // Safe to apply twice.
$files=[];
try {
 foreach([17,20,30] as $schoolId) {
    $file=tempnam(sys_get_temp_dir(),'darasa-kpi-');$files[]=$file;
    $central->table('schools')->insert(['id'=>$schoolId,'name'=>'Fixture '.$schoolId,'slug'=>'fixture-'.$schoolId,'database_name'=>$file,'is_active'=>1]);
    config(['database.connections.tenant.database'=>$file]); DB::purge('tenant');
    DB::connection('tenant')->statement('CREATE TABLE vouchers (id INTEGER PRIMARY KEY, voucher_type TEXT, book_id INTEGER, voided_at TEXT, created_by INTEGER, date TEXT, created_at TEXT, debit NUMERIC)');
 }
 $central->table('school_accountants')->insert([
    ['id'=>1,'school_id'=>17,'name'=>'Manager','is_main_accountant'=>1,'is_active'=>1],
    ['id'=>2,'school_id'=>20,'name'=>'Manager school 2','is_main_accountant'=>1,'is_active'=>1],
    ['id'=>3,'school_id'=>17,'name'=>'Accountant','is_main_accountant'=>0,'is_active'=>1],
    ['id'=>4,'school_id'=>30,'name'=>'Unrelated main accountant','is_main_accountant'=>1,'is_active'=>1],
 ]);
 $central->table('school_management_access')->insert([
    ['manager_accountant_id'=>1,'school_id'=>17,'school_accountant_id'=>1],
    ['manager_accountant_id'=>1,'school_id'=>20,'school_accountant_id'=>2],
 ]);
 $access=new SchoolManagementAccess(); $manager=SchoolAccountant::findOrFail(1);
 verifyManagement($access->schools($manager)->pluck('id')->all()===[17,20], 'Manager school scope failed');
 verifyManagement($access->grants(SchoolAccountant::find(3))->isEmpty(),'Regular accountant granted management');
 $privateRequest=new Illuminate\Http\Request();
 $privateRequest->setUserResolver(fn()=>SchoolAccountant::findOrFail(3));
 foreach(['index','mine'] as $method) {
    try {app(App\Http\Controllers\SchoolManagementController::class)->{$method}($privateRequest);throw new RuntimeException('Regular accountant viewed private KPI page');}
    catch(Symfony\Component\HttpKernel\Exception\HttpException $e){verifyManagement($e->getStatusCode()===403,'Private KPI page not denied');}
 }
 verifyManagement($access->grants(SchoolAccountant::find(4))->isEmpty(),'Unrelated main accountant granted management');
 session(['school_management_manager_id'=>1]);
 verifyManagement($access->schools(SchoolAccountant::find(2))->count()===2,'Authorized school switch lost manager context');
 verifyManagement($access->grants(SchoolAccountant::find(3))->isEmpty(),'Regular accountant reused manager context');
 verifyManagement($access->grants(SchoolAccountant::find(4))->isEmpty(),'Unrelated main accountant reused manager context');
 session()->forget('school_management_manager_id');
 try {$access->school($manager,30);throw new RuntimeException('Cross-school leak');}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){verifyManagement($e->getStatusCode()===403,'Wrong denial');}
 $central->table('school_accountants')->where('id',2)->update(['is_active'=>0]);
 verifyManagement($access->grants($manager)->count()===1,'Inactive school account still grants access');
 $central->table('school_accountants')->where('id',2)->update(['is_active'=>1,'school_id'=>30]);
 verifyManagement($access->grants($manager)->count()===1,'Mismatched school account grants access');
 $central->table('school_accountants')->where('id',2)->update(['school_id'=>20]);
 $school=School::findOrFail(17); app(App\Services\TenantDatabaseManager::class)->switchToSchool($school);
 $base=['voucher_type'=>'Receipt','book_id'=>1,'voided_at'=>null,'created_by'=>1,'date'=>'2026-01-01','created_at'=>'2026-01-01 12:00:00','debit'=>100];
 DB::connection('tenant')->table('vouchers')->insert([
    $base, array_replace($base,['debit'=>200,'date'=>'2026-01-10','created_at'=>'2026-01-15 12:00:00']),
    array_replace($base,['debit'=>900,'book_id'=>null]), // Existing advance, not new cash.
    array_replace($base,['debit'=>900,'voided_at'=>'2026-01-02']),
    array_replace($base,['debit'=>50,'created_by'=>3]),
    array_replace($base,['debit'=>25,'created_at'=>'2025-12-31 22:00:00']), // 1 January locally.
    array_replace($base,['debit'=>900,'created_at'=>'2026-01-31 22:00:00']), // February locally; exclude.
 ]);
 foreach(['collection_amount'=>400,'receipt_count'=>2,'recording_timeliness'=>75] as $metric=>$value)
    $central->table('accountant_kpi_targets')->insert(['school_id'=>17,'accountant_id'=>1,'metric'=>$metric,'from_date'=>'2026-01-01','to_date'=>'2026-01-31','target_value'=>$value,'recording_days'=>1,'updated_by'=>1]);
 $service=new AccountantKpiService();
 $rows=collect($service->rows($school,'2026-01-01','2026-01-31',1))->keyBy('metric');
 verifyManagement($rows['collection_amount']['actual']===325.0,'Cash/advance/voided receipt or local date boundary calculation failed');
 verifyManagement($rows['receipt_count']['actual']===3.0,'Receipt count wrong');
 verifyManagement($rows['recording_timeliness']['actual']===66.7,'Receipt timing calculation wrong');
 verifyManagement($rows['collection_amount']['shortfall']===75.0,'Target shortfall wrong');
 verifyManagement($rows['receipt_count']['status']==='Met','Reached target not marked met');
 verifyManagement(count($service->rows($school,'2026-01-01','2026-01-31',3))===3,'Own scorecard wrong');
 verifyManagement(AccountantKpiService::status(null,0,'2026-01-01','2026-02-01')==='Not set','Unset target marked failed');
 verifyManagement(AccountantKpiService::status(100,50,'2026-01-31','2026-01-31')==='In progress','Unfinished period marked missed');
 verifyManagement(AccountantKpiService::status(100,50,'2026-01-31','2026-02-01')==='Missed','Ended period not marked missed');
 verifyManagement(AccountantKpiService::status(75,null,'2026-01-31','2026-02-01')==='No data','Empty denominator marked failure');
 $request=new Illuminate\Http\Request(['school_id'=>17,'accountant_id'=>4,'metric'=>'receipt_count','from_date'=>'2026-01-01','to_date'=>'2026-01-31','target_value'=>4,'recording_days'=>1]);
 $request->setUserResolver(fn()=>$manager);
 try {app(App\Http\Controllers\SchoolManagementController::class)->saveTarget($request);throw new RuntimeException('Wrong-school accountant target accepted');}
 catch(Symfony\Component\HttpKernel\Exception\HttpException $e){verifyManagement($e->getStatusCode()===422,'Wrong target denial');}
 verifyManagement($central->table('accountant_kpi_targets')->count()===3,'Rejected target modified data');
 $request->merge(['accountant_id'=>1,'target_value'=>5]);
 $response=app(App\Http\Controllers\SchoolManagementController::class)->saveTarget($request);
 verifyManagement($response->getStatusCode()===302,'Valid target did not save');
 verifyManagement((float)$central->table('accountant_kpi_targets')->where('metric','receipt_count')->value('target_value')===5.0,'Valid target value not persisted');
 verifyManagement($central->table('activity_logs')->where('action','kpi_target_updated')->count()===1,'Target change not audited');
 $request->setUserResolver(fn()=>SchoolAccountant::findOrFail(3));
 try {app(App\Http\Controllers\SchoolManagementController::class)->saveTarget($request);throw new RuntimeException('Regular accountant set a target');}
 catch(Symfony\Component\HttpKernel\Exception\HttpException $e){verifyManagement($e->getStatusCode()===403,'Wrong regular-accountant denial');}
 $request->setUserResolver(fn()=>$manager);$request->merge(['metric'=>'recording_timeliness','target_value'=>101]);
 try {app(App\Http\Controllers\SchoolManagementController::class)->saveTarget($request);throw new RuntimeException('Invalid percent accepted');}
 catch(Illuminate\Validation\ValidationException $expected){}
 echo "PASS: manager scope, inactive/mismatched grant denial, own-accountant scope, cash/advance/void exclusion, timing, targets, statuses, and cross-school target rejection\n";
} finally {
 DB::purge('tenant');foreach($files as $file)@unlink($file);
}
