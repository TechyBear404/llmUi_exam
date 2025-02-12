<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function __construct(
        private ChatService $chatService
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'model_id' => 'required|string',
        ]);

        $conversation = Conversation::Create([
            'model_id' => $validated['model_id'],
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('conversations.show', $conversation);
    }

    public function show(Conversation $conversation)
    {
        abort_if($conversation->user_id !== auth()->id(), 403);

        return Inertia::render('Main/Index', [
            'currentConversation' => $conversation->load('messages'),
            'conversations' => auth()->user()->conversations()->latest()->get(),
            'models' => $this->chatService->getModels(),
        ]);
    }

    public function destroy(Conversation $conversation)
    {
        abort_if($conversation->user_id !== auth()->id(), 403);

        $conversation->delete();
        return redirect()->route('ask.index');
    }
}
