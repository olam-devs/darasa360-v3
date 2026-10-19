<?php
// Isolated SQLite: no school data or sessions are changed.
$root=getenv('DARASA_FINANCE_ROOT') ?: dirname(__DIR__,2);
$loader=require $root.'/vendor/autoload.php';
$source=getenv('DARASA_MANAGEMENT_SOURCE') ?: dirname(__DIR__,2);
$loader->addClassMap([
 App\Services\ManagementQuarterAnalytics::class=>$source.'/app/Services/ManagementQuarterAnalytics.php',
 App\Http\Controllers\SchoolManagementController::class=>$source.'/app/Http/Controllers/SchoolManagementController.php',
]);
$capsule=new Illuminate\Database\Capsule\Manager;
$capsule->addConnection(['driver'=>'sqlite','database'=>':memory:'],'tenant');
$capsule->setAsGlobal();$capsule->bootEloquent();
$container=$capsule->getContainer();$container->instance('db',$capsule->getDatabaseManager());
Illuminate\Support\Facades\Facade::setFacadeApplication($container);
$db=$capsule->getConnection('tenant');
$db->statement('CREATE TABLE academic_years(id INTEGER PRIMARY KEY,name TEXT)');
$db->statement('CREATE TABLE particulars(id INTEGER PRIMARY KEY,name TEXT)');
$db->statement('CREATE TABLE particular_student(id INTEGER PRIMARY KEY,student_id INTEGER,particular_id INTEGER,academic_year_id INTEGER,quarter INTEGER,sales NUMERIC,credit NUMERIC)');
$db->table('academic_years')->insert([['id'=>2,'name'=>'2026'],['id'=>9,'name'=>'2025']]);
$db->table('particulars')->insert([['id'=>1,'name'=>'Fees'],['id'=>2,'name'=>'Transport']]);
$db->table('particular_student')->insert([
 ['id'=>1,'student_id'=>1,'particular_id'=>1,'academic_year_id'=>2,'quarter'=>3,'sales'=>600,'credit'=>500],
 ['id'=>2,'student_id'=>1,'particular_id'=>1,'academic_year_id'=>2,'quarter'=>4,'sales'=>600,'credit'=>100],
 ['id'=>3,'student_id'=>1,'particular_id'=>2,'academic_year_id'=>2,'quarter'=>4,'sales'=>200,'credit'=>250],
 ['id'=>4,'student_id'=>2,'particular_id'=>1,'academic_year_id'=>2,'quarter'=>4,'sales'=>400,'credit'=>200],
 ['id'=>5,'student_id'=>3,'particular_id'=>1,'academic_year_id'=>9,'quarter'=>4,'sales'=>9999,'credit'=>9999],
 ['id'=>6,'student_id'=>3,'particular_id'=>1,'academic_year_id'=>2,'quarter'=>null,'sales'=>300,'credit'=>100],
]);
function checkQuarter($ok,$message){if(!$ok)throw new RuntimeException($message);}
$service=new App\Services\ManagementQuarterAnalytics();$report=$service->report(2026,4);
checkQuarter($report['selected']['required']===1200.0 && $report['selected']['paid']===550.0,'Year isolation or exact Q4 assignment failed');
checkQuarter($report['selected']['outstanding']===700.0,'An overpaid assignment offset another student balance');
checkQuarter($report['selected']['students']===2,'Student was counted once per particular');
checkQuarter($report['quarters'][3]['required']===600.0 && $report['quarters'][3]['paid']===500.0,'Q4 changed Q3 totals');
checkQuarter($report['quarters'][1]['assignments']===0 && $report['quarters'][1]['rate']===null,'Missing Q1 presented as a collection failure');
checkQuarter($report['quarters'][2]['assignments']===0 && $report['unassigned']===1,'Missing Q2 or unassigned-quarter handling failed');
checkQuarter((float)$report['particulars']->sum('required_amount')===1200.0 && (float)$report['particulars']->sum('paid')===550.0,'Particulars do not reconcile to quarter totals');
checkQuarter(abs($report['selected']['rate']-(550/1200*100))<0.001,'Rate formula wrong');
checkQuarter(!$service->report(2027,4)['year_exists'],'Missing academic year counted as populated');
// School two reuses the same academic-year ID for a different year.
$db->table('academic_years')->where('id',2)->update(['name'=>'2025']);
checkQuarter($service->report(2026,4)['selected']['assignments']===0,'Academic-year IDs leaked between schools');
echo "PASS: quarter assignments, academic-year isolation, unpaid balances, distinct students, missing quarters, unassigned warning and particular reconciliation\n";
