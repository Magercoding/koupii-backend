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

    public function store(ClassInvitationRequest $request, ClassInvitationService $service)
    {
        Gate::authorize('create', ClassInvitation::class);


        $invitation = $service->create($request->validated(), auth()->user());
        
        return response()->json(['message' => 'Invitation sent', 'data' => new ClassInvitationResource($invitation)], 201);

    }

    public function update(ClassInvitationRequest $request, ClassInvitation $id, ClassInvitationService $service)
    {
        Gate::authorize('update', $id);

        $updated = $service->updateStatus($id, $request->status);

        return response()->json([
            'message' => 'Invitation updated',
            'data' => new ClassInvitationResource($updated)
        ]);
    }

    public function destroy(ClassInvitation $id, ClassInvitationService $service)
    {
        Gate::authorize('delete', $id);

        $service->delete($id);

        return response()->json(['message' => 'Invitation deleted'], 200);
    }


}
