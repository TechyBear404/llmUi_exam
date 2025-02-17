<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ConversationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ChatService $chatService
    ) {}

    public function store(Request $request)
    {
        $this->authorize('create', Conversation::class);

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
        $this->authorize('view', $conversation);

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
        $this->authorize('delete', $conversation);

        $conversation->delete();
        return redirect()->route('ask.index');
    }

    public function update(Request $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        // dd($request->all());

        $validated = $request->validate([
            'model' => 'nullable|array',
            'title' => 'nullable|string',
        ]);

        abort_if($conversation->user_id !== Auth::id(), 403);

        $updateData = [];
        if (isset($validated['model'])) {
            $updateData['model_id'] = $validated['model']['id'];
            $updateData['max_contecxt_length'] = $validated['model']['context_length'];

            $user = User::find(Auth::id());
            $user->update([
                'last_selected_conversation_id' => $conversation->id,
                'last_used_model' => $validated['model']['id'],
            ]);
        }

        if (isset($validated['title'])) {
            $updateData['title'] = $validated['title'];
        }
        // dd($updateData);

        $conversation->update($updateData);

        // Return JSON response when it's a title update
        if (isset($validated['title'])) {
            return response()->json(['title' => $conversation->title]);
        }

        return redirect()->route('conversations.show', $conversation);
    }

    public function updateUserModel(Request $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $validated = $request->validate([
            'model' => 'nullable|array',
        ]);

        $user = User::find(Auth::id());
        $user->update(['last_used_model' => $validated['model']['id']]);

        return redirect()->route('ask.index');
    }

    public function updateCustomInstruction(Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $conversation->update(['custom_instruction_id' => request('custom_instruction_id')]);

        return redirect()->route('conversations.show', $conversation);
    }

    public function updateTitle(Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        try {
            $title = $this->chatService->makeTitle($conversation);
            $conversation->update(['title' => $title]);

            return redirect()->route('conversations.show', $conversation);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update title');
        }
    }
}
