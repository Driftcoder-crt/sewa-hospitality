<div class="admin-screen">
@section('title', 'Invitations — Sewa Admin')

    <h1 class="font-display text-2xl text-ink">Invitations</h1>
    <p class="eyebrow mt-1 text-ink-muted">Portal ops · org users & magic links</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-line bg-paper-2 p-5">
            <h2 class="font-display text-lg">Invite a portal user</h2>
            <form wire:submit="invite" class="mt-3 grid gap-3">
                <div>
                    <label class="text-xs font-semibold text-ink-muted" for="orgId">Organization</label>
                    <select id="orgId" wire:model.live="orgId" required class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        <option value="">Choose…</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                        @endforeach
                    </select>
                    @error('orgId') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-muted" for="invName">Their name</label>
                    <input id="invName" type="text" wire:model="name" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-muted" for="invEmail">Email</label>
                    <input id="invEmail" type="email" wire:model="email" required class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                    @error('email') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-muted" for="roleInOrg">Portal role</label>
                    <select id="roleInOrg" wire:model="roleInOrg" class="mt-1 w-full min-h-[44px] rounded-lg border border-line bg-paper px-3 text-sm focus:border-brand focus:outline-none">
                        <option value="employee">Relocating employee — own move</option>
                        <option value="manager">Mobility manager — org-wide view</option>
                        <option value="billing">Billing contact — invoices</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex min-h-[44px] items-center justify-start self-start rounded-full bg-brand px-6 text-sm font-semibold text-brand-ink hover:opacity-90">
                    Send invitation
                </button>
                @if ($invited)
                    <p class="text-xs text-ink-muted">The invite email carries a single-use link valid 72 hours. Re-inviting the same person for the same role sends a fresh link.</p>
                @endif
            </form>
        </section>

        <section class="rounded-xl border border-line bg-paper-2 p-5">
            <h2 class="font-display text-lg">Members</h2>
            @if ($orgId === '')
                <p class="mt-3 text-sm text-ink-soft">Choose an organization to see its portal members.</p>
            @else
                <ul class="mt-3 flex flex-col gap-2">
                    @forelse ($members as $member)
                        <li class="flex items-center justify-between rounded-lg border border-line px-4 py-3">
                            <div>
                                <p class="text-sm font-medium">{{ $member['user']->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $member['user']->email }} · joined {{ $member['joined']?->format('d M Y') ?? '—' }}</p>
                            </div>
                            <span class="rounded-full bg-paper-3 px-3 py-1 text-xs font-semibold uppercase text-ink-soft">{{ $member['role'] }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-ink-soft">No members yet — send the first invite.</li>
                    @endforelse
                </ul>
            @endif
        </section>
    </div>
</div>
