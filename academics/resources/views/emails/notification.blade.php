<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notificationData['subject'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .message {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            white-space: pre-line;
        }
        .footer {
            background-color: #333;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 5px 5px;
            font-size: 12px;
        }
        .category-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .category-ticket { background-color: #ff9800; color: white; }
        .category-report_card { background-color: #2196F3; color: white; }
        .category-event { background-color: #9c27b0; color: white; }
        .category-default { background-color: #607d8b; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>OLAM School Management System</h1>
    </div>

    <div class="content">
        @if($notificationData['recipientName'])
            <p>Dear {{ $notificationData['recipientName'] }},</p>
        @else
            <p>Hello,</p>
        @endif

        @if($notificationData['category'])
            <span class="category-badge category-{{ $notificationData['category'] }}">
                {{ strtoupper(str_replace('_', ' ', $notificationData['category'])) }}
            </span>
        @endif

        <div class="message">
            {{ $notificationData['message'] }}
        </div>

        <p>
            <strong>This is an automated notification from the OLAM School Management System.</strong>
        </p>

        <p>
            If you have any questions, please contact your school administration or log in to the system.
        </p>

        <p>
            Best regards,<br>
            <strong>OLAM School Management Team</strong>
        </p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} OLAM School Management System. All rights reserved.</p>
        <p>This email was sent to {{ $notificationData['recipientName'] ?? 'you' }} as part of the school notification system.</p>
    </div>
</body>
</html>
