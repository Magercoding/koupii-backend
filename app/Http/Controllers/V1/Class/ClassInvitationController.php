<?php

namespace App\Http\Controllers\V1\Class;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Class\ClassInvitationRequest;
use App\Http\Requests\V1\Class\UpdateInvitationRequest;
use App\Http\Resources\V1\Class\ClassInvitationResource;
use App\Models\ClassInvitation;
use App\Services\V1\Class\ClassInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClassInvitationController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $invitations = ClassInvitation::visibleTo($user)->paginate(10);

        return ClassInvitationResource::collection($invitations);
    }

    /**
     * Teacher/Admin: Create a new invitation
     */
    public function store(ClassInvitationRequest $request, ClassInvitationService $service)
    {
        Gate::authorize('create', ClassInvitation::class);

        $invitation = $service->create($request->validated(), auth()->user());
        
        return response()->json([
            'message' => 'Invitation sent successfully',
            'data' => new ClassInvitationResource($invitation)
        ], 201);
    }

    /**
     * Student: Accept an invitation
     */
    public function accept($id, ClassInvitationService $service)
    {
        $invitation = ClassInvitation::findOrFail($id);
        Gate::authorize('update', $invitation);
        
        $user = auth()->user();
        
        // Only students can accept invitations and only their own
        if ($user->role !== 'student' || $invitation->student_id !== $user->id) {
            return response()->json(['message' => 'You can only accept your own invitations'], 403);
        }

        $updated = $service->updateStatus($invitation, 'accepted');

        return response()->json([
            'message' => 'Invitation accepted successfully',
            'data' => new ClassInvitationResource($updated)
        ]);
    }

    /**
     * Student: Decline an invitation
     */
    public function decline($id, ClassInvitationService $service)
    {
        $invitation = ClassInvitation::findOrFail($id);
        Gate::authorize('update', $invitation);
        
        $user = auth()->user();
        
        // Only students can decline invitations and only their own
        if ($user->role !== 'student' || $invitation->student_id !== $user->id) {
            return response()->json(['message' => 'You can only decline your own invitations'], 403);
        }

        $updated = $service->updateStatus($invitation, 'declined');

        return response()->json([
            'message' => 'Invitation declined successfully',
            'data' => new ClassInvitationResource($updated)
        ]);
    }

    /**
     * Teacher/Admin: Update invitation (e.g., change email if wrong person was invited)
     */
    public function update(ClassInvitationRequest $request, $id, ClassInvitationService $service)
    {
        $invitation = ClassInvitation::findOrFail($id);
        Gate::authorize('update', $invitation);
        
        $user = auth()->user();
        
        // Only admin or the teacher who owns the class can update invitation details
        if (!($user->role === 'admin' || ($user->role === 'teacher' && $invitation->class->teacher_id === $user->id))) {
            return response()->json(['message' => 'You can only update invitations for your own classes'], 403);
        }

        $updated = $service->updateInvitation($invitation, $request->validated());

        return response()->json([
            'message' => 'Invitation updated successfully',
            'data' => new ClassInvitationResource($updated)
        ]);
    }

    /**
     * Teacher/Admin: Delete an invitation
     */
    public function destroy($id, ClassInvitationService $service)
    {
        $invitation = ClassInvitation::findOrFail($id);
        Gate::authorize('delete', $invitation);

        $service->delete($invitation);

        return response()->json(['message' => 'Invitation deleted successfully'], 200);
    }
}
