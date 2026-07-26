<?php

namespace App\Http\Middleware;

use App\Models\LogModal;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\TenantLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LogRequests
{
  // Cache to prevent duplicate logs in the same request cycle
  protected static $loggedUrls = [];

  public function handle(Request $request, Closure $next)
  {
    $response = $next($request);

    try {
      $userId = Session::get('user_id');
      $url = $request->fullUrl();
      $method = $request->method();

      // Create a unique key for this request
      $logKey = $this->generateLogKey($userId, $method, $url);

      // Skip if we've already logged this request
      if (isset(self::$loggedUrls[$logKey])) {
        return $response;
      }

      // Mark this request as logged
      self::$loggedUrls[$logKey] = true;

      // Get user details from session if available
      $userName = Session::get('name');
      $registrationNo = Session::get('registration_no');
      $role = Session::get('role');
      $schoolCode = Session::get('school_code');
      $schoolName = Session::get('school_name');

      // Get status code safely
      $status = $this->getResponseStatusCode($response);

      // Prepare context data
      $context = [
        'status' => $status,
        'user_agent' => $request->header('User-Agent'),
      ];

      // Add additional request data for auth routes
      if ($request->is('login') || $request->is('register')) {
        $context['request_data'] = $request->except(['password', 'password_confirmation']);
      }

      // Prepare common log data
      $logData = [
        'user_id' => $userId ?: null,
        'user_name' => $userName,
        'registration_no' => $registrationNo,
        'role' => $role,
        'school_code' => $schoolCode,
        'school_name' => $schoolName,
        'level' => $this->getLogLevel($status),
        'method' => $method,
        'url' => $url,
        'ip' => $request->ip(),
        'message' => 'Request processed',
        'context' => $context,
      ];

      // Determine which log model to use
      if (!empty($schoolCode)) {
        // This is a school (tenant) user - log to tenant logs
        $this->createTenantLog($logData, $schoolCode);
      } else {
        // Regular user or admin - log to main logs
        $this->createMainLog($logData);
      }
    } catch (\Exception $e) {
      Log::error('Failed to log request: ' . $e->getMessage(), [
        'exception' => $e,
        'url' => $request->fullUrl()
      ]);
    }

    return $response;
  }

  /**
   * Generate a unique key for this request to prevent duplicates
   */
  protected function generateLogKey($userId, $method, $url): string
  {
    return md5(implode('|', [
      $userId ?? 'guest',
      $method,
      $url,
      now()->format('Y-m-d H:i') // Group by minute to allow same URL in different times
    ]));
  }

  /**
   * Create log entry in main database with duplicate check
   */
  protected function createMainLog(array $logData): void
  {
    // Check if similar log exists in the last minute
    $exists = LogModal::where('user_id', $logData['user_id'])
      ->where('method', $logData['method'])
      ->where('url', $logData['url'])
      ->where('created_at', '>=', now()->subMinute())
      ->exists();

    if (!$exists) {
      LogModal::create($logData);
    }
  }

  /**
   * Create log entry in tenant database with duplicate check
   */
  protected function createTenantLog(array $logData, string $schoolCode): void
  {
    try {
      // Configure tenant connection if needed
      $this->configureTenantConnection($schoolCode);

      // Check if similar log exists in the last minute
      $exists = TenantLog::on('tenant')
        ->where('user_id', $logData['user_id'])
        ->where('method', $logData['method'])
        ->where('url', $logData['url'])
        ->where('created_at', '>=', now()->subMinute())
        ->exists();

      if (!$exists) {
        TenantLog::on('tenant')->create($logData);
      }
    } catch (\Exception $e) {
      // Fallback to main log if tenant logging fails
      $this->createMainLog(array_merge($logData, [
        'context' => array_merge($logData['context'], [
          'log_error' => 'Failed to log to tenant: ' . $e->getMessage()
        ])
      ]));

      Log::error('Tenant log failed for school: ' . $schoolCode, [
        'exception' => $e,
        'log_data' => $logData
      ]);
    }
  }

  /**
   * Configure tenant database connection
   */
  protected function configureTenantConnection(string $schoolCode): void
  {
    // Query from the main database (mysql) not the tenant database
    $school = \App\Models\School::on('mysql')->where('school_code', $schoolCode)->first();
    if ($school) {
      $school->useAsTenant();
    }
  }

  /**
   * Safely get the response status code
   */
  protected function getResponseStatusCode($response): int
  {
    if (method_exists($response, 'getStatusCode')) {
      return $response->getStatusCode();
    }

    if (property_exists($response, 'statusCode')) {
      return $response->statusCode;
    }

    return 200; // Default fallback
  }

  /**
   * Determine log level based on status code
   */
  protected function getLogLevel(int $status): string
  {
    if ($status >= 500) {
      return 'error';
    }

    if ($status >= 400) {
      return 'warning';
    }

    return 'info';
  }
}
