<?php
// In-memory sessions only. No authentication attempt or financial data mutation.
$root=getenv('DARASA_FINANCE_ROOT') ?: dirname(__DIR__,2);
$source=getenv('DARASA_LOGIN_SOURCE') ?: dirname(__DIR__,2);
$loader=require $root.'/vendor/autoload.php';$loader->addPsr4('App\\',$source.'/app',true);
$loader->addClassMap([
    App\Http\Controllers\Auth\AuthenticatedSessionController::class=>$source.'/app/Http/Controllers/Auth/AuthenticatedSessionController.php',
    App\Support\ExpiredLoginResponse::class=>$source.'/app/Support/ExpiredLoginResponse.php',
]);
$app=require $root.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->instance('env','production');
config(['app.debug'=>false,'session.driver'=>'array']);
app('session')->forgetDrivers();$store=app('session')->driver();$store->start();
$app->instance('session.store',$store);app('redirect')->setSession($store);
app('view')->share('errors',new Illuminate\Support\ViewErrorBag());
$handler=new Illuminate\Foundation\Exceptions\Handler($app);
$handler->respondUsing(new App\Support\ExpiredLoginResponse());
$middleware=new Illuminate\Foundation\Http\Middleware\VerifyCsrfToken($app,app('encrypter'));
function checkExpiredLogin($ok,$message){if(!$ok)throw new RuntimeException($message);}
function loginRequest($path,$data,$json=false){
    $request=Illuminate\Http\Request::create('https://finance.darasa360.co.tz'.$path,'POST',$data);
    $request->headers->set('Accept',$json?'application/json':'text/html');
    $request->setLaravelSession(app('session.store'));app()->instance('request',$request);return $request;
}
$password='dummy-regression-password-not-a-real-credential';
$request=loginRequest('/login',['email'=>'accountant@example.test','password'=>$password,'_token'=>'stale-token','other'=>'do-not-flash']);
$nextCalled=false;
try{$middleware->handle($request,function()use(&$nextCalled){$nextCalled=true;return response('wrong',200);});throw new RuntimeException('Stale CSRF accepted');}
catch(Illuminate\Session\TokenMismatchException $exception){$response=$handler->render($request,$exception);}
checkExpiredLogin(!$nextCalled,'Stale login reached authentication');
checkExpiredLogin($response->getStatusCode()===302&&str_ends_with($response->headers->get('Location'),'/login'),'Expired form did not return to login');
checkExpiredLogin($store->get('_old_input')===['email'=>'accountant@example.test'],'Unexpected login fields flashed');
checkExpiredLogin(!str_contains(json_encode($store->all()),$password),'Password retained in session');
checkExpiredLogin(str_contains($store->get('status'),'expired'),'Recovery message missing');
$request=loginRequest('/login',['_token'=>$store->token()]);$nextCalled=false;
$response=$middleware->handle($request,function()use(&$nextCalled){$nextCalled=true;return response('valid',200);});
checkExpiredLogin($nextCalled&&$response->getStatusCode()===200,'Valid CSRF blocked');
foreach([['/login',true],['/api/expense-submissions',false]] as [$path,$json]){
    $request=loginRequest($path,['_token'=>'stale'],$json);
    try{$middleware->handle($request,fn()=>response('wrong'));throw new RuntimeException('Invalid token accepted');}
    catch(Illuminate\Session\TokenMismatchException $exception){$response=$handler->render($request,$exception);}
    checkExpiredLogin($response->getStatusCode()===419,'Other CSRF rejection changed');
}
$login=app(App\Http\Controllers\Auth\AuthenticatedSessionController::class)->create();
checkExpiredLogin(str_contains($login->headers->get('Cache-Control'),'no-store'),'Login can be cached');
checkExpiredLogin(str_contains($login->getContent(),'name="_token"'),'Fresh login token missing');
checkExpiredLogin(str_contains($login->getContent(),'name="email"'),'Login fields missing');
echo "PASS: stale login rejected and redirected, email-only recovery, password not retained, valid tokens accepted, API/other CSRF unchanged, and uncached login form\n";
