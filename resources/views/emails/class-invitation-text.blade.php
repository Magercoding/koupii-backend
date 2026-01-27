Class Invitation

Hello {{ $student->name }}!

You've been invited by {{ $teacher->name }} to join their class:

Class: {{ $class->name }}
@if($class->description)
Description: {{ $class->description }}
@endif
Class Code: {{ $class->class_code }}
Teacher: {{ $teacher->name }}
Invitation expires: {{ $invitation->expires_at->format('F j, Y \a\t g:i A') }}

To accept this invitation, visit: {{ $acceptUrl }}
To decline this invitation, visit: {{ $declineUrl }}

Alternative: You can also join the class manually using the class code "{{ $class->class_code }}" in the app.

---
This invitation was sent to {{ $student->email }}
If you didn't expect this invitation, you can safely ignore this email.

© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.