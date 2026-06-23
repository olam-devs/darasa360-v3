<?php

namespace App\Services;

use App\Models\NotificationQueue;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Process pending notifications from queue
     */
    public function processPendingNotifications($limit = 50)
    {
        $notifications = NotificationQueue::pending()
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_at', 'asc')
            ->limit($limit)
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            try {
                if ($notification->type === 'sms' || $notification->type === 'both') {
                    $this->sendSMS($notification);
                }

                if ($notification->type === 'email' || $notification->type === 'both') {
                    $this->sendEmail($notification);
                }

                $notification->markAsSent();
                $processed++;
            } catch (\Exception $e) {
                $notification->markAsFailed($e->getMessage());
                $failed++;
                Log::error('Notification failed', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'total' => $notifications->count(),
        ];
    }

    /**
     * Send SMS notification
     */
    private function sendSMS($notification)
    {
        if (!$notification->recipient_phone) {
            throw new \Exception('No phone number provided');
        }

        // Use the existing Next SMS integration
        $messages = [[
            'to' => $notification->recipient_phone,
            'text' => $notification->message,
        ]];

        $result = \App\Helpers\SmsHelper::sendNextSmsBulk($messages);

        if (!$result['success']) {
            throw new \Exception('SMS sending failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        // Log to SmsMessage table for tracking
        try {
            SmsMessage::create([
                'phone_number' => $notification->recipient_phone,
                'message' => $notification->message,
                'status' => 'sent',
            ]);
        } catch (\Exception $e) {
            // Continue even if logging fails
            Log::warning('Failed to log SMS to database: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Send Email notification
     */
    private function sendEmail($notification)
    {
        if (!$notification->recipient_email) {
            throw new \Exception('No email address provided');
        }

        try {
            Mail::to($notification->recipient_email)
                ->send(new \App\Mail\NotificationEmail(
                    $notification->subject ?? 'OLAM Notification',
                    $notification->recipient_name,
                    $notification->message,
                    $notification->notification_category
                ));

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Email sending failed: ' . $e->getMessage());
        }
    }

    /**
     * Queue a new notification
     */
    public function queueNotification(array $data)
    {
        return NotificationQueue::create($data);
    }

    /**
     * Queue mass notifications (for report cards, events, etc.)
     */
    public function queueMassNotifications(array $recipients, string $message, string $category, $relatedId = null)
    {
        $queued = 0;

        foreach ($recipients as $recipient) {
            NotificationQueue::create([
                'type' => $recipient['type'] ?? 'sms',
                'recipient_phone' => $recipient['phone'] ?? null,
                'recipient_email' => $recipient['email'] ?? null,
                'recipient_name' => $recipient['name'] ?? null,
                'subject' => $recipient['subject'] ?? null,
                'message' => $message,
                'status' => 'pending',
                'priority' => $recipient['priority'] ?? 'medium',
                'notification_category' => $category,
                'related_id' => $relatedId,
                'related_type' => $category,
                'scheduled_at' => $recipient['scheduled_at'] ?? now(),
            ]);

            $queued++;
        }

        return $queued;
    }

    /**
     * Retry failed notifications
     */
    public function retryFailedNotifications($maxRetries = 3)
    {
        $failed = NotificationQueue::where('status', 'failed')
            ->where('retry_count', '<', $maxRetries)
            ->get();

        foreach ($failed as $notification) {
            $notification->update(['status' => 'pending']);
        }

        return $failed->count();
    }

    /**
     * Get notification statistics
     */
    public function getStatistics()
    {
        return [
            'pending' => NotificationQueue::where('status', 'pending')->count(),
            'sent' => NotificationQueue::where('status', 'sent')->count(),
            'failed' => NotificationQueue::where('status', 'failed')->count(),
            'high_priority' => NotificationQueue::highPriority()->where('status', 'pending')->count(),
        ];
    }
}
