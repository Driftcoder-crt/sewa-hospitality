<?php

namespace App\Modules\Portal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Portal\Enums\SenderRole;
use App\Modules\Portal\Events\MessageSent;
use App\Modules\Portal\Models\PortalMessage;
use App\Modules\Portal\Services\TenantAccess;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessagesController extends Controller
{
    public function __construct(private readonly TenantAccess $access) {}

    /** Thread list (04 doc §3) — latest message preview per thread. */
    public function index(): View
    {
        $threads = $this->access->threads()
            ->withCount('messages')
            ->paginate(15);

        return view('portal.messages.index', ['threads' => $threads]);
    }

    /** One thread — messages render server-side; island polls for new. */
    public function show(string $thread): View
    {
        $thread = $this->access->authorizeThread($thread);
        $thread->load(['move', 'organization']);

        $messages = $thread->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        // Reading the thread marks consultant messages as read.
        PortalMessage::query()
            ->where('thread_id', $thread->getKey())
            ->whereNull('read_at')
            ->where('sender_role', 'consultant')
            ->update(['read_at' => now()]);

        return view('portal.messages.show', [
            'thread' => $thread,
            'messages' => $messages,
        ]);
    }

    /**
     * Reply (04 doc §6): plain text, validated; the submitted body is
     * preserved on validation failure (never lost typing) and MessageSent
     * fires so the consultant side + realtime learn about it.
     */
    public function store(Request $request, string $thread): RedirectResponse
    {
        $thread = $this->access->authorizeThread($thread);

        abort_if($thread->status->value === 'closed', 422, 'This conversation is closed.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'media_ids' => ['nullable', 'array', 'max:5'],
            'media_ids.*' => ['string'],
        ]);

        $message = PortalMessage::query()->create([
            'thread_id' => $thread->getKey(),
            'sender_user_id' => $this->access->context()->user()->getKey(),
            'sender_role' => SenderRole::Client,
            'body' => $validated['body'],
            'media_ids' => $validated['media_ids'] ?? [],
        ]);

        ActivityLogger::log('portal', 'create', $message, ['thread' => $thread->getKey()]);

        MessageSent::dispatch($message);

        return redirect()
            ->route('portal.messages.show', $thread)
            ->with('status', 'Message sent — your consultant will reply within the published window.');
    }
}
