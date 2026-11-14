<?php

namespace App\Http\Controllers;

use App\Models\ChatbotMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Staff view of ORBIT conversation activity. Medical officers see topic counts and
 * emergency timestamps for their assigned crew only. Administrators see aggregate
 * totals across all astronauts, with no names, per-person rows or message text.
 */
class ChatbotOversightController extends Controller
{
    private const WINDOW_DAYS = 30;

    private const TOPIC_LABELS = [
        'metric' => 'Personal metrics',
        'alerts' => 'Alert explanations',
    ];

    public function index(Request $request)
    {
        $windowDays = self::WINDOW_DAYS;
        $since = now()->subDays($windowDays);

        if ($request->user()->isAdmin()) {
            $messages = ChatbotMessage::query()
                ->whereIn('astronaut_id', User::query()->where('role', User::ROLE_ASTRONAUT)->select('id'))
                ->where('created_at', '>=', $since);

            return view('staff.chatbot', [
                'isAdmin' => true,
                'rows' => collect(),
                'windowDays' => $windowDays,
                'aggregate' => [
                    'crew_count' => User::query()->where('role', User::ROLE_ASTRONAUT)->count(),
                    'questions' => (clone $messages)->where('role', 'user')->count(),
                    'emergencies' => (clone $messages)->where('role', 'assistant')->where('intent', 'emergency')->count(),
                    'topics' => $this->topics(clone $messages),
                ],
            ]);
        }

        abort_unless($request->user()->isMedicalOfficer(), 403);

        $rows = $request->user()->assignedCrewProfiles()
            ->with('user')
            ->get()
            ->map(function ($crew) use ($since): array {
                $messages = ChatbotMessage::query()
                    ->where('astronaut_id', $crew->user_id)
                    ->where('created_at', '>=', $since);

                $questions = (clone $messages)->where('role', 'user')->count();

                $emergencies = (clone $messages)->where('role', 'assistant')->where('intent', 'emergency');
                $lastEmergency = (clone $emergencies)->latest('created_at')->value('created_at');

                return [
                    'crew' => $crew,
                    'questions' => $questions,
                    'topics' => $this->topics(clone $messages),
                    'emergency_count' => (clone $emergencies)->count(),
                    'last_emergency_at' => $lastEmergency,
                ];
            });

        return view('staff.chatbot', ['isAdmin' => false, 'rows' => $rows, 'windowDays' => $windowDays, 'aggregate' => null]);
    }

    /**
     * Top five assistant topics for the given message query, without emergency or clarification replies.
     *
     * @return array<int, array{label: string, count: int}>
     */
    private function topics(Builder $messages): array
    {
        return $messages
            ->where('role', 'assistant')
            ->whereNotIn('intent', ['emergency', 'clarify', 'error'])
            ->whereNotNull('intent')
            ->selectRaw('intent, count(*) as total')
            ->groupBy('intent')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'label' => self::TOPIC_LABELS[$row->intent] ?? ucfirst(str_replace('_', ' ', $row->intent)),
                'count' => (int) $row->total,
            ])
            ->all();
    }
}
