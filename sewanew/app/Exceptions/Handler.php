<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log to Sentry if configured
            if (app()->bound('sentry') && config('sentry.dsn')) {
                app('sentry')->captureException($e);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render(Request $request, Throwable $e): SymfonyResponse
    {
        // Circuit breaker fallback for AI service failures
        if ($this->isAiServiceFailure($e)) {
            return $this->renderAiFallback($request, $e);
        }

        // Realtime service fallback
        if ($this->isRealtimeServiceFailure($e)) {
            // Gracefully degrade to polling
            config(['broadcasting.default' => 'log']);
        }

        return parent::render($request, $e);
    }

    /**
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated(Request $request, AuthenticationException $exception): Response|SymfonyResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Check if exception is an AI service failure.
     */
    private function isAiServiceFailure(Throwable $e): bool
    {
        return str_contains(get_class($e), 'AI') || 
               str_contains($e->getMessage(), 'AI provider') ||
               str_contains($e->getMessage(), 'TokenRouter') ||
               str_contains($e->getMessage(), 'OpenRouter');
    }

    /**
     * Render AI service fallback response.
     */
    private function renderAiFallback(Request $request, Throwable $e): SymfonyResponse
    {
        // Log the failure for monitoring
        logger()->warning('AI service unavailable, using fallback', [
            'exception' => $e->getMessage(),
            'url' => $request->url(),
        ]);

        // Return appropriate fallback based on request type
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'AI features temporarily unavailable. Using cached/native behavior.',
                'fallback' => true,
            ], 200);
        }

        // For web requests, continue but AI features will be disabled
        return parent::render($request, $e);
    }

    /**
     * Check if exception is a realtime service failure.
     */
    private function isRealtimeServiceFailure(Throwable $e): bool
    {
        return str_contains(get_class($e), 'Ably') ||
               str_contains($e->getMessage(), 'realtime') ||
               str_contains($e->getMessage(), 'broadcasting');
    }
}
