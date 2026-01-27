<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Invitation</title>
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
        .buttons {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
        }
        .btn-accept {
            background-color: #4CAF50;
            color: white;
        }
        .btn-decline {
            background-color: #f44336;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
        .class-code {
            background-color: #e8f5e8;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎓 Class Invitation</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $student->name }}!</h2>
        
        <p>You've been invited by <strong>{{ $teacher->name }}</strong> to join their class:</p>
        
        <div class="class-info">
            <h3>{{ $class->name }}</h3>
            @if($class->description)
                <p>{{ $class->description }}</p>
            @endif
            
            <p><strong>Class Code:</strong></p>
            <div class="class-code">{{ $class->class_code }}</div>
            
            <p><strong>Teacher:</strong> {{ $teacher->name }}</p>
            <p><strong>Invitation expires:</strong> {{ $invitation->expires_at->format('F j, Y \a\t g:i A') }}</p>
        </div>
        
        <p>You can accept or decline this invitation by clicking the buttons below:</p>
        
        <div class="buttons">
            <a href="{{ $acceptUrl }}" class="btn btn-accept">✅ Accept Invitation</a>
            <a href="{{ $declineUrl }}" class="btn btn-decline">❌ Decline Invitation</a>
        </div>
        
        <p><strong>Alternative:</strong> You can also join the class manually using the class code <strong>{{ $class->class_code }}</strong> in the app.</p>
        
        <div class="footer">
            <p>This invitation was sent to {{ $student->email }}</p>
            <p>If you didn't expect this invitation, you can safely ignore this email.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>