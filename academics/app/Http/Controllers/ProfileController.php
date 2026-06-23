<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
  /**
   * Display the user's profile
   */
  public function index(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $tenantDb = session('school_db');
    $userId = session('user_id');
    $userType = session('user_type', 'staff'); // staff or student

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    try {
      // Get user data
      if ($userType === 'student') {
        $user = DB::connection('tenant')
          ->table('students')
          ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
          ->where('students.id', $userId)
          ->select(
            'students.*',
            'classes.name as class_name'
          )
          ->first();
      } else {
        $user = DB::connection('tenant')
          ->table('schoolUsers')
          ->leftJoin('school_roles', 'schoolUsers.role_id', '=', 'school_roles.id')
          ->where('schoolUsers.id', $userId)
          ->select('schoolUsers.*', 'school_roles.name as role_name')
          ->first();
      }

      if (!$user) {
        return redirect('/login')->with('error', 'User not found');
      }

      // Ensure all properties exist with default values
      $user->username = $user->username ?? 'User';
      $user->registration_no = $user->registration_no ?? 'N/A';
      $user->email = $user->email ?? null;
      $user->phone = $user->phone ?? null;
      $user->address = $user->address ?? null;
      $user->gender = $user->gender ?? null;
      $user->profile_picture = $user->profile_picture ?? null;
      $user->created_at = $user->created_at ?? null;
      $user->updated_at = $user->updated_at ?? null;

      return view('content.pages.profile', compact('user', 'userType'));
    } catch (\Exception $e) {
      \Log::error('Profile error: ' . $e->getMessage());
      return redirect('/login')->with('error', 'Error loading profile: ' . $e->getMessage());
    }
  }

  /**
   * Update the user's profile
   */
  public function update(Request $request)
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

    try {
      $validatedData = $request->validate([
        'username' => 'required|string|max:255',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string|max:500',
      ]);

      $table = $userType === 'student' ? 'students' : 'schoolUsers';

      // Build update array with only fields that exist
      $updateData = ['username' => $validatedData['username']];

      // Check which columns exist before updating
      $columns = \Illuminate\Support\Facades\Schema::connection('tenant')->getColumnListing($table);

      if (in_array('email', $columns)) {
        $updateData['email'] = $validatedData['email'] ?? null;
      }
      if (in_array('phone', $columns)) {
        $updateData['phone'] = $validatedData['phone'] ?? null;
      }
      if (in_array('address', $columns)) {
        $updateData['address'] = $validatedData['address'] ?? null;
      }
      if (in_array('updated_at', $columns)) {
        $updateData['updated_at'] = now();
      }

      DB::connection('tenant')->table($table)
        ->where('id', $userId)
        ->update($updateData);

      // Update session
      session(['username' => $validatedData['username']]);

      return redirect()->route('profile.index')->with('success', 'Profile updated successfully!');
    } catch (\Exception $e) {
      \Log::error('Profile update error: ' . $e->getMessage());
      return back()->with('error', 'Failed to update profile: ' . $e->getMessage())->withInput();
    }
  }

  /**
   * Change user password
   */
  public function changePassword(Request $request)
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

    try {
      $request->validate([
        'current_password' => 'required',
        'new_password' => 'required|min:6|confirmed',
      ]);

      $table = $userType === 'student' ? 'students' : 'schoolUsers';

      // Get current user
      $user = DB::connection('tenant')->table($table)->where('id', $userId)->first();

      if (!$user) {
        return back()->with('error', 'User not found');
      }

      // Verify current password
      if (!isset($user->password) || !Hash::check($request->current_password, $user->password)) {
        return back()->with('error', 'Current password is incorrect');
      }

      // Update password
      $updateData = ['password' => Hash::make($request->new_password)];

      // Check if updated_at column exists
      $columns = \Illuminate\Support\Facades\Schema::connection('tenant')->getColumnListing($table);
      if (in_array('updated_at', $columns)) {
        $updateData['updated_at'] = now();
      }

      DB::connection('tenant')->table($table)
        ->where('id', $userId)
        ->update($updateData);

      return redirect()->route('profile.index')->with('success', 'Password changed successfully!');
    } catch (\Exception $e) {
      \Log::error('Password change error: ' . $e->getMessage());
      return back()->with('error', 'Failed to change password: ' . $e->getMessage());
    }
  }

  /**
   * Upload profile picture
   */
  public function uploadPicture(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return response()->json(['error' => 'Unauthorized'], 401);
    }

    try {
      $request->validate([
        'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
      ]);

      $userId = session('user_id');
      $userType = session('user_type', 'staff');

      // Store the image
      $image = $request->file('profile_picture');
      $imageName = $userId . '_' . time() . '.' . $image->getClientOriginalExtension();

      // Ensure directory exists
      $uploadPath = public_path('uploads/profiles');
      if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0755, true);
      }

      $image->move($uploadPath, $imageName);

      $tenantDb = session('school_db');
      config(['database.connections.tenant.database' => $tenantDb]);
      DB::purge('tenant');
      DB::reconnect('tenant');

      $table = $userType === 'student' ? 'students' : 'schoolUsers';

      // Check if profile_picture column exists
      $columns = \Illuminate\Support\Facades\Schema::connection('tenant')->getColumnListing($table);

      if (!in_array('profile_picture', $columns)) {
        return response()->json([
          'error' => 'Profile picture feature is not available. Please contact administrator to update the system.'
        ], 400);
      }

      // Update database
      DB::connection('tenant')->table($table)
        ->where('id', $userId)
        ->update(['profile_picture' => $imageName]);

      // Update session
      session(['profile_picture' => $imageName]);

      return response()->json([
        'success' => true,
        'message' => 'Profile picture updated successfully',
        'image_url' => asset('uploads/profiles/' . $imageName)
      ]);
    } catch (\Exception $e) {
      \Log::error('Profile picture upload error: ' . $e->getMessage());
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }
}
