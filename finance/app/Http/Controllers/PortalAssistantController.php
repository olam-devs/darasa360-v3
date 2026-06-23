<?php

namespace App\Http\Controllers;

use App\Models\AssistantReport;
use App\Services\PortalAssistantService;
use Illuminate\Http\Request;

class PortalAssistantController extends Controller
{
    public function __construct(
        protected PortalAssistantService $assistant
    ) {}

    public function intents(Request $request)
    {
        $audience = $this->assistant->resolveAudience($request);
        if (! $audience) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        return response()->json([
            'audience' => $audience,
            'intents' => $this->assistant->intentsFor($audience),
            'welcome' => $this->welcomeMessage($audience),
        ]);
    }

    public function ask(Request $request)
    {
        $audience = $this->assistant->resolveAudience($request);
        if (! $audience) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'intent' => 'required|string|max:64',
        ]);

        $answer = $this->assistant->answer($audience, $validated['intent'], $request);

        return response()->json([
            'reply' => $answer['reply'],
            'links' => $answer['links'] ?? [],
        ]);
    }

    public function chat(Request $request)
    {
        $audience = $this->assistant->resolveAudience($request);
        if (! $audience) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $answer = $this->assistant->freeText($validated['message'], $audience, $request);

        return response()->json([
            'reply'  => $answer['reply'],
            'links'  => $answer['links'] ?? [],
        ]);
    }

    public function report(Request $request)
    {
        $audience = $this->assistant->resolveAudience($request);
        if (! $audience) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        AssistantReport::create([
            'audience'  => $audience,
            'message'   => $validated['message'],
            'sender_id' => session('parent_id') ?? session('headmaster_id') ?? session('accountant_id') ?? null,
            'sender_name' => session('parent_name') ?? session('headmaster_name') ?? session('accountant_name') ?? 'Unknown',
            'status'    => 'new',
        ]);

        return response()->json(['ok' => true]);
    }

    public function reports(Request $request)
    {
        $reports = AssistantReport::orderBy('created_at', 'desc')->get();
        return response()->json($reports);
    }

    public function markReportRead($id)
    {
        $report = AssistantReport::findOrFail($id);
        $report->update(['status' => 'read']);
        return response()->json(['ok' => true]);
    }

    protected function welcomeMessage(string $audience): string
    {
        return match ($audience) {
            'parent' => 'Hello! I can answer quick questions about your child\'s fees. Choose a topic below.',
            'headmaster' => 'Hello! Ask about collections, outstanding fees, and school-wide stats (read-only).',
            default => 'Hello! I can answer quick questions about fees, collections, and daily tasks.',
        };
    }
}
