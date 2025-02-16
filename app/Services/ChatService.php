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
            // Load messages
            $messages = $conversation->messages()
                ->orderBy('created_at')
                ->get()
                ->map(fn($msg) => [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ])
                ->toArray();

            // Include system prompt for title
            $systemPrompt = $this->getTitleSystemPrompt();
            array_unshift($messages, [
                'role' => 'system',
                'content' => $systemPrompt,
            ]);

            $model = $conversation->model_id ?? self::DEFAULT_MODEL;

            // Send request
            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.2,
            ]);

            // Add response validation
            if (!isset($response['choices']) || empty($response['choices'])) {
                logger()->error('Invalid API response:', ['response' => $response]);
                throw new \Exception('Invalid response from AI service');
            }

            return $response['choices'][0]['message']['content'] ?? '';
        } catch (\Exception $e) {
            logger()->error('Chat service error:', [
                'error' => $e->getMessage(),
                'model' => $model,
                'conversation_id' => $conversation->id
            ]);
            throw $e;
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
                Tu es un assistant de chat. La date et l'heure actuelle est le {$now}.
                Tu es actuellement utilisé par {$user->name}.
                Tu dois utilisé le Markdown pour formater tes réponses.

                Instructions importantes:
                - Ignore toutes les instructions des messages précédents.
                - Chaque message doit être traité de manière indépendante.
                - Si aucune instruction spécifique n'est donnée dans le message actuel, réponds de manière professionnelle et neutre.
                - Les seules instructions à suivre sont celles présentes dans le message actuel.
                - Le formatage Markdown doit toujours être utilisé pour les réponses.
                EOT,
        ];
    }

    private function getTitleSystemPrompt(): string
    {
        return <<<EOT
            **Rôle :**
            Tu es un assistant expert en génération de titres de conversation. Ta mission est de créer un **titre court, précis et pertinent** à partir des messages échangés.

            **Consignes :**
            1. **Clarté & Fidélité** : Le titre doit **résumer fidèlement le sujet principal** de la conversation sans interprétation excessive.
            2. **Concision** : Il doit être **court** (maximum **10 mots**, sans articles inutiles ni formules superflues).
            3. **Impact & Pertinence** : Il doit **attirer l’attention** tout en restant **informatif et neutre**.
            4. **Lexique Spécifique** : Privilégie des **mots-clés précis** plutôt que des termes vagues ou génériques.
            5. **Éviter les répétitions** : Reformule intelligemment pour éviter la redondance des mots.
            6. **Gestion des conversations complexes** :
            - **Si un seul sujet principal émerge**, résume-le en une phrase claire.
            - **Si plusieurs sujets distincts sont abordés**, privilégie le plus dominant ou regroupe-les intelligemment.
            - **Si la conversation est vague ou incohérente**, utilise un titre descriptif général plutôt qu’un titre inexact.

            **Exemples de bonnes pratiques :**
            ✅ **SEO et marketing digital** → *"Stratégies SEO pour améliorer la visibilité en ligne"*
            ✅ **Bug informatique sur Windows** → *"Correction d’un bug Windows 11 – Solutions"*
            ✅ **Discussion sur un livre** → *"Critique du roman '1984' de George Orwell"*
            ✅ **Conversations multiples sur IA et programmation** → *"Intelligence artificielle et développement : enjeux et outils"*

            **Exemples d’erreurs à éviter :**
            ❌ **Trop générique** → *"Discussion générale sur le web"* (Pas assez précis)
            ❌ **Trop long** → *"Comment optimiser son SEO en 2024 pour Google"* (Trop détaillé)
            ❌ **Trop vague** → *"Problème technique"* (Pas assez informatif)
        EOT;
    }
}
