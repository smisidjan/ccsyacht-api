<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Http\Resources\Project\LogbookEntryResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LogbookController extends Controller
{
    public function index(string $projectId, Request $request): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $query = $project->logbookEntries()
            ->with('user');

        // Filter by action type
        if ($request->action_type) {
            $query->where('action_type', $request->action_type);
        }

        // Filter by user
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $entries = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 25);

        return LogbookEntryResource::collection($entries);
    }
}
