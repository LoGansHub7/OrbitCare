<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AuditEvent;
use App\Models\FollowUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $crew = $request->user()->crewProfile()->firstOrFail();
        $alerts = $crew->alerts()
            ->with('rule')
            ->orderByRaw("CASE WHEN status = 'closed' THEN 1 ELSE 0 END")
            ->orderByDesc('last_evaluated_at')
            ->paginate(10);

        return view('alerts.index', compact('alerts'));
    }

    public function show(Request $request, int $alertId)
    {
        $crew = $request->user()->crewProfile()->firstOrFail();
        $alert = $crew->alerts()
            ->with(['rule', 'followups.actor'])
            ->findOrFail($alertId);

        return view('alerts.show', compact('alert'));
    }

    public function update(Request $request, int $alertId)
    {
        $crew = $request->user()->crewProfile()->firstOrFail();
        $data = $request->validate([
            'action' => ['required', Rule::in(['acknowledge', 'start_review', 'close'])],
            'notes' => ['nullable', 'string', 'max:2000', Rule::requiredIf($request->input('action') === 'close')],
        ]);
        $actorId = $request->user()->id;

        DB::transaction(function () use ($crew, $alertId, $data, $actorId): void {
            $alert = $crew->alerts()->lockForUpdate()->findOrFail($alertId);
            $previousStatus = $alert->status;

            [$nextStatus, $followUpAction] = match ([$data['action'], $previousStatus]) {
                ['acknowledge', 'open'] => ['acknowledged', 'acknowledged'],
                ['start_review', 'acknowledged'] => ['under_review', 'review_started'],
                ['close', 'under_review'] => ['closed', 'closed'],
                default => abort(422, 'This alert action is not available for its current status.'),
            };

            $timestamps = [];
            if ($nextStatus === 'acknowledged') {
                $timestamps['acknowledged_at'] = now();
            }
            if ($nextStatus === 'closed') {
                $timestamps['closed_at'] = now();
            }

            $alert->update(['status' => $nextStatus] + $timestamps);
            FollowUp::create([
                'alert_id' => $alert->id,
                'actor_id' => $actorId,
                'action' => $followUpAction,
                'notes' => $data['notes'] ?? null,
            ]);
            AuditEvent::record('alert.status_changed', $alert, [
                'from' => $previousStatus,
                'to' => $nextStatus,
                'action' => $data['action'],
            ]);
        });

        return redirect()->route('alerts.show', $alertId)->with('status', 'Alert updated and follow-up recorded.');
    }
}
