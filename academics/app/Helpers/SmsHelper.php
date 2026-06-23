<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class SmsHelper
{
  /**
   * Send bulk SMS via Next SMS multi-send endpoint
   *
   * @param array $messages Array of messages, each with keys: to, text, from (optional)
   * @param int $flash 0 or 1
   * @return array response with success and data/error
   */
  public static function sendNextSmsBulk(array $messages, $flash = 0)
  {
    $endpoint = config('services.nextsms.endpoint_multi'); // multi-send endpoint
    $apiKey = config('services.nextsms.api_key');

    $payload = [
      'messages' => [],
      'flash' => $flash,
      'reference' => uniqid('sms_'),
    ];

    $senderName = config('services.nextsms.sender_name', 'DARASA 360');

    foreach ($messages as $msg) {
      $to = preg_replace('/^0/', '255', $msg['to']); // normalize phone
      $payload['messages'][] = [
        'from' => $senderName,
        'to' => $to,
        'text' => $msg['text'],
      ];
    }

    try {

      $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer $apiKey",
      ])->withOptions([
        'verify' => false,
      ])->post($endpoint, $payload);


      if ($response->successful()) {
        return [
          'success' => true,
          'response' => $response->json(),
        ];
      }

      return [
        'success' => false,
        'error' => $response->body(),
      ];
    } catch (\Exception $e) {
      return [
        'success' => false,
        'error' => $e->getMessage(),
      ];
    }
  }


  /**
   * Calculate number of SMS segments for a message text (160 chars per SMS segment,
   * concatenated messages count 153 chars per segment)
   */
  public static function calculateSmsSegments(string $text): int
  {
    $length = mb_strlen($text);
    if ($length <= 160) {
      return 1;
    }
    return (int) ceil($length / 153);
  }
}
