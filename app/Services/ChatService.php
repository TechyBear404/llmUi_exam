<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;

class ChatService
{
    private $baseUrl;
    private $apiKey;
    private $client;
    public const DEFAULT_MODEL = 'meta-llama/llama-3.2-11b-vision-instruct:free';

    public function __construct()
    {
        $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->apiKey = config('services.openrouter.api_key');
        $this->client = $this->createOpenAIClient();
    }

    /**
     * @return array<array-key, array{
     *     id: string,
     *     name: string,
     *     context_length: int,
     *     max_completion_tokens: int,
     *     pricing: array{prompt: int, completion: int}
     * }>
     */
    public function getModels(): array
    {
        return cache()->remember('openai.models', now()->addHour(), function () {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/models');

            return collect($response->json()['data'])
                ->filter(function ($model) {
                    return str_ends_with($model['id'], ':free');
                })
                ->sortBy('name')
                ->map(function ($model) {
                    return [
                        'id' => $model['id'],
                        'name' => $model['name'],
                        'context_length' => $model['context_length'],
                        'max_completion_tokens' => $model['top_provider']['max_completion_tokens'],
                        'pricing' => $model['pricing'],
                    ];
                })
                ->values()
                ->all()
            ;
        });
    }

    /**
     * @param array{role: 'user'|'assistant'|'system'|'function', content: string} $messages
     * @param string|null $model
     * @param float $temperature
     *
     * @return string
     */
    public function streamConversation(array $messages, ?string $model = null, float $temperature = 0.7)
    {
        try {
            logger()->info('Début streamConversation', [
                'model' => $model,
                'temperature' => $temperature,
            ]);

            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
                logger()->info('Modèle par défaut utilisé:', ['model' => $model]);
            }

            $messages = [$this->getChatSystemPrompt(), ...$messages];

            // Méthode "createStreamed" qui renvoie un flux "StreamResponse"
            return $this->client->chat()->createStreamed([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);
        } catch (\Exception $e) {
            logger()->error('Erreur dans streamConversation:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function makeTitle(Conversation $conversation): string
    {
        try {
            // Get conversation messages first
            $conversationMessages = $conversation->messages()
                ->orderBy('created_at')
                ->get()
                ->map(fn($msg) => [
                    'role' => $msg->role,
                    'content' => strip_tags($msg->content), // Remove any HTML/markdown
                ])
                ->toArray();

            // Add system message and user prompt
            $messages = array_merge(
                [$this->getTitleSystemPrompt()],
                $conversationMessages,
                [[
                    'role' => 'user',
                    'content' => "Based on the conversation above, generate a title. Response with ONLY the title."
                ]]
            );

            logger()->info('Messages pour le titre:', ['messages' => $messages]);

            $model = $conversation->model_id ?? self::DEFAULT_MODEL;

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.3, // Reduced for more focused output
                'max_tokens' => 20,
                'stop' => ["\n", "."]  // Stop at newlines or periods
            ]);

            logger()->debug('Réponse API brute:', [
                'response' => $response,
                'content' => $response->choices[0]->message->content ?? null
            ]);

            $title = $response->choices[0]->message->content ?? '';

            if (empty($title)) {
                // Fallback title if generation fails
                return "Conversation #" . $conversation->id;
            }

            return $title;
        } catch (\Exception $e) {
            logger()->error('Chat service error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'model' => $model ?? self::DEFAULT_MODEL,
                'conversation_id' => $conversation->id
            ]);
            // Return fallback title on error
            return "Conversation #" . $conversation->id;
        }
    }

    private function createOpenAIClient(): \OpenAI\Client
    {
        return \OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withBaseUri($this->baseUrl)
            ->make()
        ;
    }

    /**
     * @return array{role: 'system', content: string}
     */
    private function getChatSystemPrompt(): array
    {
        $user = Auth::user();
        $now = now()->locale('fr')->format('l d F Y H:i');

        return [
            'role' => 'system',
            'content' => <<<EOT
                You are a chat assistant. The current date and time is **{$now}**.
                You are currently being used by **{$user->name}**.
                All your responses **must be formatted using Markdown**.

                ### **Important Instructions:**
                - **Ignore all previous instructions** from past messages.
                - **Each message must be processed independently** without considering prior context.
                - If the current message does **not** contain specific instructions, **respond professionally and neutrally**.
                - **Only follow instructions provided in the current message**.
                - Always use **Markdown formatting** for your responses.
                - **Detect the language of the message and respond in the same language**.

                ### **Additional Formatting Rules:**
                - Use `**bold**`, `*italics*`, and `- bullet points` where appropriate.
                - For code, use `inline code` or fenced code blocks (` ``` `).
                EOT,
        ];
    }

    private function getTitleSystemPrompt(): array
    {
        return [
            'role' => 'system',
            'content' => <<<EOT
                You are an assistant specialized in generating conversation titles.
                Your goal is to create a short, clear, and relevant title from the conversation messages.

                Instructions:
                1. The title must accurately reflect the main topic of the conversation.
                2. Keep it short (maximum 10 words).
                3. It should be informative and engaging, without being misleading.
                4. Use specific and evocative words rather than generic terms.
                5. Avoid repetition and unnecessary words.
                6. If multiple topics are discussed, prioritize the most important one.
                7. If the conversation is unclear or vague, use a general but relevant title.
                8. Detect the language of the conversation and generate the title in the same language.
                9. Do NOT format the title using Markdown, quotation marks, or any special syntax. The output must be plain text.

                Output:
                - The title must be in the same language as the conversation.
                - The title must be plain text only (no Markdown, no quotes, no extra formatting).

                Example outputs (Plain Text Only):
                - French: Optimiser son site pour le SEO
                - English: Fixing a bug on Windows 11
                - Spanish: Solución de error en Windows 11
                - German: SEO-Optimierung für bessere Sichtbarkeit

            EOT,
        ];
    }
}
