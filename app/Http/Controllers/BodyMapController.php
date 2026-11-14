<?php

namespace App\Http\Controllers;

use App\Services\BodyMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BodyMapController extends Controller
{
    public function __construct(private readonly BodyMapService $service) {}

    public function index(): View
    {
        $crew = auth()->user()->crewProfile;

        return view('body-map.index', [
            'hasCrew' => (bool) $crew,
            'regions' => $crew ? $this->service->regionsFor($crew) : [],
        ]);
    }

    public function all(Request $request): JsonResponse
    {
        $crew = auth()->user()->crewProfile;

        return response()->json([
            'mission_day' => $crew?->missionDay(),
            'regions' => $crew ? $this->service->regionsFor($crew) : [],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $crew = auth()->user()->crewProfile;

        abort_if(! $crew, 404);

        $data = $this->service->regionDetail($crew, $slug);

        abort_if($data === null, 404);

        return response()->json($data);
    }
}