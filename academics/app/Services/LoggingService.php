<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Jenssegers\Agent\Agent;

class LoggingService
{
    /**
     * Log an activity with enhanced metadata
     */
    public static function log(
        string $level,
        string $method,
        string $url,
        string $message,
        ?array $context = null,
        ?int $userId = null
    ) {
        $agent = new Agent();
        $ip = request()->ip();

        // Get location data from IP (using free ipapi.co service)
        $location = self::getLocationFromIp($ip);

        // Get user details
        $userName = null;
        $registrationNo = null;
        $role = null;
        $schoolCode = null;
        $schoolName = null;

        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $userName = $user->username;
                $registrationNo = $user->registration_no;
                $role = $user->userRole->name ?? null;
                $schoolCode = $user->school->school_code ?? null;
                $schoolName = $user->school->name ?? null;
            }
        } elseif (auth()->check()) {
            $user = auth()->user();
            $userId = $user->id;
            $userName = $user->username;
            $registrationNo = $user->registration_no;
            $role = $user->userRole->name ?? null;
            $schoolCode = $user->school->school_code ?? null;
            $schoolName = $user->school->name ?? null;
        }

        try {
            DB::table('logs')->insert([
                'user_id' => $userId,
                'user_name' => $userName,
                'registration_no' => $registrationNo,
                'role' => $role,
                'school_code' => $schoolCode,
                'school_name' => $schoolName,
                'level' => $level,
                'method' => $method,
                'url' => $url,
                'ip' => $ip,
                'browser' => $agent->browser(),
                'browser_version' => $agent->version($agent->browser()),
                'platform' => $agent->platform(),
                'device_type' => self::getDeviceType($agent),
                'country' => $location['country'] ?? null,
                'city' => $location['city'] ?? null,
                'user_agent' => request()->userAgent(),
                'message' => $message,
                'context' => $context ? json_encode($context) : null,
                'logged_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail - logging shouldn't break the application
            \Log::error('Failed to log activity: ' . $e->getMessage());
        }
    }

    /**
     * Determine device type
     */
    private static function getDeviceType(Agent $agent): string
    {
        if ($agent->isTablet()) {
            return 'tablet';
        } elseif ($agent->isMobile()) {
            return 'mobile';
        } elseif ($agent->isDesktop()) {
            return 'desktop';
        } else {
            return 'unknown';
        }
    }

    /**
     * Get location from IP address using ipapi.co (free tier: 1000 requests/day)
     */
    private static function getLocationFromIp(string $ip): array
    {
        // Don't query for local IPs
        if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.')) {
            return ['country' => 'Local', 'city' => 'Local'];
        }

        try {
            // Cache the result for 24 hours
            $cacheKey = 'ip_location_' . $ip;

            if (cache()->has($cacheKey)) {
                return cache($cacheKey);
            }

            $response = Http::timeout(3)->get("https://ipapi.co/{$ip}/json/");

            if ($response->successful()) {
                $data = $response->json();
                $location = [
                    'country' => $data['country_name'] ?? null,
                    'city' => $data['city'] ?? null,
                ];

                cache([$cacheKey => $location], now()->addDay());
                return $location;
            }
        } catch (\Exception $e) {
            // Silently fail - logging shouldn't break the application
        }

        return ['country' => null, 'city' => null];
    }
}
