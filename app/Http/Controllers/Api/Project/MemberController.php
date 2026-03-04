<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Http\Resources\Project\MemberResource;
use App\Models\LogbookEntry;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberController extends Controller
{
    public function index(string $projectId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        $members = $project->members()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return MemberResource::collection($members);
    }

    public function store(string $projectId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $validated = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        // Check if user is already a member
        if ($project->members()->where('user_id', $validated['user_id'])->exists()) {
            return $this->errorResponse('User is already a member of this project.');
        }

        $member = $project->members()->create($validated);
        $member->load('user');

        // Log the action
        $user = User::find($validated['user_id']);
        LogbookEntry::log(
            $project,
            'member_added',
            "Added {$user->name} as project member",
            $request->user(),
            ['user_id' => $validated['user_id'], 'user_name' => $user->name]
        );

        return $this->resourceResponse(new MemberResource($member), 201);
    }

    public function destroy(string $projectId, string $userId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $member = $project->members()->where('user_id', $userId)->first();

        if (!$member) {
            return $this->errorResponse('User is not a member of this project.', 404);
        }

        $user = $member->user;
        $member->delete();

        // Log the action
        LogbookEntry::log(
            $project,
            'member_removed',
            "Removed {$user->name} from project members",
            $request->user(),
            ['user_id' => $userId, 'user_name' => $user->name]
        );

        return $this->successResponse('DeleteAction', 'Member removed successfully.');
    }
}
