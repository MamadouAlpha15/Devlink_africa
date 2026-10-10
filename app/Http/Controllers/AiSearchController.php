<?php

namespace App\Http\Controllers;

use App\Ai\DevLinkAi;
use App\Models\AiSearch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * DevLink AI: "Je cherche un développeur Flutter en Guinée…" → recommended profiles with a match %.
 */
class AiSearchController extends Controller
{
    public const EXAMPLES = [
        'Je cherche un développeur Flutter en Guinée pour travailler sur une application de livraison.',
        'Il me faut un expert Laravel disponible pour un projet fintech au Sénégal.',
        'Je cherche un designer UI/UX pour une application de santé.',
        'Un développeur mobile en Côte d\'Ivoire pour une plateforme agricole.',
    ];

    public function create(Request $request): View
    {
        return view('ai.create', [
            'examples' => self::EXAMPLES,
            'recentSearches' => $request->user()->aiSearches()->latest()->limit(5)->get(),
        ]);
    }

    public function store(Request $request, DevLinkAi $ai): RedirectResponse
    {
        $validated = $request->validate(
            ['query' => ['required', 'string', 'min:10', 'max:500']],
            [],
            ['query' => 'demande'],
        );

        $search = $ai->search($request->user(), $validated['query']);

        return redirect()->route('ai.show', $search);
    }

    public function show(Request $request, AiSearch $aiSearch): View
    {
        abort_unless($aiSearch->user_id === $request->user()->id, 404);

        // Results were stored as ids; reload the profiles that are still visible.
        $users = User::query()->listed()
            ->with(['country', 'skills'])
            ->whereIn('id', collect($aiSearch->results)->pluck('user_id'))
            ->get()
            ->keyBy('id');

        $results = collect($aiSearch->results)
            ->filter(fn (array $result) => $users->has($result['user_id']))
            ->map(fn (array $result) => $result + ['user' => $users[$result['user_id']]])
            ->values();

        return view('ai.show', [
            'search' => $aiSearch,
            'criteria' => $aiSearch->criteria,
            'results' => $results,
        ]);
    }
}
