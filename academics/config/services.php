<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Third Party Services
  |--------------------------------------------------------------------------
  |
  | This file is for storing the credentials for third party services such
  | as Mailgun, Postmark, AWS and more. This file provides the de facto
  | location for this type of information, allowing packages to have
  | a conventional file to locate the various service credentials.
  |
  */

  'postmark' => [
    'token' => env('POSTMARK_TOKEN'),
  ],

  'ses' => [
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
  ],

  'slack' => [
    'notifications' => [
      'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
      'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
    ],
  ],

  'nextsms' => [
    'api_key' => env('SMS_API_TOKEN'),
    'sender_name' => env('SMS_SENDER_NAME', 'DARASA 360'),
    'endpoint' => env('SMS_ENDPOINT', 'https://messaging-service.co.tz/api/sms/v2/text/single'),
    'endpoint_multi' => env('SMS_ENDPOINT_MULTI', 'https://messaging-service.co.tz/api/sms/v2/text/multi'),
  ],


];
