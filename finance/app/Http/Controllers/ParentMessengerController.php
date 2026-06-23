<?php

namespace App\Http\Controllers;

use App\Services\PortalAssistantService;
use Illuminate\Http\Request;

/**
 * Webhook-style endpoint for school office WhatsApp/SMS integrations.
 * Parents message the school number; gateway POSTs phone, PIN, and message text here.
 */
class ParentMessengerController extends Controller
{
    public function __construct(
        protected PortalAssistantService $assistant
    ) {}

    public function handle(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:32',
            'pin' => 'required|string|max:64',
            'message' => 'nullable|string|max:500',
        ]);

        $result = $this->assistant->messengerReply(
            $validated['phone'],
            $validated['pin'],
            $validated['message'] ?? 'balance'
        );

        return response()->json($result);
    }
}
