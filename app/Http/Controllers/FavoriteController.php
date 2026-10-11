<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Project;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * "Mes favoris" (cahier, section 8): developers, resources and projects saved by the user.
 */
class FavoriteController extends Controller
{
    /** Type names used in URLs (/favorites/developer/12) → models. */
    public const TYPES = [
        'developer' => User::class,
        'resource' => Resource::class,
        'project' => Project::class,
    ];

    public function index(Request $request): View
    {
        $favorites = $request->user()->favorites()
            ->with(['favoritable' => fn (MorphTo $morph) => $morph->morphWith([
                User::class => ['country', 'skills'],
                Resource::class => ['user'],
                Project::class => ['owner', 'country'],
            ])])
            ->latest()
            ->get()
            // Deleted resources/projects come back as null; private or suspended profiles are hidden.
            ->filter(fn (Favorite $favorite) => $this->isVisible($favorite->favoritable));

        return view('favorites.index', [
            'developers' => $this->ofType($favorites, User::class),
            'resources' => $this->ofType($favorites, Resource::class),
            'projects' => $this->ofType($favorites, Project::class),
        ]);
    }

    public function store(Request $request, string $type, int $id): RedirectResponse
    {
        $model = $this->find($type, $id);

        abort_if($model instanceof User && $model->is($request->user()), 422, 'Vous ne pouvez pas vous ajouter à vos favoris.');

        $request->user()->favorites()->firstOrCreate([
            'favoritable_type' => $model->getMorphClass(),
            'favoritable_id' => $model->getKey(),
        ]);

        return back()->with('status', 'Ajouté à vos favoris.');
    }

    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        $model = self::TYPES[$type];

        $request->user()->favorites()
            ->where('favoritable_type', (new $model)->getMorphClass())
            ->where('favoritable_id', $id)
            ->delete();

        return back()->with('status', 'Retiré de vos favoris.');
    }

    private function find(string $type, int $id): Model
    {
        $model = self::TYPES[$type]::findOrFail($id);
        abort_unless($this->isVisible($model), 404);

        return $model;
    }

    /**
     * Profiles and resources follow their own visibility rules; projects are public (deleted ones are already excluded).
     */
    private function isVisible(?Model $model): bool
    {
        return match (true) {
            $model === null => false,
            $model instanceof Project => true,
            default => Gate::allows('view', $model),
        };
    }

    /**
     * @param  Collection<int, Favorite>  $favorites
     * @return Collection<int, Model>
     */
    private function ofType(Collection $favorites, string $class): Collection
    {
        return $favorites->pluck('favoritable')->filter(fn (Model $model) => $model instanceof $class)->values();
    }
}
