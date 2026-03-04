<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Http\Resources\Project\DeckResource;
use App\Models\LogbookEntry;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeckController extends Controller
{
    public function index(string $projectId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        $decks = $project->decks()
            ->withCount(['areas', 'stages'])
            ->ordered()
            ->get();

        return DeckResource::collection($decks);
    }

    public function store(string $projectId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Auto-increment sort_order if not provided
        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = $project->decks()->max('sort_order') + 1;
        }

        $deck = $project->decks()->create($validated);
        $deck->loadCount(['areas', 'stages']);

        // Log the action
        LogbookEntry::log(
            $project,
            'deck_created',
            "Created deck '{$deck->name}'",
            $request->user(),
            ['deck_id' => $deck->id, 'deck_name' => $deck->name]
        );

        return $this->resourceResponse(new DeckResource($deck), 201);
    }

    public function show(string $projectId, string $deckId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $deck = $project->decks()
            ->withCount(['areas', 'stages'])
            ->with(['areas' => fn($q) => $q->withCount('stages')->ordered()])
            ->findOrFail($deckId);

        return $this->resourceResponse(new DeckResource($deck));
    }

    public function update(string $projectId, string $deckId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $deck = $project->decks()->findOrFail($deckId);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $deck->update($validated);
        $deck->loadCount(['areas', 'stages']);

        // Log the action
        LogbookEntry::log(
            $project,
            'deck_updated',
            "Updated deck '{$deck->name}'",
            $request->user(),
            ['deck_id' => $deck->id, 'deck_name' => $deck->name]
        );

        return $this->resourceResponse(new DeckResource($deck));
    }

    public function destroy(string $projectId, string $deckId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $deck = $project->decks()->findOrFail($deckId);

        $deckName = $deck->name;
        $deck->delete();

        // Log the action
        LogbookEntry::log(
            $project,
            'deck_deleted',
            "Deleted deck '{$deckName}'",
            $request->user(),
            ['deck_name' => $deckName]
        );

        return $this->successResponse('DeleteAction', 'Deck deleted successfully.');
    }
}
