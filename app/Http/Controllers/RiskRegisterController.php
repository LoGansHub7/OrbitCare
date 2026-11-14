<?php

namespace App\Http\Controllers;

use App\Models\Hazard;
use App\Models\Reference;
use App\Models\Risk;
use Illuminate\Http\Request;

class RiskRegisterController extends Controller
{
    public function risks(Request $request)
    {
        $slug = $this->hazardSlug($request);
        $search = trim((string) $request->query('q', ''));

        $risks = Risk::query()
            ->with('hazard')
            ->when($slug, fn ($q) => $q->whereHas('hazard', fn ($h) => $h->where('slug', $slug)))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('summary', 'like', "%{$search}%")))
            ->orderBy('hazard_id')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Risk $risk) => $risk->hazard->name);

        return view('risks.index', [
            'groups' => $risks,
            'hazards' => Hazard::orderBy('sort_order')->get(['slug', 'name']),
            'hazardSlug' => $slug,
            'search' => $search,
        ]);
    }

    public function evidence(Request $request)
    {
        $slug = $this->hazardSlug($request);
        $search = trim((string) $request->query('q', ''));

        $references = Reference::query()
            ->with('hazard')
            ->when($slug, fn ($q) => $q->whereHas('hazard', fn ($h) => $h->where('slug', $slug)))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', "%{$search}%")->orWhere('publisher', 'like', "%{$search}%")->orWhere('summary', 'like', "%{$search}%")))
            ->orderByDesc('published_at')
            ->orderBy('title')
            ->get();

        return view('evidence.index', [
            'references' => $references,
            'hazards' => Hazard::orderBy('sort_order')->get(['slug', 'name']),
            'hazardSlug' => $slug,
            'search' => $search,
        ]);
    }

    private function hazardSlug(Request $request): ?string
    {
        $slug = $request->query('hazard');

        return is_string($slug) && Hazard::where('slug', $slug)->exists() ? $slug : null;
    }
}
