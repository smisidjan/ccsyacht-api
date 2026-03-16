<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Http\Resources\Project\SignerResource;
use App\Models\LogbookEntry;
use App\Models\Project;
use App\Models\User;
use App\Traits\BroadcastsProjectChanges;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SignerController extends Controller
{
    use BroadcastsProjectChanges;
    public function index(string $projectId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $signers = $project->signers()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return SignerResource::collection($signers);
    }

    public function store(string $projectId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $validated = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        // Check if user is a member of this project
        if (!$project->members()->where('user_id', $validated['user_id'])->exists()) {
            return $this->errorResponse('User must be a project member before becoming a signer.');
        }

        // Check if user is already a signer
        if ($project->signers()->where('user_id', $validated['user_id'])->exists()) {
            return $this->errorResponse('User is already a signer for this project.');
        }

        $signer = $project->signers()->create($validated);
        $signer->load('user');

        // Log the action
        $user = User::find($validated['user_id']);
        LogbookEntry::log(
            $project,
            'signer_added',
            "Added {$user->name} as default signer",
            $request->user(),
            ['user_id' => $validated['user_id'], 'user_name' => $user->name]
        );

        // Broadcast the change
        $this->broadcastChange($project, 'signer', 'created', $signer);

        return $this->resourceResponse(new SignerResource($signer), 201);
    }

    public function destroy(string $projectId, string $userId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $signer = $project->signers()->where('user_id', $userId)->first();

        if (!$signer) {
            return $this->errorResponse('User is not a signer for this project.', 404);
        }

        $user = $signer->user;
        $signerId = $signer->id;
        $signer->delete();

        // Log the action
        LogbookEntry::log(
            $project,
            'signer_removed',
            "Removed {$user->name} from default signers",
            $request->user(),
            ['user_id' => $userId, 'user_name' => $user->name]
        );

        // Broadcast the change
        $this->broadcastChange($project, 'signer', 'deleted', null, ['id' => $signerId, 'user_id' => $userId]);

        return $this->successResponse('DeleteAction', 'Signer removed successfully.');
    }
}
