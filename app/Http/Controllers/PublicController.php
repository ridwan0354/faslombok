<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Event;
use App\Services\RankingService;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    protected RankingService $rankingService;

    public function __construct(RankingService $rankingService)
    {
        $this->rankingService = $rankingService;
    }

    public function index()
    {
        $event = Event::withCount(['participants', 'competitions', 'groups'])->where('status', 'active')->first() 
            ?? Event::latest()->first();

        $competitions = Competition::where('event_id', $event->id)
            ->with('latestPublication')
            ->orderBy('name')
            ->get();

        $publishedCount = $competitions->where('status', 'published')->count();

        return view('public.index', compact('event', 'competitions', 'publishedCount'));
    }

    public function pengumuman()
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $publishedCompetitions = Competition::where('event_id', $event->id)
            ->where('status', 'published')
            ->with(['latestPublication', 'awards'])
            ->orderBy('name')
            ->get();

        return view('public.pengumuman', compact('event', 'publishedCompetitions'));
    }

    public function hasilDetail(Competition $competition)
    {
        if ($competition->status !== 'published' || !$competition->latestPublication) {
            abort(404, 'Hasil perlombaan ini belum resmi dipublikasikan.');
        }

        $publication = $competition->latestPublication;
        $snapshot = $publication->payload_snapshot;
        $leaderboard = $snapshot['leaderboard'] ?? [];

        return view('public.hasil-detail', compact('competition', 'publication', 'leaderboard'));
    }

    public function juaraUmum()
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();
        $standings = $this->rankingService->getGroupChampionStandings($event);

        return view('public.juara-umum', compact('event', 'standings'));
    }
}
