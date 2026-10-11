<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Search\Ranking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * "Classement africain": /ranking?country=guinee&period=month&month=2026-10
     */
    public function index(Request $request): View
    {
        $ranking = Ranking::fromRequest($request);
        $developers = $ranking->query()->paginate(25)->withQueryString();
        $viewer = $request->user();

        return view('ranking.index', [
            'developers' => $developers,
            // Rank of the first developer of the page: 1 on page 1, 26 on page 2…
            'firstRank' => $developers->firstItem() ?? 1,
            'filters' => $ranking->input(),
            'periodLabel' => $ranking->periodLabel(),
            'periods' => Ranking::PERIODS,
            'countries' => Country::orderBy('name')->get(),
            'myRank' => $viewer ? $ranking->rankOf($viewer) : null,
        ]);
    }
}
