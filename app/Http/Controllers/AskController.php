<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageStreamed;
use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AskController extends Controller
{
    public function index()
    {
        $models = (new ChatService())->getModels();
        $selectedModel = ChatService::DEFAULT_MODEL;
        $conversations = auth()->user()->conversations()
            ->with('messages')
            ->latest()
            ->get();
        $customInstructions = auth()->user()->customInstructions()->get();
        if (auth()->user()->last_selected_conversation_id) {
            $currentConversation = Conversation::find(auth()->user()->last_selected_conversation_id);
        }


        return Inertia::render('Main/Index', [
            'models' => $models,
            'selectedModel' => $selectedModel,
            'conversations' => $conversations,
            'customInstructions' => $customInstructions,
            'currentConversation' => $currentConversation->load('messages'),
        ]);
    }

    public function streamMessage(Conversation $conversation, Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'model'   => 'nullable|string',
        ]);

        try {
            Log::info('Starting message stream', [
                'conversation_id' => $conversation->id,
                'model' => $validated['model']
            ]);

            // Save user message first
            $userMessage = $conversation->messages()->create([
                'content' => $validated['message'],
                'role'    => 'user',
            ]);

            $channelName = "chat.{$conversation->id}";

            // Get conversation history
            $messages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($msg) => [
                    'role'    => $msg->role,
                    'content' => $msg->content,
                ])
                ->toArray();


            // Create empty assistant message
            $assistantMessage = $conversation->messages()->create([
                'content' => '',
                'role'    => 'assistant',
            ]);

            // Stream the response
            $stream = (new ChatService())->streamConversation(
                messages: $messages,
                model: $conversation->model ?? $request->user()->last_used_model ?? ChatService::DEFAULT_MODEL,
                conversation: $conversation,
            );

            $fullResponse = '';
            $lastBroadcastTime = microtime(true);

            foreach ($stream as $response) {
                $chunk = $response->choices[0]->delta->content ?? '';
                if ($chunk) {
                    $fullResponse .= $chunk;
                    $currentTime = microtime(true);

                    // Broadcast every 100ms to avoid overwhelming the connection
                    if ($currentTime - $lastBroadcastTime >= 0.1) {
                        broadcast(new ChatMessageStreamed(
                            $channelName,
                            $fullResponse,
                            false
                        ));
                        $lastBroadcastTime = $currentTime;
                    }
                }
            }

            // Update the assistant message with complete response
            $assistantMessage->update(['content' => $fullResponse]);

            // Send final message
            broadcast(new ChatMessageStreamed(
                $channelName,
                $fullResponse,
                true
            ));

            return response()->json([
                'status' => 'success',
                'message' => $assistantMessage
            ]);
        } catch (\Exception $e) {
            Log::error('Error in stream message', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id
            ]);

            broadcast(new ChatMessageStreamed(
                $channelName ?? "chat.{$conversation->id}",
                "Error: " . $e->getMessage(),
                true,
                true
            ));

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
