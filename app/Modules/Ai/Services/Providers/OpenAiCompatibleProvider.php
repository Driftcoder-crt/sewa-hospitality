<?php

namespace App\Modules\Ai\Services\Providers;

use Illuminate\Support\Facades\Http;

/**
 * Thin OpenAI-compatible chat adapter (08-ai-system/01 §2). One class,
 * N providers — the provider identity is config; the wire format is
 * identical everywhere. RECORDED DEVIATION: replaces the first-party
 * Laravel AI SDK (not on the locked composer allowlist) while keeping
 * the config-only provider-swapping contract intact.
 *
 * Throws on any transport/API problem — the gateway + breaker own the
 * degradation policy; this class is deliberately dumb.
 */
final class OpenAiCompatibleProvider
{
    public function __construct(private readonly string $id) {}

    public function id(): string
    {
        return $this->id;
    }

    public function baseUrl(): string
    {
        return rtrim((string) config("ai.providers.{$this->id}.base_url", ''), '/');
    }

    public function key(): ?string
    {
        $key = config("ai.providers.{$this->id}.key");

        return is_string($key) && $key !== '' ? $key : null;
    }

    public function defaultModel(): ?string
    {
        $model = config("ai.providers.{$this->id}.default_model");

        return is_string($model) && $model !== '' ? $model : null;
    }

    /** Is this provider usable at all (configured + keyed)? */
    public function isConfigured(): bool
    {
        return $this->baseUrl() !== '' && $this->key() !== null;
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @return array{content: string, tokens_in: int, tokens_out: int, model: string}
     */
    public function chat(string $model, array $messages, array $options = []): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException("AI provider [{$this->id}] is not configured.");
        }

        $body = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.2,
        ];

        if (isset($options['max_tokens'])) {
            $body['max_tokens'] = (int) $options['max_tokens'];
        }

        if (isset($options['response_format'])) {
            $body['response_format'] = $options['response_format'];
        }

        $response = Http::baseUrl($this->baseUrl())
            ->withToken((string) $this->key())
            ->timeout((int) config('ai.timeout', 30))
            ->connectTimeout((int) config('ai.connect_timeout', 10))
            ->post('/chat/completions', $body);

        if ($response->failed()) {
            throw new \RuntimeException(
                "AI provider [{$this->id}] HTTP ".$response->status().' — '.str((string) $response->body())->limit(180),
            );
        }

        $payload = $response->json();

        $content = (string) data_get($payload, 'choices.0.message.content', '');

        if ($content === '') {
            throw new \RuntimeException("AI provider [{$this->id}] returned an empty completion.");
        }

        return [
            'content' => $content,
            'tokens_in' => (int) data_get($payload, 'usage.prompt_tokens', 0),
            'tokens_out' => (int) data_get($payload, 'usage.completion_tokens', 0),
            'model' => (string) (data_get($payload, 'model') ?: $model),
        ];
    }
}
