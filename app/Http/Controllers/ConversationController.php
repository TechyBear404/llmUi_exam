<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

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
            'user_id' => Auth::id(),
        ]);

        $user = User::find(Auth::id());
        $user->update(['last_selected_conversation_id' => $conversation->id]);
        $user->update(['last_used_model' => $validated['model_id']]);

        // Update the user's last used model
        // Auth::user()->update(['last_used_model' => $validated['model_id']]);

        return redirect()->route('conversations.show', $conversation);
    }

    public function show(Conversation $conversation)
    {
        abort_if($conversation->user_id !== Auth::id(), 403);

        auth()->user()->update(['last_selected_conversation_id' => $conversation->id]);

        return Inertia::render('Main/Index', [
            'currentConversation' => $conversation->load('messages'),
            'conversations' => auth()->user()->conversations()->with('messages')->latest()->get(),
            'models' => $this->chatService->getModels(),
            'customInstructions' => auth()->user()->customInstructions()->get(),
        ]);
    }

    public function destroy(Conversation $conversation)
    {
        abort_if($conversation->user_id !== Auth::id(), 403);

        $conversation->delete();
        return redirect()->route('ask.index');
    }

    public function update(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'model' => 'nullable|array',
            'title' => 'nullable|string',
        ]);

        abort_if($conversation->user_id !== Auth::id(), 403);

        $updateData = [];
        if (isset($validated['model'])) {
            $updateData['model_id'] = $validated['model']['id'];
            $updateData['context_length'] = $validated['model']['context_length'];

            $user = User::find(Auth::id());
            $user->update([
                'last_selected_conversation_id' => $conversation->id,
                'last_used_model' => $validated['model']['id'],
            ]);
        }

        if (isset($validated['title'])) {
            $updateData['title'] = $validated['title'];
        }

        $conversation->update($updateData);

        // Return JSON response when it's a title update
        if (isset($validated['title'])) {
            return response()->json(['title' => $conversation->title]);
        }

        return redirect()->route('conversations.show', $conversation);
    }

    public function updateUserModel(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'model' => 'nullable|array',
        ]);

        $user = User::find(Auth::id());
        $user->update(['last_used_model' => $validated['model']['id']]);

        return redirect()->route('ask.index');
    }

    public function updateCustomInstruction(Conversation $conversation)
    {
        abort_if($conversation->user_id !== Auth::id(), 403);

        $conversation->update(['custom_instruction_id' => request('custom_instruction_id')]);

        return redirect()->route('conversations.show', $conversation);
    }

    public function updateTitle(Conversation $conversation)
    {
        abort_if($conversation->user_id !== Auth::id(), 403);

        // $conversation = Conversation::find($conversation->id)->with('messages')->first();
        // dd($conversation);

        try {
            $title = $this->chatService->makeTitle($conversation);
            $conversation->update(['title' => $title]);

            return redirect()->route('conversations.show', $conversation);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update title');
        }
    }
}
