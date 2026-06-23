<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
  /**
   * Display the settings page
   */
  public function index(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $tenantDb = session('school_db');
    $userId = session('user_id');
    $userType = session('user_type', 'staff');

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Get user preferences (you can create a user_preferences table later)
    // For now, we'll use session-based preferences
    $preferences = [
      'email_notifications' => session('pref_email_notifications', true),
      'sms_notifications' => session('pref_sms_notifications', true),
      'result_notifications' => session('pref_result_notifications', true),
      'announcement_notifications' => session('pref_announcement_notifications', true),
      'theme' => session('pref_theme', 'light'),
      'language' => session('pref_language', 'en'),
      'timezone' => session('pref_timezone', 'Africa/Nairobi'),
    ];

    return view('content.pages.settings', compact('preferences'));
  }

  /**
   * Update notification preferences
   */
  public function updateNotifications(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    try {
      // Store in session (you can later store in database)
      session([
        'pref_email_notifications' => $request->has('email_notifications'),
        'pref_sms_notifications' => $request->has('sms_notifications'),
        'pref_result_notifications' => $request->has('result_notifications'),
        'pref_announcement_notifications' => $request->has('announcement_notifications'),
      ]);

      return back()->with('success', 'Notification preferences updated successfully!');
    } catch (\Exception $e) {
      return back()->with('error', 'Failed to update preferences: ' . $e->getMessage());
    }
  }

  /**
   * Update appearance preferences
   */
  public function updateAppearance(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    try {
      $request->validate([
        'theme' => 'required|in:light,dark,auto',
        'language' => 'required|in:en,sw',
      ]);

      session([
        'pref_theme' => $request->theme,
        'pref_language' => $request->language,
      ]);

      return back()->with('success', 'Appearance settings updated successfully!');
    } catch (\Exception $e) {
      return back()->with('error', 'Failed to update settings: ' . $e->getMessage());
    }
  }

  /**
   * Update privacy preferences
   */
  public function updatePrivacy(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    try {
      session([
        'pref_show_email' => $request->has('show_email'),
        'pref_show_phone' => $request->has('show_phone'),
        'pref_allow_messages' => $request->has('allow_messages'),
      ]);

      return back()->with('success', 'Privacy settings updated successfully!');
    } catch (\Exception $e) {
      return back()->with('error', 'Failed to update privacy settings: ' . $e->getMessage());
    }
  }
}
