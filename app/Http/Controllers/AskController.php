<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageStreamed;
use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AskController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Conversation::class);

        $models = (new ChatService())->getModels();

        $selectedModel = ChatService::DEFAULT_MODEL;
        $conversations = auth()->user()->conversations()
            ->with('messages')
            ->latest()
            ->get();
        $customInstructions = auth()->user()->customInstructions()->get();

        if (auth()->user()->last_selected_conversation_id) {
            $currentConversation = Conversation::find(auth()->user()->last_selected_conversation_id);
            if ($currentConversation) {
                $this->authorize('view', $currentConversation);
            }
        }

        return Inertia::render('Main/Index', [
            'models' => $models,
            'selectedModel' => $selectedModel,
            'conversations' => $conversations,
            'customInstructions' => $customInstructions,
            'currentConversation' => isset($currentConversation) ? $currentConversation->load('messages') : null,
        ]);
    }

    public function streamMessage(Conversation $conversation, Request $request)
    {
        $this->authorize('update', $conversation);

        $validated = $request->validate([
            'message' => 'required|string',
            'model'   => 'nullable|string',
        ]);

        try {
            Log::info('Starting message stream', [
                'conversation_id' => $conversation->id,
                'model' => $validated['model']
            ]);

            // Check if the last message is the same as the current one
            $lastMessage = $conversation->messages()
                ->orderBy('created_at', 'desc')
                ->first();

            logger()->info('lastUserMessage', [
                'lastMessage' => $lastMessage,
                'message' => $validated['message']
            ]);

            // Only save user message if it's different from the last one
            if (!$lastMessage || $lastMessage->content !== $validated['message']) {
                $conversation->messages()->create([
                    'content' => $validated['message'],
                    'role'    => 'user',
                ]);
            }

            $channelName = "chat.{$conversation->id}";

            // Get conversation history
            $messages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get();
            $maxContextLength = $conversation->max_contecxt_length;
            $currentContextLength = $conversation->context_length;

            $getMessageLength = function ($msg) {
                return str_word_count($msg->content);
            };

            while ($currentContextLength > $maxContextLength && $messages->count() > 0) {
                $oldestMessage = $messages->shift(); // Récupère et supprime le premier message
                $messageLength = $getMessageLength($oldestMessage);
                $currentContextLength -= $messageLength;
            }

            $messages = $messages->map(fn($msg) => [
                'role'    => $msg->role,
                'content' => $msg->content,
            ])->toArray();

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


                $totalTokens = $response->usage->totalTokens ?? 0;
                logger()->info('totalTokens', [
                    'response' => $response,
                    'totalTokens' => $totalTokens
                ]);
                if ($totalTokens) {
                    $conversation->update(['context_length' => $totalTokens]);
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

            // Return the updated conversation with messages
            return response()->json([
                'conversation' => $conversation->fresh()->load('messages')
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
