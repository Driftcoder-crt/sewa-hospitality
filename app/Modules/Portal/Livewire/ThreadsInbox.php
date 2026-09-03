<?php

namespace App\Modules\Portal\Livewire;

use App\Models\ActivityLog;
use App\Modules\Portal\Enums\SenderRole;
use App\Modules\Portal\Enums\ThreadStatus;
use App\Modules\Portal\Events\MessageSent;
use App\Modules\Portal\Models\PortalMessage;
use App\Modules\Portal\Models\PortalThread;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Consultant thread inbox (04 doc §4.4): threads assigned to me via my
 * moves, reply with context, internal notes (audit-trail — never
 * client-visible), thread close. wire:poll keeps the list live (30s).
 */
#[Layout('layouts.admin')]
class ThreadsInbox extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = 'open';

    public ?string $activeId = null;

    public string $reply = '';

    public string $note = '';

    public function select(string $threadId): void
    {
        $this->activeId = $threadId;
        $this->reply = '';
        $this->note = '';
    }

    public function render(): View
    {
        $this->authorize('viewAny', PortalThread::class);

        $threads = PortalThread::query()
            ->when(! auth()->user()->hasPermissionTo('portal.manage'), fn ($q) => $q
                ->whereHas('move', fn ($m) => $m->assignedTo((string) auth()->id())))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->q !== '', fn ($q) => $q->where(fn ($inner) => $inner
                ->where('subject', 'like', '%'.$this->q.'%')
                ->orWhereHas('move', fn ($m) => $m->where('reference', 'like', '%'.$this->q.'%'))))
            ->with(['move', 'organization'])
            ->latest()
            ->paginate(20, ['*'], 'threads');

        $active = $this->activeId !== null
            ? $this->authorizeThread($this->activeId)
            : null;

        $messages = $active?->messages()->with('sender')->get() ?? collect();

        return view('portal.livewire.threads-inbox', [
            'threads' => $threads,
            'active' => $active,
            'messages' => $messages,
            'notes' => $active !== null
                ? ActivityLog::query()
                    ->where('subject_type', $active::class)
                    ->where('subject_id', $active->getKey())
                    ->where('action', 'note')
                    ->latest()
                    ->get()
                : collect(),
        ]);
    }

    public function send(): void
    {
        $thread = $this->authorizeThread($this->activeId);

        $this->validate(['reply' => ['required', 'string', 'min:1', 'max:5000']]);

        $message = PortalMessage::query()->create([
            'thread_id' => $thread->getKey(),
            'sender_user_id' => auth()->id(),
            'sender_role' => SenderRole::Consultant,
            'body' => $this->reply,
        ]);

        $this->reply = '';

        MessageSent::dispatch($message);
        ActivityLogger::log('admin', 'create', $message, ['thread' => $thread->getKey()]);
        $this->dispatch('notify', tone: 'success', message: 'Reply sent — the client sees it in the portal.');
    }

    /** Internal note — audit trail, NEVER client-visible (04 doc §4.4). */
    public function addNote(): void
    {
        $thread = $this->authorizeThread($this->activeId);

        $this->validate(['note' => ['required', 'string', 'min:1', 'max:2000']]);

        ActivityLogger::log('admin', 'note', $thread, ['note' => $this->note], auth()->user());

        $this->note = '';
        $this->dispatch('notify', tone: 'success', message: 'Internal note saved (never client-visible).');
    }

    public function close(): void
    {
        $thread = $this->authorizeThread($this->activeId);

        $thread->forceFill(['status' => ThreadStatus::Closed])->save();
        ActivityLogger::log('admin', 'update', $thread, ['status' => 'closed']);
        $this->dispatch('notify', tone: 'success', message: 'Thread closed.');
    }

    public function reopen(): void
    {
        $thread = $this->authorizeThread($this->activeId);

        $thread->forceFill(['status' => ThreadStatus::Open])->save();
        ActivityLogger::log('admin', 'update', $thread, ['status' => 'open']);
    }

    private function authorizeThread(string $id): PortalThread
    {
        $thread = PortalThread::query()->findOrFail($id);
        $this->authorize('update', $thread);

        return $thread;
    }
}
