<?php

namespace App\Http\Controllers;

use App\Enums\ResourceType;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Models\Country;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\Tag;
use App\Search\ResourceSearch;
use App\Services\ResourceLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dev Resources (cahier, sections 6 and 7).
 */
class ResourceController extends Controller
{
    public function index(Request $request): View
    {
        $search = ResourceSearch::fromRequest($request);

        return view('resources.index', [
            'resources' => $search->query()->paginate(12)->withQueryString(),
            'filters' => $search->input(),
            'hasFilters' => $search->hasFilters(),
            'sorts' => ResourceSearch::SORTS,
            'popularTags' => Tag::withCount(['resources' => fn ($query) => $query->published()])
                ->orderByDesc('resources_count')->limit(15)->get()
                ->filter(fn (Tag $tag) => $tag->resources_count > 0)->values(),
        ] + $this->formOptions());
    }

    public function create(): View
    {
        Gate::authorize('create', Resource::class);

        return view('resources.create', $this->formOptions());
    }

    public function store(StoreResourceRequest $request, ResourceLibrary $library): RedirectResponse
    {
        Gate::authorize('create', Resource::class);

        $resource = $library->publish($request->user(), $request->resourceData(), $request->file('image'), $request->file('file'));

        return redirect()->route('resources.show', $resource)
            ->with('status', 'Ressource publiée ! +'.config('devscore.points.resource_published').' points DevScore.');
    }

    public function show(Request $request, Resource $resource): View
    {
        abort_unless(Gate::allows('view', $resource), 404);

        $resource->load(['user.country', 'country', 'skills', 'tags']);
        $viewer = $request->user();

        return view('resources.show', [
            'resource' => $resource,
            'reviews' => $resource->reviews()->with('user')->latest()->get(),
            'comments' => $resource->comments()->with('user')->oldest()->get(),
            'myReview' => $viewer ? $resource->reviews()->where('user_id', $viewer->id)->first() : null,
            'hasLiked' => $viewer ? $resource->likers()->whereKey($viewer->id)->exists() : false,
            'canUpdate' => $viewer && Gate::forUser($viewer)->allows('update', $resource),
            'canInteract' => $viewer && Gate::forUser($viewer)->allows('interact', $resource),
            'canComment' => $viewer && Gate::forUser($viewer)->allows('comment', $resource),
        ]);
    }

    public function edit(Resource $resource): View
    {
        Gate::authorize('update', $resource);

        return view('resources.edit', ['resource' => $resource->load(['skills', 'tags'])] + $this->formOptions());
    }

    public function update(UpdateResourceRequest $request, Resource $resource, ResourceLibrary $library): RedirectResponse
    {
        Gate::authorize('update', $resource);

        $library->update($resource, $request->resourceData(), $request->file('image'), $request->file('file'));

        return redirect()->route('resources.show', $resource)->with('status', 'Ressource mise à jour.');
    }

    public function destroy(Resource $resource): RedirectResponse
    {
        Gate::authorize('delete', $resource);

        $resource->delete();

        return redirect()->route('resources.index')->with('status', 'Ressource supprimée.');
    }

    /**
     * "Télécharger": serves the private file and counts the download.
     */
    public function download(Resource $resource, ResourceLibrary $library): StreamedResponse
    {
        abort_unless(Gate::allows('view', $resource), 404);
        abort_unless($resource->file_path && Storage::disk('local')->exists($resource->file_path), 404);

        $resource->increment('downloads_count');

        return Storage::disk('local')->download($resource->file_path, $library->downloadName($resource));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'types' => ResourceType::cases(),
            'countries' => Country::orderBy('name')->get(),
            'skillsByCategory' => Skill::orderBy('name')->get()->groupBy(fn (Skill $skill) => $skill->category->label()),
            'fileExtensions' => StoreResourceRequest::FILE_EXTENSIONS,
        ];
    }
}
