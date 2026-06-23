<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HelperController extends Controller
{
  function errorResponse($message, $asJson = false)
  {
    if ($asJson) {
      return response()->json([
        'status' => 'error',
        'message' => $message
      ], 401);
    }

    return back()->withErrors(['registration_no' => $message])->withInput();
  }

  public static function isLoggedIn(Request $request)
  {
    Log::info('Session content:helper:', session()->all());
    if (session()->has('user_id')) {
      return true;
    }

    if ($request->has('toJson') && $request->input('toJson') == true) {
      return true;
    }

    return false;
  }
}
