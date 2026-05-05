<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Co-Teacher Invitation</title>
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
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .class-info {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
        }
        .class-code {
            background-color: #e8f5e8;
            padding: 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 4px;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 28px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎓 Co-Teacher Invitation</h1>
    </div>

    <div class="content">
        <h2>Hello!</h2>

        <p><strong>{{ $inviter->name }}</strong> has invited you to join their class as a co-teacher:</p>

        <div class="class-info">
            <h3>{{ $class->name }}</h3>
            @if($class->description)
                <p>{{ $class->description }}</p>
            @endif

            <p><strong>Class Owner:</strong> {{ $inviter->name }}</p>

            <p><strong>Class Code:</strong></p>
            <div class="class-code">{{ $class->class_code }}</div>
        </div>

        <p>To join as a co-teacher, open the app and use the <strong>"Join a Class"</strong> button with the code above.</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $joinUrl }}" class="btn">Open App</a>
        </div>

        <div class="footer">
            <p>This invitation was sent to {{ $recipientEmail }}</p>
            <p>If you didn't expect this, you can safely ignore this email.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
