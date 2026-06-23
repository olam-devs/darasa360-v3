<?php

namespace App\Http\Controllers;

use App\Models\NotificationQueue;
use App\Models\Suggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
  /**
   * Get notifications for the current user
   */
  public function getNotifications(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return response()->json(['error' => 'Unauthorized'], 401);
    }

    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return response()->json(['error' => 'Tenant database not found'], 400);
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    try {
      $userId = session('user_id');
      $userRole = session('role');

      // Get recent notifications (last 15) - general broadcast notifications
      $notifications = DB::connection('tenant')
        ->table('notification_queue')
        ->where('status', 'sent')
        ->where('notification_category', '!=', 'sms') // Exclude individual SMS notifications
        ->orderBy('created_at', 'desc')
        ->limit(15)
        ->get()
        ->map(function ($notification) {
          return [
            'id' => $notification->id,
            'title' => $notification->subject ?? 'Notification',
            'message' => strlen($notification->message) > 80
              ? substr($notification->message, 0, 80) . '...'
              : $notification->message,
            'time' => $this->timeAgo($notification->created_at),
            'type' => $notification->notification_category ?? 'general',
            'read' => false,
          ];
        });

      // Get unread count (recent notifications from last 7 days)
      $unreadCount = DB::connection('tenant')
        ->table('notification_queue')
        ->where('status', 'sent')
        ->where('notification_category', '!=', 'sms')
        ->where('created_at', '>=', now()->subDays(7))
        ->count();

      return response()->json([
        'notifications' => $notifications,
        'unreadCount' => min($unreadCount, 99),
      ]);
    } catch (\Exception $e) {
      \Log::error('Error fetching notifications: ' . $e->getMessage());
      return response()->json([
        'notifications' => [],
        'unreadCount' => 0,
      ]);
    }
  }

  /**
   * Get messages for the current user
   */
  public function getMessages(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return response()->json(['error' => 'Unauthorized'], 401);
    }

    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return response()->json(['error' => 'Tenant database not found'], 400);
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    try {
      $userId = session('user_id');
      $roleId = session('role_id');

      // Get recent messages/suggestions (last 10)
      $messages = DB::connection('tenant')
        ->table('suggestions')
        ->leftJoin('schoolUsers', 'suggestions.sender_id', '=', 'schoolUsers.id')
        ->where(function ($query) use ($roleId, $userId) {
          $query->where('suggestions.destination_role_id', $roleId)
                ->orWhere('suggestions.receiver_id', $userId);
        })
        ->select(
          'suggestions.*',
          'schoolUsers.username as sender_name',
          'schoolUsers.registration_no as sender_reg'
        )
        ->orderBy('suggestions.created_at', 'desc')
        ->limit(10)
        ->get()
        ->map(function ($message) {
          return [
            'id' => $message->id,
            'sender' => $message->sender_name ?? 'Unknown',
            'senderReg' => $message->sender_reg ?? 'N/A',
            'content' => strlen($message->content) > 60
              ? substr($message->content, 0, 60) . '...'
              : $message->content,
            'time' => $this->timeAgo($message->created_at),
            'status' => $message->status ?? 'unread',
            'hasReply' => !empty($message->reply),
          ];
        });

      // Get unread count
      $unreadCount = DB::connection('tenant')
        ->table('suggestions')
        ->where(function ($query) use ($roleId, $userId) {
          $query->where('destination_role_id', $roleId)
                ->orWhere('receiver_id', $userId);
        })
        ->where('status', 'unread')
        ->count();

      return response()->json([
        'messages' => $messages,
        'unreadCount' => $unreadCount > 99 ? '99+' : $unreadCount,
      ]);
    } catch (\Exception $e) {
      \Log::error('Error fetching messages: ' . $e->getMessage());
      return response()->json([
        'messages' => [],
        'unreadCount' => 0,
      ]);
    }
  }

  /**
   * Helper function to convert timestamp to "time ago" format
   */
  private function timeAgo($timestamp)
  {
    $time = strtotime($timestamp);
    $diff = time() - $time;

    if ($diff < 60) {
      return 'Just now';
    } elseif ($diff < 3600) {
      $mins = floor($diff / 60);
      return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
      $hours = floor($diff / 3600);
      return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
      $days = floor($diff / 86400);
      return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
      return date('M j, Y', $time);
    }
  }
}
